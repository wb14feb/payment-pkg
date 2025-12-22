/**
 * Jinah Payment Package - JavaScript
 * Payment form handling and interaction
 */

(function(window) {
    'use strict';

    /**
     * Jinah Payment Handler
     */
    class JinahPayment {
        constructor(options = {}) {
            this.options = {
                formSelector: '#paymentForm',
                submitButtonSelector: '#submitBtn',
                totalAmountSelector: '#totalAmount',
                selectedItemsSelector: '#selectedItems',
                paymentMethodCardSelector: '.payment-method-card',
                selectedPaymentMethodSelector: '#selectedPaymentMethod',
                ...options
            };

            this.form = document.querySelector(this.options.formSelector);
            this.submitBtn = document.querySelector(this.options.submitButtonSelector);
            this.totalAmount = document.querySelector(this.options.totalAmountSelector);
            this.selectedItemsContainer = document.querySelector(this.options.selectedItemsSelector);

            this.init();
        }

        /**
         * Initialize the payment handler
         */
        init() {
            if (!this.form) {
                console.warn('Jinah Payment: Form not found');
                return;
            }

            this.initializePaymentMethods();
            this.initializeFormSubmission();
            this.initializeQuantityControls();
        }

        /**
         * Initialize payment method selection
         */
        initializePaymentMethods() {
            const paymentCards = document.querySelectorAll(this.options.paymentMethodCardSelector);
            const selectedPaymentMethodInput = document.querySelector(this.options.selectedPaymentMethodSelector);

            paymentCards.forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Remove selected class from all cards
                    paymentCards.forEach(c => c.classList.remove('selected'));
                    
                    // Add selected class to clicked card
                    card.classList.add('selected');
                    
                    // Update hidden input
                    if (selectedPaymentMethodInput) {
                        selectedPaymentMethodInput.value = card.dataset.method;
                    }
                    
                    // Enable submit button
                    if (this.submitBtn) {
                        this.submitBtn.disabled = false;
                    }

                    // Trigger custom event
                    this.triggerEvent('paymentMethodSelected', {
                        method: card.dataset.method,
                        element: card
                    });
                });
            });
        }

        /**
         * Initialize form submission
         */
        initializeFormSubmission() {
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                if (!this.validateForm()) {
                    return;
                }

                const selectedItems = this.getSelectedItems();
                const selectedPaymentMethod = this.getSelectedPaymentMethod();
                
                if (!selectedPaymentMethod) {
                    this.showAlert('Silakan pilih metode pembayaran.', 'warning');
                    return;
                }
                
                // Add selected items to form
                this.addItemsToForm(selectedItems);
                
                // Add payment method to form
                this.addPaymentMethodToForm(selectedPaymentMethod);
                
                // Disable submit button and show loading state
                this.setLoadingState(true);
                
                // Trigger custom event before submission
                const canSubmit = this.triggerEvent('beforeSubmit', {
                    items: selectedItems,
                    paymentMethod: selectedPaymentMethod
                });

                if (canSubmit !== false) {
                    this.form.submit();
                } else {
                    this.setLoadingState(false);
                }
            });
        }

        /**
         * Initialize quantity controls
         */
        initializeQuantityControls() {
            const quantityButtons = document.querySelectorAll('[data-quantity-action]');
            
            quantityButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const action = button.dataset.quantityAction;
                    const input = button.closest('.quantity-control').querySelector('input[type="number"]');
                    
                    if (input) {
                        let value = parseInt(input.value) || 1;
                        const min = parseInt(input.min) || 1;
                        const max = parseInt(input.max) || 999;
                        
                        if (action === 'increase' && value < max) {
                            value++;
                        } else if (action === 'decrease' && value > min) {
                            value--;
                        }
                        
                        input.value = value;
                        this.updateItemQuantity(input.dataset.itemId, value);
                        this.calculateTotal();
                    }
                });
            });
        }

        /**
         * Validate form before submission
         */
        validateForm() {
            const requiredFields = this.form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });

            return isValid;
        }

        /**
         * Get selected items from the form
         */
        getSelectedItems() {
            const selectedItems = [];
            const itemElements = document.querySelectorAll('#selectedItems > div[data-item-id]');
            
            itemElements.forEach(element => {
                selectedItems.push({
                    id: parseInt(element.dataset.itemId),
                    quantity: parseInt(element.dataset.itemQuantity),
                    price: parseFloat(element.dataset.itemPrice)
                });
            });
            
            return selectedItems;
        }

        /**
         * Get selected payment method
         */
        getSelectedPaymentMethod() {
            const input = document.querySelector(this.options.selectedPaymentMethodSelector);
            return input ? input.value : null;
        }

        /**
         * Add items to form as hidden inputs
         */
        addItemsToForm(items) {
            // Remove existing item inputs
            this.form.querySelectorAll('input[name^="items["]').forEach(input => input.remove());

            // Add new item inputs
            items.forEach((item, index) => {
                this.addHiddenInput(`items[${index}][id]`, item.id);
                this.addHiddenInput(`items[${index}][quantity]`, item.quantity);
            });
        }

        /**
         * Add payment method to form
         */
        addPaymentMethodToForm(method) {
            // Remove existing payment method input
            const existingInput = this.form.querySelector('input[name="payment_method"]');
            if (existingInput) {
                existingInput.remove();
            }

            // Add new payment method input
            this.addHiddenInput('payment_method', method);
        }

        /**
         * Add hidden input to form
         */
        addHiddenInput(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            this.form.appendChild(input);
        }

        /**
         * Update item quantity
         */
        updateItemQuantity(itemId, quantity) {
            const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
            if (itemElement) {
                itemElement.dataset.itemQuantity = quantity;
            }
        }

        /**
         * Calculate and update total amount
         */
        calculateTotal() {
            const items = this.getSelectedItems();
            let total = 0;

            items.forEach(item => {
                total += item.price * item.quantity;
            });

            // Get discount if exists
            const discountElement = document.querySelector('[data-discount]');
            const discount = discountElement ? parseFloat(discountElement.dataset.discount) : 0;
            
            total -= discount;

            if (this.totalAmount) {
                this.totalAmount.textContent = this.formatCurrency(total);
            }

            this.triggerEvent('totalCalculated', { total, discount });
        }

        /**
         * Format currency (Indonesian Rupiah)
         */
        formatCurrency(amount) {
            return 'Rp. ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        /**
         * Set loading state
         */
        setLoadingState(loading) {
            if (!this.submitBtn) return;

            if (loading) {
                this.submitBtn.disabled = true;
                this.submitBtn.classList.add('btn-loading');
                this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            } else {
                this.submitBtn.disabled = false;
                this.submitBtn.classList.remove('btn-loading');
                this.submitBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Lanjutkan Pembayaran';
            }
        }

        /**
         * Show alert message
         */
        showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            this.form.insertAdjacentElement('afterbegin', alertDiv);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        /**
         * Trigger custom event
         */
        triggerEvent(eventName, detail = {}) {
            const event = new CustomEvent(`jinah:${eventName}`, {
                detail,
                bubbles: true,
                cancelable: true
            });
            
            return this.form.dispatchEvent(event);
        }
    }

    // Export to window
    window.JinahPayment = JinahPayment;

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.querySelector('#paymentForm')) {
                window.jinahPayment = new JinahPayment();
            }
        });
    } else {
        if (document.querySelector('#paymentForm')) {
            window.jinahPayment = new JinahPayment();
        }
    }

})(window);
