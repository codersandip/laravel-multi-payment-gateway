<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<div id="payment-form"></div>
<script>
    const environment = "{{ config('multi-payment.gateways.cashfree.test_mode') ? 'sandbox' : 'production' }}";
    const cashfree = Cashfree({
        mode: environment,
    });

    let checkoutOptions = {
        paymentSessionId: "{{ $response['raw']['payment_session_id'] }}",
        redirectTarget: "_self" // Use _self to redirect the same tab, or _blank for popup
    };

    @if($autoOpen ?? true)
        window.onload = function() {
            cashfree.checkout(checkoutOptions);
        };
    @endif
    
    function initiateCashfree() {
        cashfree.checkout(checkoutOptions);
    }
</script>

<button class="{{ $buttonClass ?? 'btn btn-primary' }}" onclick="initiateCashfree(); event.preventDefault();">
    {{ $buttonText ?? 'Pay Now' }}
</button>
