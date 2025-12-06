<div class="total-section">
    <h5 class="mb-4">
        <i class="fas fa-receipt me-2"></i>
        Order Summary
    </h5>
    
    <div id="selectedItems" class="mb-4">
        <!-- Items will be loaded here -->
    </div>
    
    <hr>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Total Amount:</h5>
        <h4 class="mb-0 text-primary" id="totalAmount">$0.00</h4>
    </div>
    
    <div class="d-grid gap-2">
        <button type="submit" 
                id="submitBtn" 
                class="btn btn-primary btn-lg" 
                form="paymentForm" 
                disabled>
            <i class="fas fa-credit-card me-2"></i>
            Proceed to Payment
        </button>
    </div>
    
    <div class="mt-4">
        <div class="d-flex align-items-center mb-2">
            <i class="fas fa-shield-alt text-success me-2"></i>
            <small class="text-muted">Secure payment processing</small>
        </div>
        <div class="d-flex align-items-center mb-2">
            <i class="fas fa-lock text-success me-2"></i>
            <small class="text-muted">SSL encrypted transactions</small>
        </div>
        <div class="d-flex align-items-center">
            <i class="fas fa-undo text-success me-2"></i>
            <small class="text-muted">Money-back guarantee</small>
        </div>
    </div>
</div>

<script>
    function clearSelection() {
        // Clear payment method selection
        const paymentCards = document.querySelectorAll('.payment-method-card');
        paymentCards.forEach(card => card.classList.remove('selected'));
        document.getElementById('selectedPaymentMethod').value = '';
        
        // Clear radio buttons
        const radioButtons = document.querySelectorAll('input[name="payment_method_radio"]');
        radioButtons.forEach(radio => radio.checked = false);
        
        // Update order summary
        if (typeof updateOrderSummary === 'function') {
            updateOrderSummary();
        }
    }
</script>