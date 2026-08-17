<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MastercardService
{
    public function isConfigured(): bool
    {
        return filled(config('services.mastercard.base_url'))
            && filled(config('services.mastercard.merchant_id'))
            && filled(config('services.mastercard.api_password'));
    }

    public function initiateCheckout(Payment $payment, string $returnUrl): array
    {
        $response = $this->request()
            ->post($this->sessionPath(), [
                'apiOperation' => 'INITIATE_CHECKOUT',
                'checkoutMode' => 'WEBSITE',
                'interaction' => [
                    'operation' => 'PURCHASE',
                    'merchant' => [
                        'name' => (string) config('services.mastercard.merchant_name', 'ViLoCare'),
                        'url' => (string) config('services.mastercard.merchant_url', config('app.url')),
                    ],
                    'returnUrl' => $returnUrl,
                ],
                'order' => [
                    'id' => $payment->receipt_number,
                    'currency' => strtoupper((string) $payment->currency),
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                    'description' => $payment->service_label,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Unable to initiate the Mastercard checkout session.'));
        }

        $data = $response->json();
        $sessionId = (string) data_get($data, 'session.id', '');
        $successIndicator = (string) data_get($data, 'successIndicator', '');

        if ($sessionId === '' || $successIndicator === '') {
            throw new RuntimeException('Mastercard did not return the hosted checkout session details.');
        }

        return [
            'session_id' => $sessionId,
            'success_indicator' => $successIndicator,
            'session_version' => (string) data_get($data, 'session.version', ''),
            'payload' => $data,
            'checkout_js_url' => $this->checkoutJsUrl(),
        ];
    }

    public function checkoutJsUrl(): string
    {
        $baseUrl = rtrim((string) config('services.mastercard.base_url'), '/');
        $version = (string) config('services.mastercard.api_version', '100');

        return $baseUrl . '/checkout/version/' . $version . '/checkout.js';
    }

    private function request(): PendingRequest
    {
        $merchantId = (string) config('services.mastercard.merchant_id');

        return Http::baseUrl(rtrim((string) config('services.mastercard.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withBasicAuth('merchant.' . $merchantId, (string) config('services.mastercard.api_password'))
            ->timeout((int) config('services.mastercard.timeout', 30));
    }

    private function sessionPath(): string
    {
        return '/api/rest/version/' . config('services.mastercard.api_version', '100')
            . '/merchant/' . config('services.mastercard.merchant_id')
            . '/session';
    }

    private function errorMessage(mixed $payload, string $fallback): string
    {
        $message = data_get($payload, 'error.explanation')
            ?? data_get($payload, 'response.gatewayRecommendation')
            ?? data_get($payload, 'result')
            ?? data_get($payload, 'message');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }
}
