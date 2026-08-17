<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\EACSession;
use App\Models\Patient;
use App\Models\User;
use App\Models\ViralLoad;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_assistant_returns_ollama_answer_with_patient_context(): void
    {
        putenv('VILOCARE_AI_PROVIDER=ollama');
        config()->set('services.ollama.base_url', 'http://127.0.0.1:11434');
        config()->set('services.ollama.model', 'llama3.1:8b');

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => 'This patient needs EAC follow-up and a reviewed SMS draft.',
                ],
            ], 200),
        ]);

        $patient = $this->createPatientBundle();
        $user = $this->createUser();

        $result = app(ChatbotService::class)->askOperationalAssistant(
            'Summarize this patient and draft an SMS.',
            ['period' => 'All time', 'totals' => ['patients' => 1], 'reports' => []],
            $user,
            $patient->load(['viralLoads', 'eacSessions', 'appointments', 'payments'])
        );

        $this->assertSame('sent', $result['status']);
        $this->assertSame('This patient needs EAC follow-up and a reviewed SMS draft.', $result['answer']);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return str_contains((string) data_get($payload, 'messages.1.content'), 'EE-TR-0099')
                && str_contains((string) data_get($payload, 'messages.1.content'), 'patient_attention_signals')
                && str_contains((string) data_get($payload, 'messages.1.content'), 'Summarize this patient and draft an SMS.');
        });
    }

    public function test_operational_assistant_returns_openrouter_answer(): void
    {
        putenv('VILOCARE_AI_PROVIDER=openrouter');
        config()->set('services.openrouter.api_key', 'or-test-key');
        config()->set('services.openrouter.model', 'openrouter/free');
        config()->set('services.openrouter.endpoint', 'https://openrouter.ai/api/v1/chat/completions');

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Here is a safe SMS draft and a short patient summary.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $patient = $this->createPatientBundle();
        $user = $this->createUser();

        $result = app(ChatbotService::class)->askOperationalAssistant(
            'Draft an SMS and explain the follow-up need.',
            ['period' => 'All time', 'totals' => ['patients' => 1], 'reports' => []],
            $user,
            $patient->load(['viralLoads', 'eacSessions', 'appointments', 'payments'])
        );

        $this->assertSame('sent', $result['status']);
        $this->assertSame('Here is a safe SMS draft and a short patient summary.', $result['answer']);
    }

    public function test_patient_attention_signals_flag_unsuppressed_and_pending_eac(): void
    {
        $patient = $this->createPatientBundle();

        $signals = app(ChatbotService::class)->patientAttentionSignals(
            $patient->load(['viralLoads', 'eacSessions', 'appointments', 'payments'])
        );

        $titles = collect($signals)->pluck('title')->all();

        $this->assertContains('Unsuppressed viral load', $titles);
        $this->assertContains('Pending EAC follow-up due', $titles);
    }

    private function createPatientBundle(): Patient
    {
        $patient = Patient::query()->create([
            'art_number' => 'EE-TR-0099',
            'first_name' => 'Sarah',
            'last_name' => 'Ladu',
            'sex' => 'Female',
            'phone' => '+211900000099',
            'age' => 31,
        ]);

        ViralLoad::query()->create([
            'patient_id' => $patient->patient_id,
            'sample_date' => '2026-08-01',
            'result_date' => '2026-08-08',
            'result_cpml' => 1850,
            'vl_testing_indication' => 'Routine',
        ]);

        EACSession::query()->create([
            'patient_id' => $patient->patient_id,
            'session_number' => 1,
            'session_date' => '2026-08-05',
            'completion_status' => 'Pending',
        ]);

        Appointment::query()->create([
            'patient_id' => $patient->patient_id,
            'appointment_date' => '2026-08-20',
            'reason' => 'EAC follow-up',
            'status' => 'Pending',
        ]);

        return $patient;
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Clinician User',
            'username' => 'clinician_user',
            'role' => 'Clinician',
            'email' => 'clinician@example.com',
            'password' => bcrypt('password'),
            'must_change_password' => false,
        ]);
    }
}
