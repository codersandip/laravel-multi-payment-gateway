@if($autoOpen ?? true)
    <p>Redirecting to Secure Stripe Checkout...</p>
    <script>
        window.location.href = "{!! $response['raw']['url'] !!}";
    </script>
@else
    <a href="{!! $response['raw']['url'] !!}" class="{{ $buttonClass ?? 'btn btn-primary' }}">
        {{ $buttonText ?? 'Proceed to Stripe Checkout' }}
    </a>
@endif
