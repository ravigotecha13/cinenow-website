<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\Frontend\Models\PayPerView;
use Modules\Season\Models\Season;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Auth;
use Modules\Frontend\Models\PayperviewTransaction;
use Modules\Video\Models\Video;
use Modules\Entertainment\Transformers\MoviesResource;
use Modules\Entertainment\Transformers\TvshowResource;
use Modules\Video\Transformers\VideoResource;
use Modules\Entertainment\Transformers\SeasonResource;
use Modules\Entertainment\Transformers\EpisodeResource;
use Carbon\Carbon;
use Modules\Entertainment\Transformers\MoviesResourceV2;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Entertainment\Models\Watchlist;
use App\Models\User;
use App\Services\HyperPayService;


class PerviewPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('frontend::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function PayPerViewForm(Request $request)
    {

        if (!Auth::check()) {
            return redirect()->route('login');
        }
        if ($request->type == 'video') {
            $data = Video::findOrFail($request->id);
            $data->type = 'video';
        } else if ($request->type == 'episode') {
            $data = Episode::findOrFail($request->id);
            $data->type = 'episode';
        } else if ($request->type == 'season') {
            $data = Season::findOrFail($request->id);
            $data->type = 'season';
        } else {
            $data = Entertainment::findOrFail($request->id);
        }

        return view('frontend::perviewpayment', compact('data'));
    }

    public function processPayment(Request $request)
    {
        $paymentMethod = $request->input('payment_method');
        $price = $request->input('price');
        // dd($price);
        $paymentHandlers = [
            'stripe' => 'StripePayment',
            'razorpay' => 'RazorpayPayment',
            'paystack' => 'PaystackPayment',
            'paypal' => 'PayPalPayment',
            'flutterwave' => 'FlutterwavePayment',
            'cinet' => 'CinetPayment',
            'sadad' => 'SadadPayment',
            'airtel' => 'AirtelPayment',
            'phonepe' => 'PhonePePayment',
            'midtrans' => 'MidtransPayment',
            'hyperpay' => 'HyperPayPayment',
        ];

        if (array_key_exists($paymentMethod, $paymentHandlers)) {
            return $this->{$paymentHandlers[$paymentMethod]}($request, $price);
        }

        return redirect()->back()->withErrors('Invalid payment method.');
    }

    protected function HyperPayPayment(Request $request, $price = null)
    {
        if ((int) setting('hyperpay_payment_method') !== 1) {
            return redirect()->back()->withErrors('HyperPay is disabled.');
        }

        $amount = $request->input('price');
        $currency = GetpaymentMethod('hyperpay_currency') ?: GetcurrentCurrency();
        $paymentType = GetpaymentMethod('hyperpay_payment_type') ?: 'DB';
        $brands = GetpaymentMethod('hyperpay_brands') ?: 'VISA MASTER';

        $service = app(HyperPayService::class);
        $checkout = $service->createCheckout([
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency' => $currency,
            'paymentType' => $paymentType,
            'merchantTransactionId' => 'ppv_' . ($request->input('movie_id') ?? '0') . '_' . time(),
        ]);

        $checkoutId = $checkout['id'] ?? null;
        if (! $checkoutId) {
            return redirect()->back()->withErrors('HyperPay checkout failed.');
        }

        return view('frontend::payments.hyperpay_checkout', [
            'purpose' => 'ppv',
            'checkoutId' => $checkoutId,
            'baseUrl' => rtrim((string) GetpaymentMethod('hyperpay_base_url'), '/'),
            'brands' => $brands,
            'resultRouteName' => 'payperview.payment.success',
            'amount' => $amount,
            'currency' => $currency,
            'movie_id' => $request->input('movie_id'),
            'type' => $request->input('type'),
            'discount' => $request->input('discount'),
            'access_duration' => $request->input('access_duration'),
            'available_for' => $request->input('available_for'),
        ]);
    }

    protected function StripePayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $stripe_secret_key = GetpaymentMethod('stripe_secretkey');
        $currency = GetcurrentCurrency();

        $stripe = new \Stripe\StripeClient($stripe_secret_key);
        $price = $request->input('price');

        $currenciesWithoutCents = ['XAF', 'XOF', 'JPY', 'KRW'];
        $priceInCents = in_array(strtoupper($currency), $currenciesWithoutCents) ? $price : (int)round($price * 100);

        try {
            $checkout_session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Pay Per View',
                        ],
                        'unit_amount' => $priceInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'metadata' => [
                    'movie_id' => $request->input('movie_id'),
                    'type' => $request->input('type'),
                    'access_duration' => $request->input('access_duration'),
                    'available_for' => $request->input('available_for'),
                    'discount' => $request->input('discount'),
                ],
                'success_url' => $baseURL . '/payment/success/pay-per-view?gateway=stripe&session_id={CHECKOUT_SESSION_ID}'
            ]);

            return response()->json(['redirect' => $checkout_session->url]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, "must convert to at least") !== false) {
                $errorMessage = "The amount entered is too low to process a payment. Please increase the amount and try again.";
            }
            return response()->json(['error' => $errorMessage], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong. Please try again later.'], 500);
        }
    }

    protected function PaystackPayment(Request $request)
    {
        try {
            $baseURL = env('APP_URL');
            $paystackSecretKey = GetpaymentMethod('paystack_secretkey');
            $price = $request->input('price');
            $priceInKobo = $price * 100;

            $callbackUrl = $baseURL . '/payment/success/pay-per-view?' . http_build_query([
                'gateway' => 'paystack',
                'movie_id' => $request->input('movie_id'),
                'type' => $request->input('type'),
                'access_duration' => $request->input('access_duration'),
                'available_for' => $request->input('available_for'),
                'discount' => $request->input('discount'),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => auth()->user()->email,
                'amount' => $priceInKobo,
                'currency' => 'NGN',
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'movie_id' => $request->input('movie_id'),
                    'type' => $request->input('type'),
                    'access_duration' => $request->input('access_duration'),
                    'available_for' => $request->input('available_for'),
                    'discount' => $request->input('discount'),
                ],
            ]);

            $responseBody = $response->json();

            if ($responseBody['status']) {
                return response()->json([
                    'success' => true,
                    'authorization_url' => $responseBody['data']['authorization_url']
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => __('messages.something_wrong_choose_another')
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    protected function RazorpayPayment(Request $request, $price)
    {
        $baseURL = env('APP_URL');
        $razorpayKey = GetpaymentMethod('razorpay_publickey');
        $razorpaySecret = GetpaymentMethod('razorpay_secretkey');
        $plan_id = $request->input('plan_id');
        $currency = GetcurrentCurrency();
        $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'SGD', 'AED'];
        $formattedCurrency = strtoupper($currency);

        try {
            if (!in_array($formattedCurrency, $supportedCurrencies)) {
                $formattedCurrency = 'INR';
                $price = $price;
            }
            $amount = $price * 100;

            return response()->json([
                'key' => $razorpayKey,
                'amount' => $amount,
                'currency' => $formattedCurrency,
                'name' => config('app.name'),
                'description' => 'Pay Per View Payment',
                'plan_id' => $plan_id,
                'order_id' => null,
                'success_url' => route('payperview.payment.success', [
                    'movie_id' => $request->movie_id,
                    'type' => $request->type,
                    'access_duration' => $request->access_duration,
                    'available_for' => $request->available_for,
                    'discount' => $request->discount,
                    'plan_id' => $request->plan_id,
                ]),
                'prefill' => [
                    'name' => auth()->user()->name ?? '',
                    'email' => auth()->user()->email ?? '',
                    'contact' => auth()->user()->phone ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Currency not supported. Please try with INR or contact support.',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    protected function FlutterwavePayment(Request $request)
    {
        try {
            $baseURL = env('APP_URL');
            $flutterwavePublicKey = GetpaymentMethod('flutterwave_publickey');
            $price = $request->input('price');

            $txRef = 'PPV_' . uniqid() . '_' . time();

            $callbackUrl = $baseURL . '/payment/success/pay-per-view?' . http_build_query([
                'gateway' => 'flutterwave',
                'movie_id' => $request->input('movie_id'),
                'type' => $request->input('type'),
                'access_duration' => $request->input('access_duration'),
                'available_for' => $request->input('available_for'),
                'discount' => $request->input('discount'),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'public_key' => $flutterwavePublicKey,
                    'tx_ref' => $txRef,
                    'amount' => $price,
                    'currency' => 'NGN',
                    'payment_options' => 'card,banktransfer',
                    'customer' => [
                        'email' => auth()->user()->email,
                        'name' => auth()->user()->name,
                        'phonenumber' => auth()->user()->phone ?? ''
                    ],
                    'customizations' => [
                        'title' => config('app.name') . ' - Pay Per View',
                        'description' => 'Payment for Pay Per View content',
                        'logo' => asset('images/logo.png')
                    ],
                    'redirect_url' => $callbackUrl
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function PayPalPayment(Request $request)
    {
        try {
            $baseURL = env('APP_URL');
            $paypalClientId = GetpaymentMethod('paypal_clientid');

            if (!$paypalClientId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PayPal client ID is not configured'
                ], 400);
            }

            $price = $request->input('price');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'client_id' => $paypalClientId,
                    'currency' => 'USD',
                    'amount' => $price,
                    'return_url' => $baseURL . '/payment/success/pay-per-view?' . http_build_query([
                        'gateway' => 'paypal',
                        'movie_id' => $request->input('movie_id'),
                        'type' => $request->input('type'),
                        'access_duration' => $request->input('access_duration'),
                        'discount' => $request->input('discount'),
                    ])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $gateway = $request->input('gateway');

        switch ($gateway) {
            case 'stripe':
                return $this->handleStripeSuccess($request);
            case 'razorpay':
                return $this->handleRazorpaySuccess($request);
            case 'paystack':
                return $this->handlePaystackSuccess($request);
            case 'paypal':
                return $this->handlePayPalSuccess($request);
            case 'flutterwave':
                return $this->handleFlutterwaveSuccess($request);
            case 'cinet':
                return $this->handleCinetSuccess($request);
            case 'sadad':
                return $this->handleSadadSuccess($request);
            case 'airtel':
                return $this->handleAirtelSuccess($request);
            case 'phonepe':
                return $this->handlePhonePeSuccess($request);
            case 'midtrans':
                return $this->MidtransPayment($request);
            case 'hyperpay':
                return $this->handleHyperPaySuccess($request);
            default:
                return redirect('/')->with('error', 'Invalid payment gateway.');
        }
    }

    protected function handleHyperPaySuccess(Request $request)
    {
        try {
            $resourcePath = $request->query('resourcePath') ?? $request->input('resourcePath');
            if (! $resourcePath) {
                return redirect('/')->with('error', 'HyperPay: missing resourcePath.');
            }

            $service = app(HyperPayService::class);
            $status = $service->fetchPaymentStatusByResourcePath($resourcePath);

            if (! $service->isPaid($status)) {
                $code = data_get($status, 'result.code');
                $desc = data_get($status, 'result.description');
                return redirect('/')->with('error', 'HyperPay payment not successful. ' . ($code ? "{$code} " : '') . ($desc ?? ''));
            }

            $transactionId = data_get($status, 'id') ?? ($request->input('checkout_id') ?? uniqid('hyperpay_', true));

            $this->handlePaymentSuccess(
                $request->input('amount'),
                'hyperpay',
                $transactionId,
                $request->input('movie_id'),
                $request->input('type'),
                $request->input('access_duration'),
                $request->input('available_for'),
                $request->input('discount')
            );

            return redirect()->route('unlock.videos')->with('purchase_success', true);
        } catch (\Throwable $e) {
            return redirect('/')->with('error', 'HyperPay error: ' . $e->getMessage());
        }
    }

    protected function handlePaymentSuccess($amount, $payment_type, $transaction_id, $movie_id = null, $type = null, $access_duration = null, $available_for = null, $discount = null)
    {
        $user = Auth::user();

        if ($type == 'movie') {
            $movie = Entertainment::find($movie_id);
        } else if ($type == 'tvshow') {
            $movie = Entertainment::find($movie_id);
        } else if ($type == 'video') {
            $movie = Video::find($movie_id);
        } else if ($type == 'episode') {
            $movie = Episode::find($movie_id);
        } else if ($type == 'season') {
            $movie = season::find($movie_id);
        }


        $viewExpiry = now()->addDays((int)$available_for ?? 48); // default to 48 hours if not provide
        $payperview = PayPerView::create([
            'user_id' => $user->id,
            'movie_id' => $movie_id,
            'type' => $type,
            'content_price' => $movie->price,
            'price' => $amount,
            'discount_percentage' => $discount,
            'view_expiry_date' => $viewExpiry,
            'access_duration' => $access_duration,
            'available_for' => $available_for,
        ]);

        // Expire previous active ticket if exists to avoid unique constraint violation
        \DB::table('ppv_tickets')
            ->where('user_id', $user->id)
            ->where('entertainment_id', $movie_id)
            ->where('status', 'active')
            ->update(['status' => 'expired', 'updated_at' => now()]);

        // Create PPV Ticket
        \DB::table('ppv_tickets')->insert([
            'user_id'            => $user->id,
            'entertainment_id'   => $movie_id,
            'entertainment_type' => $type,
            'status'             => 'active',
            'purchased_at'       => now(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Reset Continue Watch history
        \Modules\Entertainment\Models\ContinueWatch::where('user_id', $user->id)
            ->where('entertainment_id', $movie_id)
            ->where('entertainment_type', $type)
            ->delete();

        if ($type == 'movie') {
            \DB::table('movie_user_trackings')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'entertainment_id' => $movie_id,
                ],
                [
                    'ticket_purchased' => 1,
                    'watched_percentage' => 0,
                    'current_status' => 'watch_now',
                    'last_watched_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Insert into movie_collections for Admin Report
            \DB::table('movie_collections')->insert([
                'entertainment_id' => $movie_id,
                'user_id'          => $user->id,
                'amount'           => $amount,
                'currency'         => GetcurrentCurrency(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        $ticket = \DB::table('ppv_tickets')
            ->where('user_id', $user->id)
            ->where('entertainment_id', $movie_id)
            ->where('status', 'active')
            ->latest()
            ->first();

        \DB::table('watch_progress')->updateOrInsert(
            [
                'user_id' => $user->id,
                'entertainment_id' =>  $movie_id,
            ],
            [
                'ticket_id' => $ticket->id,
                'entertainment_type' => $type,
                'last_time_seconds' => null,
                'watched_percentage' => 0,
                'completed_at' => null,
                'updated_at' => now(),
            ]
        );

        // $payperview = PayPerView::updateOrCreate(
        //     ['user_id' => $user->id, 'movie_id' => $movie_id],
        //     [
        //         'type' => $type,
        //         'price' => $amount,
        //         'discount_percentage' => $discount,
        //         'view_expiry_date' => $viewExpiry,
        //         'access_duration' => $access_duration,
        //         'available_for' => $available_for,
        //     ]
        // );

        PayperviewTransaction::create([
            'user_id' => auth()->id(),
            'amount' => $amount,
            'payment_type' => $payment_type,
            'payment_status' => 'paid',
            'transaction_id' => $transaction_id,
            'pay_per_view_id' => $payperview->id,
        ]);


        sendNotification([
            'notification_type' => $movie->purchase_type == 'rental' ? 'rent_video' : 'purchase_video',
            'user_id' => $user->id,
            'user_name' => $user->full_name,
            'name' => $movie->name ?? 'Video',
            'content_type' => $type,
            'status' => 'success',
            'amount' => $amount,
            'notification_group' => 'pay_per_view',
            'start_date' => now()->toDateString(),
            'end_date' => $viewExpiry->toDateString(),
        ]);

        return redirect('/')->with([
            'purchase_success' => 'Payment completed successfully!',
            'view_expiry' => $viewExpiry->format('j F, Y') // e.g., "3 March, 2024"
        ]);
    }


    protected function handleStripeSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');
        $stripe_secret_key = GetpaymentMethod('stripe_secretkey');
        $stripe = new StripeClient($stripe_secret_key);

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            return $this->handlePaymentSuccess($session->amount_total / 100, 'stripe', $session->payment_intent, $session->metadata->movie_id, $session->metadata->type, $session->metadata->access_duration, $session->metadata->available_for, $session->metadata->discount);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    protected function handleRazorpaySuccess(Request $request)
    {
        $paymentId = $request->input('razorpay_payment_id');
        $razorpayKey = GetpaymentMethod('razorpay_publickey');
        $razorpaySecret = GetpaymentMethod('razorpay_secretkey');

        if (empty($razorpayKey) || empty($razorpaySecret) || empty($paymentId)) {
            return redirect('/')->with('error', 'Missing required payment information.');
        }

        try {
            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
            $payment = $api->payment->fetch($paymentId);

            // Capture payment if authorized
            if ($payment['status'] === 'authorized') {
                $payment = $payment->capture([
                    'amount' => $payment['amount'],
                    'currency' => $payment['currency'],
                ]);
            }

            if ($payment['status'] === 'captured') {
                return $this->handlePaymentSuccess(
                    $payment['amount'] / 100,
                    'razorpay',
                    $paymentId,
                    $request->input('movie_id'),
                    $request->input('type'),
                    $request->input('access_duration'),
                    $request->input('available_for'),
                    $request->input('discount')
                );
            }

            return redirect('/')->with('error', 'Payment verification failed. Status: ' . $payment['status']);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }


    protected function handlePaystackSuccess(Request $request)
    {
        $reference = $request->input('reference');
        $paystackSecretKey = GetpaymentMethod('paystack_secretkey');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecretKey,
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $responseBody = $response->json();

            if ($responseBody['status'] && isset($responseBody['data']['amount'])) {
                $movie_id = $request->query('movie_id');
                $type = $request->query('type');
                $access_duration = $request->query('access_duration');
                $available_for = $request->query('available_for');
                $discount = $request->query('discount');

                if (!$movie_id) {
                    throw new \Exception('Movie ID is required');
                }

                return $this->handlePaymentSuccess(
                    $responseBody['data']['amount'] / 100,
                    'paystack',
                    $responseBody['data']['reference'],
                    $movie_id,
                    $type,
                    $access_duration,
                    $available_for,
                    $discount
                );
            }

            return redirect('/')->with('error', __('messages.payment_verification_failed'));
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    protected function handleFlutterwaveSuccess(Request $request)
    {
        try {
            $flutterwaveSecretKey = GetpaymentMethod('flutterwave_secretkey');
            $transactionId = $request->input('transaction_id');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $flutterwaveSecretKey,
            ])->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            $responseData = $response->json();

            if ($responseData['status'] === 'success' && $responseData['data']['status'] === 'successful') {
                return $this->handlePaymentSuccess(
                    $responseData['data']['amount'],
                    'flutterwave',
                    $responseData['data']['id'],
                    $request->query('movie_id'),
                    $request->query('type'),
                    $request->query('access_duration'),
                    $request->query('available_for'),
                    $request->query('discount')
                );
            }

            throw new \Exception('Payment verification failed');
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    protected function handlePayPalSuccess(Request $request)
    {
        try {
            $paypalClientId = GetpaymentMethod('paypal_clientid');
            $paypalSecret = GetpaymentMethod('paypal_secretkey');
            $paypalOrderId = $request->input('orderID');

            if (!$paypalClientId || !$paypalSecret || !$paypalOrderId) {
                throw new \Exception('Missing PayPal credentials or order ID.');
            }

            $apiBase = 'https://api-m.sandbox.paypal.com';

            $accessToken = Http::withBasicAuth($paypalClientId, $paypalSecret)
                ->asForm()
                ->post("$apiBase/v1/oauth2/token", ['grant_type' => 'client_credentials'])
                ->json()['access_token'] ?? null;

            if (!$accessToken) {
                throw new \Exception('Could not retrieve PayPal access token.');
            }

            $orderData = Http::withToken($accessToken)
                ->get("$apiBase/v2/checkout/orders/$paypalOrderId")
                ->json();

            if ($orderData['status'] !== 'COMPLETED') {
                throw new \Exception("Payment status is {$orderData['status']}.");
            }

            $amount = $orderData['purchase_units'][0]['amount']['value'];
            $availableFor = $request->query('available_for') ?? 48;

            return $this->handlePaymentSuccess(
                $amount,
                'paypal',
                $paypalOrderId,
                $request->query('movie_id'),
                $request->query('type'),
                $request->query('access_duration'),
                $availableFor,
                $request->query('discount')
            );
        } catch (\Exception $e) {
            $message = app()->environment('production')
                ? 'Payment verification failed. Please contact support.'
                : 'Payment verification failed: ' . $e->getMessage();

            return redirect('/')->with('error', $message);
        }
    }


    public function savePaymentPayperview(Request $request)
    {
        $userId = $request->user_id ?? auth()->user()->id;
        $user = User::find($userId);
        $accessDuration = $request->input('available_for', 48); // default to 48 hours
        $viewExpiry = now()->addDays((int) $accessDuration);

        if ($request->type == 'movie') {
            $movie = Entertainment::find($request->movie_id);
        } else if ($request->type == 'tvshow') {
            $movie = Entertainment::find($request->movie_id);
        } else if ($request->type == 'video') {
            $movie = Video::find($request->movie_id);
        } else if ($request->type == 'episode') {
            $movie = Episode::find($request->movie_id);
        } else if ($request->type == 'season') {
            $movie = season::find($request->movie_id);
        }

        // Update or create the PayPerView record
        $payperview = PayPerView::create(
            [
                'user_id' => $userId,
                'movie_id' => $request->movie_id,
                'type' => $request->type,
                'content_price' => $movie->price,
                'price' => $request->price,
                'discount_percentage' => $request->discount,
                'view_expiry_date' => $viewExpiry,
                'access_duration' => $accessDuration,
                'available_for' => $request->available_for,
            ]
        );

        // Always create a new transaction
        PayperviewTransaction::create([
            'user_id' => $userId,
            'amount' => $request->price,
            'payment_type' => $request->payment_type,
            'payment_status' => $request->payment_status,
            'transaction_id' => $request->transaction_id,
            'pay_per_view_id' => $payperview->id,
        ]);
        
        // Expire previous active ticket if exists to avoid unique constraint violation
        DB::table('ppv_tickets')
            ->where('user_id', $userId)
            ->where('entertainment_id', $request->movie_id)
            ->where('status', 'active')
            ->update(['status' => 'expired', 'updated_at' => now()]);

        DB::table('ppv_tickets')->insert([
            'user_id'            => $userId,
            'entertainment_id'   => $request->movie_id,
            'entertainment_type' => $request->type,
            'status'             => 'active',
            'purchased_at'       => now(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        
        // Reset Continue Watch history for this movie/content
        \Modules\Entertainment\Models\ContinueWatch::where('user_id', $userId)
            ->where('entertainment_id', $request->movie_id)
            ->where('entertainment_type', $request->type)
            ->delete();

        if ($request->type == 'movie') {
            DB::table('movie_user_trackings')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'entertainment_id' => $request->movie_id,
                ],
                [
                    'ticket_purchased' => 1,
                    'watched_percentage' => 0,
                    'current_status' => 'watch_now',
                    'last_watched_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        
        $ticket = DB::table('ppv_tickets')
            ->where('user_id', $userId)
            ->where('entertainment_id', $request->movie_id)
            ->where('status', 'active')
            ->latest()
            ->first();
        
        DB::table('watch_progress')->updateOrInsert(
            [
                'user_id' => $userId,
                'entertainment_id' =>  $request->movie_id,
            ],
            [
                'ticket_id' => $ticket->id,
                'entertainment_type' => $request->type,
                'last_time_seconds' => null,
                'watched_percentage' => 0,
                'completed_at' => null,
                'updated_at' => now(),
            ]
        );

        sendNotification([
            'notification_type' => $movie->purchase_type == 'rental' ? 'rent_video' : 'purchase_video',
            'user_id' => $userId,
            'user_name' => $user->full_name,
            'name' => $movie->name ?? 'Video',
            'content_type' => $request->type,
            'status' => 'success',
            'amount' => $request->price,
            'notification_group' => 'pay_per_view',
            'start_date' => now()->toDateString(),
            'end_date' => $viewExpiry->toDateString(),
        ]);

        return response()->json(['success' => 'Payment successful and movie rented successfully.'], 200);
    }

    public function unlockVideos()
    {
        $user =  auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        return view('frontend::unlockvideo');
    }

    public function allUnlockVideos(Request $request)
    {
        try {
            $user = Auth::user();

            // Get all purchased content
            $purchasedContent = [
                'movies' => MoviesResource::collection(
                    Entertainment::where('movie_access', 'pay-per-view')
                        ->where('type', 'movie')
                        ->where('status', 1)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'entertainments.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'movie')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'tvshows' => TvshowResource::collection(
                    Entertainment::where('movie_access', 'pay-per-view')
                        ->where('type', 'tvshow')
                        ->where('status', 1)
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'entertainments.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'tvshow')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'videos' => VideoResource::collection(
                    Video::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'videos.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'video')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'seasons' => SeasonResource::collection(
                    Season::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'seasons.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'season')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'episodes' => EpisodeResource::collection(
                    Episode::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'episodes.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'episode')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                })
            ];

            return response()->json([
                'status' => true,
                'data' => $purchasedContent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function setStartDate(Request $request)
    {
        // dd($request->all());
        $payPerView = PayPerView::where('user_id', $request->user_id)
            ->where('movie_id', $request->entertainment_id)
            ->where('type', $request->entertainment_type)
            ->where(function ($query) {
                $query->whereNull('view_expiry_date')
                    ->orWhere('view_expiry_date', '>', now());
            })
            ->whereNull('first_play_date')
            ->first();

        if ($payPerView && is_null($payPerView->first_play_date)) {
            $payPerView->first_play_date = now();
            $payPerView->save();
        }

        return response()->json(['success' => true]);
    }

    public function peyPerView()
    {
        return view('frontend::payperview');
    }

    public function PayPerViewList(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        // === Get Pay-Per-View Videos ===
        $videoList = Video::with('VideoStreamContentMappings', 'plan')
            ->where('status', 1)
            ->where('access', 'pay-per-view')
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            });

        $videoData = $videoList->orderBy('updated_at', 'desc')->get();
        $videoResponse = VideoResource::collection($videoData)->toArray($request);

        $episodeList = Episode::where('access', 'pay-per-view')
            ->where('status', 1)
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            });

        $episodeData = $episodeList->orderBy('updated_at', 'desc')->get();
        $episodeResponse = EpisodeResource::collection($episodeData)->toArray($request);

        // === Get Pay-Per-View Movies ===
        $movieList = Entertainment::selectRaw('
            entertainments.id,
            entertainments.id as e_id,
            entertainments.name,
            entertainments.type,
            entertainments.price,
            entertainments.purchase_type,
            entertainments.access_duration,
            entertainments.discount,
            entertainments.available_for,
            entertainments.plan_id,
            plan.level as plan_level,
            entertainments.description,
            entertainments.trailer_url_type,
            entertainments.is_restricted,
            entertainments.language,
            entertainments.imdb_rating,
            entertainments.content_rating,
            entertainments.duration,
            entertainments.video_upload_type,
            GROUP_CONCAT(egm.genre_id) as genres,
            entertainments.release_date,
            entertainments.trailer_url,
            entertainments.video_url_input,
            entertainments.poster_url as poster_image,
            entertainments.thumbnail_url as thumbnail_image,
            entertainments.trailer_url as base_url,
            entertainments.movie_access
        ')
            ->join('entertainment_gener_mapping as egm', 'egm.entertainment_id', '=', 'entertainments.id')
            ->leftJoin('plan', 'plan.id', '=', 'entertainments.plan_id')
            ->where('entertainments.movie_access', 'pay-per-view')
            ->where('entertainments.status', 1)
            ->groupBy('entertainments.id')
            ->orderBy('entertainments.id', 'desc');

        $movieData = $movieList->when(request()->has('is_restricted'), function ($query) {
            $query->where('is_restricted', request()->is_restricted);
        })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            })->get();
        $movieResponse = MoviesResourceV2::collection($movieData)->toArray($request);

        // === Merge Video + Movie Data ===
        $combinedData = array_merge($videoResponse, $movieResponse, $episodeResponse);

        // Optional: sort combined data by release date (descending)
        usort($combinedData, function ($a, $b) {
            return strtotime($b['release_date']) <=> strtotime($a['release_date']);
        });

        // === Pagination manually since we're merging two lists ===
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = collect($combinedData);
        $paginated = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current()]
        );

        // === Handle AJAX response ===
        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($paginated as $item) {
                $userId = auth()->id();

                if (in_array($item['type'], ['movie', 'tvshow'])) {
                    if ($userId) {
                        $isInWatchList = WatchList::where('entertainment_id', $item['id'])
                            ->where('user_id', $userId)
                            ->exists();
                        $item['is_watch_list'] = $isInWatchList;
                    }
                    $html .= view('frontend::components.card.card_entertainment', ['value' => $item])->render();
                }

                if ($item['type'] === 'video') {
                    if ($userId) {
                        $isInWatchList = WatchList::where('entertainment_id', $item['id'])
                            ->where('user_id', $userId)
                            ->where('type', 'video')
                            ->exists();
                        $item['is_watch_list'] = $isInWatchList;
                    }
                    $html .= view('frontend::components.card.card_video', ['data' => $item])->render();
                }

                if ($item['type'] === 'episode') {
                    $html .= view('frontend::components.card.card_pay_per_view', ['value' => $item])->render();
                }
            }

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => 'Pay-per-view list loaded',
                'hasMore' => $paginated->hasMorePages(),
            ]);
        }

        // === JSON response for API (non-AJAX) ===
        return response()->json([
            'status' => true,
            'data' => $paginated,
            'message' => 'Pay-per-view list loaded',
        ]);
    }
}
