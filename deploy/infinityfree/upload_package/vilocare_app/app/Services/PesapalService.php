<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PesapalService
{
    public function isConfigured(): bool
    {
        return filled(config('services.pesapal.base_url'))
            && filled(config('services.pesapal.consumer_key'))
            && filled(config('services.pesapal.consumer_secret'));
    }

    public function createOrder(Payment $payment, array $customer, string $callbackUrl, string $ipnUrl): array
    {
        $notificationId = (string) config('services.pesapal.ipn_notification_id');

        if ($notificationId === '') {
            $notificationId = $this->registerIpnUrl($ipnUrl);
        }

        $payload = [
            'id' => $payment->receipt_number,
            'currency' => strtoupper((string) $payment->currency),
            'amount' => (float) $payment->amount,
            'description' => $payment->service_label,
            'callback_url' => $callbackUrl,
            'notification_id' => $notificationId,
            'billing_address' => [
                'email_address' => $customer['email_address'] ?? '',
                'phone_number' => $customer['phone_number'] ?? '',
                'country_code' => $customer['country_code'] ?? 'SS',
                'first_name' => $customer['first_name'] ?? '',
                'middle_name' => '',
                'last_name' => $customer['last_name'] ?? '',
                'line_1' => 'ViLoCare',
                'line_2' => '',
                'city' => '',
                'state' => '',
                'postal_code' => '',
                'zip_code' => '',
            ],
        ];

        $response = $this->request()
            ->withToken($this->accessToken())
            ->post('/Transactions/SubmitOrderRequest', $payload);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Unable to create the Pesapal payment request.'));
        }

        $data = $response->json();

        return [
            'order_tracking_id' => (string) data_get($data, 'order_tracking_id'),
            'merchant_reference' => (string) data_get($data, 'merchant_reference', $payment->receipt_number),
            'redirect_url' => (string) data_get($data, 'redirect_url'),
            'notification_id' => $notificationId,
            'payload' => $data,
        ];
    }

    public function getTransactionStatus(string $orderTrackingId): array
    {
        $response = $this->request()
            ->withToken($this->accessToken())
            ->get('/Transactions/GetTransactionStatus', [
                'orderTrackingId' => $orderTrackingId,
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Unable to fetch the Pesapal transaction status.'));
        }

        $payload = $response->json();

        return [
            'reference_id' => $orderTrackingId,
            'status' => $this->mapStatus((string) data_get($payload, 'payment_status_description', 'INVALID')),
            'raw_status' => data_get($payload, 'payment_status_description'),
            'payment_method' => data_get($payload, 'payment_method'),
            'confirmation_code' => data_get($payload, 'confirmation_code'),
            'description' => data_get($payload, 'description'),
            'payload' => $payload,
        ];
    }

    private function registerIpnUrl(string $ipnUrl): string
    {
        $response = $this->request()
            ->withToken($this->accessToken())
            ->post('/URLSetup/RegisterIPN', [
                'url' => $ipnUrl,
                'ipn_notification_type' => 'GET',
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Unable to register the Pesapal IPN URL.'));
        }

        $notificationId = (string) data_get($response->json(), 'ipn_id', '');

        if ($notificationId === '') {
            throw new RuntimeException('Pesapal did not return an IPN notification ID.');
        }

        return $notificationId;
    }

    private function accessToken(): string
    {
        $response = $this->request()
            ->post('/Auth/RequestToken', [
                'consumer_key' => (string) config('services.pesapal.consumer_key'),
                'consumer_secret' => (string) config('services.pesapal.consumer_secret'),
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Unable to authenticate with Pesapal.'));
        }

        $token = (string) data_get($response->json(), 'token', '');

        if ($token === '') {
            throw new RuntimeException('Pesapal access token was not returned by the gateway.');
        }

        return $token;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.pesapal.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pesapal.timeout', 30));
    }

    private function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'COMPLETED' => 'paid',
            'FAILED', 'INVALID', 'REVERSED' => 'failed',
            default => 'pending',
        };
    }

    private function errorMessage(mixed $payload, string $fallback): string
    {
        $message = data_get($payload, 'error.message')
            ?? data_get($payload, 'message')
            ?? data_get($payload, 'error')
            ?? data_get($payload, 'error_type');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }
}
