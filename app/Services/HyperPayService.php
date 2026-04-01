<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class HyperPayService
{
    public function createCheckout(array $payload): array
    {
        $baseUrl = rtrim((string) GetpaymentMethod('hyperpay_base_url'), '/');
        $entityId = (string) GetpaymentMethod('hyperpay_entity_id');
        $token = (string) GetpaymentMethod('hyperpay_access_token');

        $response = Http::asForm()
            ->withToken($token)
            ->post($baseUrl . '/v1/checkouts', array_merge([
                'entityId' => $entityId,
            ], $payload));

        if (! $response->successful()) {
            throw new RequestException($response);
        }

        return $response->json();
    }

    public function fetchPaymentStatusByResourcePath(string $resourcePath): array
    {
        $baseUrl = rtrim((string) GetpaymentMethod('hyperpay_base_url'), '/');
        $entityId = (string) GetpaymentMethod('hyperpay_entity_id');
        $token = (string) GetpaymentMethod('hyperpay_access_token');

        $path = '/' . ltrim($resourcePath, '/');
        $url = $baseUrl . $path;

        $response = Http::withToken($token)
            ->get($url, [
                'entityId' => $entityId,
            ]);

        if (! $response->successful()) {
            throw new RequestException($response);
        }

        return $response->json();
    }

    public function isPaid(array $statusPayload): bool
    {
        // HyperPay uses result.code. Successful payments typically start with "000.000." or "000.100.1".
        $code = data_get($statusPayload, 'result.code');
        if (! is_string($code)) {
            return false;
        }

        return str_starts_with($code, '000.000.') || str_starts_with($code, '000.100.1');
    }
}

