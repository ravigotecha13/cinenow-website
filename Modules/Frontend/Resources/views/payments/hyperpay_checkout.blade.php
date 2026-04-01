@extends('frontend::layouts.master')

@section('content')
    <div class="section-spacing-bottom">
        <div class="container py-5">
            <h4 class="mb-4">HyperPay</h4>

            @if (!empty($error))
                <div class="alert alert-danger">{{ $error }}</div>
            @endif

            @if (!empty($checkoutId) && !empty($baseUrl))
                <script src="{{ rtrim($baseUrl, '/') }}/v1/paymentWidgets.js?checkoutId={{ $checkoutId }}"></script>

                <form action="{{ route($resultRouteName) }}" class="paymentWidgets"
                    data-brands="{{ $brands ?? 'VISA MASTER' }}">

                    <input type="hidden" name="gateway" value="hyperpay">
                    <input type="hidden" name="purpose" value="{{ $purpose }}">
                    <input type="hidden" name="checkout_id" value="{{ $checkoutId }}">
                    <input type="hidden" name="amount" value="{{ $amount }}">
                    <input type="hidden" name="currency" value="{{ $currency }}">

                    @if ($purpose === 'ppv')
                        <input type="hidden" name="movie_id" value="{{ $movie_id }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="discount" value="{{ $discount }}">
                        <input type="hidden" name="access_duration" value="{{ $access_duration }}">
                        <input type="hidden" name="available_for" value="{{ $available_for }}">
                    @elseif ($purpose === 'subscription')
                        <input type="hidden" name="plan_id" value="{{ $plan_id }}">
                        <input type="hidden" name="promotion_id" value="{{ $promotion_id }}">
                    @endif
                </form>
            @endif
        </div>
    </div>
@endsection

