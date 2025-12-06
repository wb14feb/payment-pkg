<div class="payment-methods-list">
    @foreach($paymentMethods as $method)
        <div class="payment-method-card" data-method="{{ $method['id'] }}">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-{{ $method['icon'] }} fa-lg text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0">{{ $method['name'] }}</h6>
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="radio" 
                               name="payment_method_radio" 
                               id="method_{{ $method['id'] }}" 
                               value="{{ $method['id'] }}">
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<input type="hidden" id="selectedPaymentMethod" value="">

@if(count($paymentMethods) === 0)
    <div class="text-center py-5">
        <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No payment methods available</h4>
        <p class="text-muted">Please contact support for assistance.</p>
    </div>
@endif