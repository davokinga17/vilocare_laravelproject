<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MtnMomoService
{
    public function requestCurrency(?string $requestedCurrency = null): string
    {
        if ($this->isSandbox()) {
            return 'EUR';
        }

        return strtoupper((string) ($requestedCurrency ?: 'EUR'));
    }

    public function isConfigured(): bool
    {
        return filled(config('services.mtn_momo.base_url'))
            && filled(config('services.mtn_momo.subscription_key'))
            && filled(config('services.mtn_momo.api_user'))
            && filled(config('services.mtn_momo.api_key'))
            && filled(config('services.mtn_momo.target_environment'));
    }

    public function requestPayment(Payment $payment, string $phoneNumber, ?string $callbackUrl = null): array
    {
        $referenceId = (string) Str::uuid();
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $currency = $this->requestCurrency((string) $payment->currency);

        $headers = [
            'X-Reference-Id' => $referenceId,
            'X-Target-Environment' => config('services.mtn_momo.target_environment'),
        ];

        $resolvedCallbackUrl = $this->resolveCallbackUrl($callbackUrl ?: config('services.mtn_momo.callback_url'));

        if (filled($resolvedCallbackUrl)) {
            $headers['X-Callback-Url'] = $resolvedCallbackUrl;
        }

        $response = $this->collectionRequest()
            ->withToken($this->accessToken())
            ->withHeaders($headers)
            ->post('/collection/v1_0/requesttopay', array_filter([
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
                'currency' => $currency,
                'externalId' => $payment->receipt_number,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $normalizedPhone,
                ],
                'payerMessage' => $payment->service_label,
                'payeeNote' => 'ViLoCare payment ' . $payment->receipt_number,
            ]));

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessageFromResponse($response->json(), 'MTN MoMo request failed.'));
        }

        return [
            'reference_id' => $referenceId,
            'phone_number' => $normalizedPhone,
            'status' => 'pending',
            'callback_url' => $resolvedCallbackUrl,
            'request_payload' => [
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
                'currency' => $currency,
                'external_id' => $payment->receipt_number,
            ],
        ];
    }

    public function getPaymentStatus(string $referenceId): array
    {
        $response = $this->collectionRequest()
            ->withToken($this->accessToken())
            ->withHeaders([
                'X-Target-Environment' => config('services.mtn_momo.target_environment'),
            ])
            ->get('/collection/v1_0/requesttopay/' . $referenceId);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessageFromResponse($response->json(), 'Unable to fetch MTN MoMo payment status.'));
        }

        $payload = $response->json();

        return [
            'reference_id' => $referenceId,
            'status' => $this->mapMomoStatus((string) data_get($payload, 'status', 'PENDING')),
            'financial_transaction_id' => data_get($payload, 'financialTransactionId'),
            'raw_status' => data_get($payload, 'status'),
            'reason' => data_get($payload, 'reason'),
            'payload' => $payload,
        ];
    }

    public function normalizePhoneNumber(string $phoneNumber): string
    {
        $normalized = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if ($normalized === '') {
            throw new RuntimeException('Enter a valid MTN Mobile Money phone number with country code.');
        }

        return $normalized;
    }

    private function collectionRequest(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.mtn_momo.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => (string) config('services.mtn_momo.subscription_key'),
            ])
            ->timeout((int) config('services.mtn_momo.timeout', 30));
    }

    private function accessToken(): string
    {
        $credentials = base64_encode(
            (string) config('services.mtn_momo.api_user') . ':' . (string) config('services.mtn_momo.api_key')
        );

        $response = $this->collectionRequest()
            ->withHeaders([
                'Authorization' => 'Basic ' . $credentials,
            ])
            ->post('/collection/token/');

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessageFromResponse($response->json(), 'Unable to authenticate with MTN MoMo.'));
        }

        $accessToken = (string) data_get($response->json(), 'access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('MTN MoMo access token was not returned by the gateway.');
        }

        return $accessToken;
    }

    private function isSandbox(): bool
    {
        return strtolower((string) config('services.mtn_momo.target_environment')) === 'sandbox';
    }

    private function resolveCallbackUrl(?string $callbackUrl): ?string
    {
        $candidate = is_string($callbackUrl) ? trim($callbackUrl) : '';

        if ($candidate === '') {
            return null;
        }

        $scheme = parse_url($candidate, PHP_URL_SCHEME);
        $host = parse_url($candidate, PHP_URL_HOST);

        if ($scheme !== 'https') {
            return null;
        }

        if (! is_string($host) || $host === '' || in_array(strtolower($host), ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        return $candidate;
    }

    private function mapMomoStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'SUCCESSFUL' => 'paid',
            'FAILED', 'REJECTED', 'TIMEOUT', 'EXPIRED' => 'failed',
            default => 'pending',
        };
    }

    private function errorMessageFromResponse(mixed $payload, string $fallback): string
    {
        $message = data_get($payload, 'message')
            ?? data_get($payload, 'reason')
            ?? data_get($payload, 'details')
            ?? data_get($payload, 'error');

        if ($message === 'invalid_client') {
            return 'MTN sandbox rejected the API user/API key combination (invalid_client). Regenerate the Collection sandbox API user and API key from MTN MoMo Developer, confirm the Collection subscription primary key is used, then update the environment values.';
        }

        return is_string($message) && $message !== '' ? $message : $fallback;
    }
}
