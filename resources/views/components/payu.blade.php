{{-- PayU returns a fully generated form payload, we auto-submit it to their Action URL --}}
@php
    $actionUrl = $response['raw']['url'] ?? (config('multi-payment.gateways.payu.test_mode') ? 'https://test.payu.in/_payment' : 'https://secure.payu.in/_payment');
@endphp

<form action="{{ $actionUrl }}" method="post" id="payu-payment-form">
    @foreach($response['raw'] as $key => $value)
        @if($key !== 'url')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    @if(!($autoOpen ?? true))
        <button type="submit" class="{{ $buttonClass ?? 'btn btn-primary' }}">
            {{ $buttonText ?? 'Proceed to PayU' }}
        </button>
    @else
        <p>Redirecting to PayU automatically...</p>
    @endif
</form>

@if($autoOpen ?? true)
<script>
    // Automatically submit the form
    window.onload = function() {
        document.getElementById('payu-payment-form').submit();
    };
</script>
@endif
