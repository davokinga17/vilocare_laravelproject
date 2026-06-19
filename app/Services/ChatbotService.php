<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ChatbotService
{
    public function askDashboardAssistant(string $question, array $summary, User $user): array
    {
        $apiKey = (string) config('services.openai.api_key');
        $endpoint = (string) config('services.openai.endpoint');
        $model = (string) config('services.openai.model');

        if ($apiKey === '') {
            return [
                'status' => 'skipped',
                'answer' => 'OpenAI is not configured yet. Add OPENAI_API_KEY and OPENAI_MODEL to enable the dashboard assistant.',
            ];
        }

        $instructions = 'You are the ViLoCare dashboard assistant. Only help with dashboard metrics, report options, workflow explanations, and administrative summaries. Never provide diagnosis, treatment advice, or patient-specific clinical recommendations. If asked for unsafe or unsupported guidance, say so clearly and redirect to a clinician or administrator.';

        $context = [
            'user_role' => $user->role,
            'period' => $summary['period'] ?? 'All time',
            'totals' => $summary['totals'] ?? [],
            'reports' => $summary['reports'] ?? [],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->post($endpoint, [
                'model' => $model,
                'instructions' => $instructions,
                'input' => "Dashboard summary:\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n\nUser question: {$question}",
            ]);

        if (! $response->successful()) {
            $errorCode = (string) data_get($response->json(), 'error.code', '');
            $errorMessage = (string) data_get($response->json(), 'error.message', '');

            $friendlyMessage = match ($errorCode) {
                'insufficient_quota' => 'The OpenAI account has no available quota right now. Please add billing or increase the project quota, then try again.',
                default => 'The assistant could not respond right now. Please verify the OpenAI credentials and try again.',
            };

            return [
                'status' => 'failed',
                'answer' => $friendlyMessage,
                'detail' => $errorMessage !== '' ? $errorMessage : $response->body(),
            ];
        }

        $payload = $response->json();
        $answer = (string) ($payload['output_text'] ?? data_get($payload, 'output.0.content.0.text', ''));

        if ($answer === '') {
            $answer = 'The assistant responded without usable text. Please try a more specific dashboard question.';
        }

        if (Schema::hasTable('notification_logs')) {
            NotificationLog::create([
                'channel' => 'chatbot',
                'category' => 'dashboard_assistant',
                'status' => 'sent',
                'recipient' => $user->email ?: $user->username ?: $user->name,
                'subject' => 'Dashboard assistant request',
                'provider' => 'openai',
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
}
