<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function send(string $to, string $message): array
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.from');
        $messagingServiceSid = (string) config('services.twilio.messaging_service_sid');

        if ($sid === '' || $token === '' || ($from === '' && $messagingServiceSid === '')) {
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
            'detail' => (string) data_get($response->json(), 'message', $response->body()),
        ];
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?: trim($phone);
    }
}
