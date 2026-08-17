<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SmsService
{
    public function send(string $to, string $message): array
    {
        return match ($this->driver()) {
            'twilio' => $this->sendViaTwilio($to, $message),
            'africastalking' => $this->sendViaAfricasTalking($to, $message),
            default => $this->sendToLog($to, $message),
        };
    }

    public function isConfigured(): bool
    {
        return match ($this->driver()) {
            'twilio' => $this->twilioIsConfigured(),
            'africastalking' => $this->africasTalkingIsConfigured(),
            default => true,
        };
    }

    private function sendViaTwilio(string $to, string $message): array
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.from');
        $messagingServiceSid = (string) config('services.twilio.messaging_service_sid');

        if (! $this->twilioIsConfigured()) {
            return [
                'status' => 'skipped',
                'provider' => 'twilio',
                'detail' => 'SMS provider is not configured.',
            ];
        }

        $payload = [
            'To' => $this->normalizePhone($to),
            'Body' => $message,
        ];

        if ($messagingServiceSid !== '') {
            $payload['MessagingServiceSid'] = $messagingServiceSid;
        } else {
            $payload['From'] = $from;
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $payload);

        if ($response->successful()) {
            return [
                'status' => 'sent',
                'provider' => 'twilio',
                'detail' => (string) data_get($response->json(), 'sid', 'Sent'),
            ];
        }

        return [
            'status' => 'failed',
            'provider' => 'twilio',
            'detail' => trim((string) (data_get($response->json(), 'message')
                ?: data_get($response->json(), 'detail')
                ?: $response->body())),
        ];
    }

    private function sendViaAfricasTalking(string $to, string $message): array
    {
        $username = (string) config('services.africastalking.username');
        $apiKey = (string) config('services.africastalking.api_key');
        $from = (string) config('services.africastalking.from');
        $isSandbox = (bool) config('services.africastalking.sandbox', false);
        $baseUrl = (string) ($isSandbox
            ? config('services.africastalking.sandbox_base_url')
            : config('services.africastalking.base_url'));

        if (! $this->africasTalkingIsConfigured()) {
            return [
                'status' => 'skipped',
                'provider' => 'africastalking',
                'detail' => 'Africa\'s Talking is not configured.',
            ];
        }

        $payload = [
            'username' => $username,
            'to' => $this->normalizePhone($to),
            'message' => $message,
        ];

        if ($from !== '') {
            $payload['from'] = $from;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->withHeaders([
                'apiKey' => $apiKey,
            ])
            ->post(rtrim($baseUrl, '/') . '/version1/messaging', $payload);

        if ($response->successful()) {
            $recipient = data_get($response->json(), 'SMSMessageData.Recipients.0');

            return [
                'status' => 'sent',
                'provider' => 'africastalking',
                'detail' => (string) (data_get($recipient, 'messageId')
                    ?: data_get($recipient, 'status')
                    ?: data_get($response->json(), 'SMSMessageData.Message')),
            ];
        }

        return [
            'status' => 'failed',
            'provider' => 'africastalking',
            'detail' => (string) (data_get($response->json(), 'SMSMessageData.Message')
                ?: data_get($response->json(), 'errorMessage')
                ?: $response->body()),
        ];
    }

    private function sendToLog(string $to, string $message): array
    {
        return [
            'status' => 'sent',
            'provider' => 'log',
            'detail' => 'LOG-' . Str::upper(Str::random(12)),
        ];
    }

    private function driver(): string
    {
        $configuredDriver = Str::lower(trim((string) config('vilocare.sms.driver', 'auto')));

        if ($configuredDriver !== '' && $configuredDriver !== 'auto') {
            return $configuredDriver;
        }

        if ($this->twilioIsConfigured()) {
            return 'twilio';
        }

        if ($this->africasTalkingIsConfigured()) {
            return 'africastalking';
        }

        return 'log';
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?: trim($phone);
    }

    private function twilioIsConfigured(): bool
    {
        return trim((string) config('services.twilio.sid')) !== ''
            && trim((string) config('services.twilio.token')) !== ''
            && (
                trim((string) config('services.twilio.from')) !== ''
                || trim((string) config('services.twilio.messaging_service_sid')) !== ''
            );
    }

    private function africasTalkingIsConfigured(): bool
    {
        return trim((string) config('services.africastalking.username')) !== ''
            && trim((string) config('services.africastalking.api_key')) !== '';
    }
}
