<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Services\HyperPayService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Throwable;

class HyperPayController extends Controller
{
    public function checkout(Request $request)
    {
        if ((int) setting('hyperpay_payment_method') !== 1) {
            return response()->json(['status' => false, 'message' => 'HyperPay is disabled.'], 400);
        }

        $amount = $request->input('amount');
        if (!is_numeric($amount) || (float) $amount <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid amount.'], 422);
        }

        $currency = GetpaymentMethod('hyperpay_currency') ?: GetcurrentCurrency();
        $paymentType = GetpaymentMethod('hyperpay_payment_type') ?: 'DB';

        $purpose = $request->input('purpose', 'ppv'); // ppv | subscription
        $merchantTransactionId = $purpose . '_' . ($request->input('item_id') ?? '0') . '_' . time();

        $service = app(HyperPayService::class);
        try {
            $checkout = $service->createCheckout([
                'amount' => number_format((float) $amount, 2, '.', ''),
                'currency' => $currency,
                'paymentType' => $paymentType,
                'merchantTransactionId' => $merchantTransactionId,
            ]);
        } catch (RequestException $e) {
            $response = $e->response;
            $message =
                data_get($response?->json(), 'result.description')
                ?? data_get($response?->json(), 'message')
                ?? ($response ? trim((string) $response->body()) : null)
                ?? 'HyperPay checkout request failed.';

            return response()->json(['status' => false, 'message' => $message], 502);
        } catch (ConnectionException $e) {
            return response()->json(['status' => false, 'message' => 'Unable to reach HyperPay. Please try again.'], 502);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => 'HyperPay checkout failed.'], 500);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'checkout_id' => $checkout['id'] ?? null,
                'currency' => $currency,
                'payment_type' => $paymentType,
                'base_url' => rtrim((string) GetpaymentMethod('hyperpay_base_url'), '/'),
                'brands' => GetpaymentMethod('hyperpay_brands') ?: 'VISA MASTER',
            ],
        ]);
    }

    public function status(Request $request)
    {
        if ((int) setting('hyperpay_payment_method') !== 1) {
            return response()->json(['status' => false, 'message' => 'HyperPay is disabled.'], 400);
        }

        $resourcePath = $request->query('resource_path') ?? $request->query('resourcePath');
        if (! $resourcePath) {
            return response()->json(['status' => false, 'message' => 'Missing resource_path.'], 422);
        }

        $service = app(HyperPayService::class);
        try {
            $payload = $service->fetchPaymentStatusByResourcePath($resourcePath);
        } catch (RequestException $e) {
            $response = $e->response;
            $message =
                data_get($response?->json(), 'result.description')
                ?? data_get($response?->json(), 'message')
                ?? ($response ? trim((string) $response->body()) : null)
                ?? 'HyperPay status request failed.';

            return response()->json(['status' => false, 'message' => $message], 502);
        } catch (ConnectionException $e) {
            return response()->json(['status' => false, 'message' => 'Unable to reach HyperPay. Please try again.'], 502);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => 'HyperPay status lookup failed.'], 500);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'paid' => $service->isPaid($payload),
                'transaction_id' => data_get($payload, 'id'),
                'result_code' => data_get($payload, 'result.code'),
                'result_description' => data_get($payload, 'result.description'),
            ],
        ]);
    }
}

