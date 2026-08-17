<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ChatbotService
{
    public function isConfigured(): bool
    {
        return match ($this->provider()) {
            'ollama' => $this->ollamaModel() !== '',
            'openrouter' => $this->openRouterApiKey() !== '' && $this->openRouterModel() !== '',
            default => false,
        };
    }

    public function providerLabel(): string
    {
        return match ($this->provider()) {
            'ollama' => 'Ollama',
            'openrouter' => 'OpenRouter',
            default => 'AI',
        };
    }

    public function askDashboardAssistant(string $question, array $summary, User $user): array
    {
        $instructions = 'You are the ViLoCare dashboard assistant. Only help with dashboard metrics, report options, workflow explanations, and administrative summaries. Never provide diagnosis, treatment advice, or patient-specific clinical recommendations. If asked for unsafe or unsupported guidance, say so clearly and redirect to a clinician or administrator.';

        $context = [
            'user_role' => $user->role,
            'period' => $summary['period'] ?? 'All time',
            'totals' => $summary['totals'] ?? [],
            'reports' => $summary['reports'] ?? [],
        ];

        return $this->sendAssistantRequest(
            instructions: $instructions,
            question: $question,
            inputContext: [
                'dashboard_summary' => $context,
            ],
            user: $user,
            category: 'dashboard_assistant',
            subject: 'Dashboard assistant request'
        );
    }

    public function askOperationalAssistant(string $question, array $summary, User $user, ?Patient $patient = null): array
    {
        $instructions = 'You are the ViLoCare operational assistant. Help staff explain system workflows, summarize grounded patient follow-up data, draft safe SMS wording, and highlight operational attention flags. You must stay anchored to the supplied ViLoCare context. Never invent records, never claim a message was sent unless the system says so, and never provide diagnosis, treatment instructions, or medication changes. If a user asks for clinical advice, say that a clinician must decide.';

        $context = [
            'user_role' => $user->role,
            'system_summary' => [
                'period' => $summary['period'] ?? 'All time',
                'totals' => $summary['totals'] ?? [],
                'reports' => $summary['reports'] ?? [],
            ],
            'capabilities' => [
                'can_explain_workflows' => true,
                'can_draft_sms' => true,
                'can_flag_attention_needs' => true,
                'cannot_send_sms_directly' => true,
                'cannot_make_clinical_decisions' => true,
            ],
        ];

        if ($patient) {
            $context['patient'] = $this->patientContext($patient);
            $context['patient_attention_signals'] = $this->patientAttentionSignals($patient);
        }

        return $this->sendAssistantRequest(
            instructions: $instructions,
            question: $question,
            inputContext: $context,
            user: $user,
            category: 'operational_ai_assistant',
            subject: $patient ? 'Patient assistant request' : 'Operational assistant request'
        );
    }

    public function patientAttentionSignals(Patient $patient): array
    {
        $patient->loadMissing(['viralLoads', 'eacSessions', 'appointments', 'payments']);

        $latestViralLoad = $patient->viralLoads->sortByDesc(fn ($item) => $item->result_date ?: $item->sample_date)->first();
        $pendingEac = $patient->eacSessions
            ->where('completion_status', 'Pending')
            ->sortBy('session_date')
            ->first();
        $completedSessionThree = $patient->eacSessions
            ->where('session_number', 3)
            ->where('completion_status', 'Completed')
            ->sortByDesc('session_date')
            ->first();
        $missedAppointment = $patient->appointments
            ->where('status', 'Missed')
            ->sortByDesc('appointment_date')
            ->first();
        $nextAppointment = $patient->appointments
            ->where('status', 'Pending')
            ->sortBy('appointment_date')
            ->first();

        $signals = [];

        if ($latestViralLoad && (float) ($latestViralLoad->result_cpml ?? 0) >= 1000) {
            $signals[] = [
                'level' => 'high',
                'title' => 'Unsuppressed viral load',
                'detail' => 'Latest recorded viral load is ' . number_format((float) $latestViralLoad->result_cpml, 0) . ' cp/ml.',
            ];
        }

        if ($pendingEac && $pendingEac->session_date && Carbon::parse($pendingEac->session_date)->isPast()) {
            $signals[] = [
                'level' => 'high',
                'title' => 'Pending EAC follow-up due',
                'detail' => 'EAC session ' . $pendingEac->session_number . ' is due from ' . Carbon::parse($pendingEac->session_date)->format('d M Y') . '.',
            ];
        }

        if ($completedSessionThree) {
            $dueDate = $completedSessionThree->next_session_date ?: $completedSessionThree->session_date;
            if ($dueDate && Carbon::parse($dueDate)->isPast()) {
                $signals[] = [
                    'level' => 'medium',
                    'title' => 'Repeat VL follow-up due',
                    'detail' => 'Repeat viral load is due from ' . Carbon::parse($dueDate)->format('d M Y') . ' after completed EAC session 3.',
                ];
            }
        }

        if ($missedAppointment) {
            $signals[] = [
                'level' => 'medium',
                'title' => 'Missed appointment',
                'detail' => 'Last missed appointment was on ' . Carbon::parse($missedAppointment->appointment_date)->format('d M Y') . '.',
            ];
        }

        if (! $patient->phone) {
            $signals[] = [
                'level' => 'low',
                'title' => 'No phone number captured',
                'detail' => 'Manual outreach may be needed because patient SMS cannot be sent.',
            ];
        }

        if ($nextAppointment) {
            $signals[] = [
                'level' => 'info',
                'title' => 'Upcoming appointment available',
                'detail' => 'Next pending appointment is on ' . Carbon::parse($nextAppointment->appointment_date)->format('d M Y') . '.',
            ];
        }

        if ($signals === []) {
            $signals[] = [
                'level' => 'info',
                'title' => 'No active operational alerts',
                'detail' => 'The available patient data does not currently show an urgent workflow issue.',
            ];
        }

        return $signals;
    }

    private function patientContext(Patient $patient): array
    {
        $patient->loadMissing(['viralLoads', 'eacSessions', 'appointments', 'payments']);

        $latestViralLoad = $patient->viralLoads->sortByDesc(fn ($item) => $item->result_date ?: $item->sample_date)->first();

        return [
            'patient_id' => $patient->patient_id,
            'art_number' => $patient->art_number,
            'name' => trim($patient->first_name . ' ' . $patient->last_name),
            'sex' => $patient->sex,
            'age' => $patient->age,
            'phone' => $patient->phone,
            'current_regimen' => $patient->current_regimen,
            'latest_viral_load' => $latestViralLoad ? [
                'result_cpml' => $latestViralLoad->result_cpml,
                'result_date' => $latestViralLoad->result_date,
                'sample_date' => $latestViralLoad->sample_date,
                'testing_indication' => $latestViralLoad->vl_testing_indication,
                'status' => ((float) ($latestViralLoad->result_cpml ?? 0) >= 1000) ? 'Unsuppressed' : 'Suppressed',
            ] : null,
            'recent_eac_sessions' => $patient->eacSessions
                ->take(3)
                ->map(fn ($session) => [
                    'session_number' => $session->session_number,
                    'session_date' => $session->session_date,
                    'next_session_date' => $session->next_session_date,
                    'completion_status' => $session->completion_status,
                ])
                ->values()
                ->all(),
            'recent_appointments' => $patient->appointments
                ->take(3)
                ->map(fn ($appointment) => [
                    'date' => $appointment->appointment_date,
                    'reason' => $appointment->reason,
                    'status' => $appointment->status,
                ])
                ->values()
                ->all(),
            'recent_payments' => $patient->payments
                ->take(3)
                ->map(fn ($payment) => [
                    'service' => $payment->service_label,
                    'amount' => $payment->currency . ' ' . number_format((float) $payment->amount, 2),
                    'method' => $payment->payment_method,
                    'status' => $payment->status,
                ])
                ->values()
                ->all(),
        ];
    }

    private function sendAssistantRequest(
        string $instructions,
        string $question,
        array $inputContext,
        User $user,
        string $category,
        string $subject
    ): array {
        if (! $this->isConfigured()) {
            return [
                'status' => 'skipped',
                'answer' => $this->providerLabel() . ' is not configured yet. Add the required environment values to enable the assistant.',
            ];
        }

        $provider = $this->provider();
        $model = $this->activeModel();
        $response = match ($provider) {
            'ollama' => $this->sendViaOllama($instructions, $question, $inputContext),
            default => $this->sendViaOpenRouter($instructions, $question, $inputContext),
        };

        if (! $response->successful()) {
            $errorCode = (string) data_get($response->json(), 'error.code', '');
            $errorMessage = (string) data_get($response->json(), 'error.message', '');
            $responseBody = $response->body();

            if ($provider === 'ollama' && str_contains(strtolower($responseBody), 'connection refused')) {
                return [
                    'status' => 'failed',
                    'answer' => 'Ollama is selected, but the local Ollama server is not reachable. Start Ollama and load the configured model, then try again.',
                    'detail' => $responseBody,
                ];
            }

            $friendlyMessage = match ($errorCode) {
                'insufficient_quota' => 'The AI provider has no available quota right now. Please review the provider plan or limits, then try again.',
                'invalid_api_key' => 'The configured AI API key was rejected. Please verify the provider credentials and try again.',
                default => 'The assistant could not respond right now. Please verify the selected AI provider credentials and try again.',
            };

            return [
                'status' => 'failed',
                'answer' => $friendlyMessage,
                'detail' => $errorMessage !== '' ? $errorMessage : $responseBody,
            ];
        }

        $answer = $this->extractAnswer($response->json(), $provider);

        if ($answer === '') {
            $answer = 'The assistant responded without usable text. Please try a more specific question.';
        }

        if (Schema::hasTable('notification_logs')) {
            NotificationLog::create([
                'channel' => 'chatbot',
                'category' => $category,
                'status' => 'sent',
                'recipient' => $user->email ?: $user->username ?: $user->name,
                'subject' => $subject,
                'provider' => $provider,
                'message' => $question,
                'triggered_by_user_id' => $user->getKey(),
                'context' => [
                    'model' => $model,
                    'answer' => $answer,
                ],
            ]);
        }

        return [
            'status' => 'sent',
            'answer' => $answer,
        ];
    }

    private function sendViaOpenRouter(string $instructions, string $question, array $inputContext)
    {
        return Http::withToken($this->openRouterApiKey())
            ->acceptJson()
            ->timeout(30)
            ->withHeaders([
                'HTTP-Referer' => $this->openRouterSiteUrl(),
                'X-OpenRouter-Title' => $this->openRouterAppName(),
            ])
            ->post($this->openRouterEndpoint(), [
                'model' => $this->openRouterModel(),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $instructions,
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->assistantInput($question, $inputContext),
                    ],
                ],
            ]);
    }

    private function sendViaOllama(string $instructions, string $question, array $inputContext)
    {
        return Http::acceptJson()
            ->timeout(60)
            ->post(rtrim($this->ollamaBaseUrl(), '/') . '/api/chat', [
                'model' => $this->ollamaModel(),
                'stream' => false,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $instructions,
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->assistantInput($question, $inputContext),
                    ],
                ],
            ]);
    }

    private function extractAnswer(array $payload, string $provider): string
    {
        return match ($provider) {
            'ollama' => (string) data_get($payload, 'message.content', ''),
            default => (string) data_get($payload, 'choices.0.message.content', ''),
        };
    }

    private function assistantInput(string $question, array $inputContext): string
    {
        return "ViLoCare context:\n" . json_encode($inputContext, JSON_PRETTY_PRINT) . "\n\nUser question: {$question}";
    }

    private function provider(): string
    {
        return strtolower((string) env('VILOCARE_AI_PROVIDER', 'ollama'));
    }

    private function activeModel(): string
    {
        return match ($this->provider()) {
            'ollama' => $this->ollamaModel(),
            default => $this->openRouterModel(),
        };
    }

    private function openRouterApiKey(): string
    {
        return (string) config('services.openrouter.api_key');
    }

    private function openRouterModel(): string
    {
        return (string) config('services.openrouter.model');
    }

    private function openRouterEndpoint(): string
    {
        return (string) config('services.openrouter.endpoint');
    }

    private function openRouterSiteUrl(): string
    {
        return (string) config('services.openrouter.site_url');
    }

    private function openRouterAppName(): string
    {
        return (string) config('services.openrouter.app_name');
    }

    private function ollamaBaseUrl(): string
    {
        return (string) config('services.ollama.base_url');
    }

    private function ollamaModel(): string
    {
        return (string) config('services.ollama.model');
    }
}
