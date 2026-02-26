<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        "key": "{{ config('multi-payment.gateways.razorpay.key_id') }}", 
        "amount": "{{ $response['raw']['amount'] }}", 
        "currency": "{{ $response['raw']['currency'] }}",
        "name": "{{ config('app.name') }}",
        "description": "Payment",
        "order_id": "{{ $response['transaction_id'] }}", // The Razorpay Order ID
        {{-- Custom Callback --}}
        "handler": function (response){
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ $verifyUrl }}'; 

            // Add CSRF Token
            let csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            // Add Razorpay payload
            ['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature'].forEach((field) => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = field;
                input.value = response[field];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        },
        "theme": {
            "color": "{{ $themeColor ?? '#3399cc' }}"
        }
    };
    var rzp1 = new Razorpay(options);
    
    // Auto-open modal or trigger via custom button
    @if($autoOpen ?? true)
        window.onload = function() {
            rzp1.open();
        };
    @endif
</script>

<button id="rzp-button1" class="{{ $buttonClass ?? 'btn btn-primary' }}" onclick="rzp1.open(); event.preventDefault();">
    {{ $buttonText ?? 'Pay Now' }}
</button>
