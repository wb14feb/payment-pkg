<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Jinah</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .payment-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .item-card {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
        }
        
        .item-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .payment-method-card {
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
            cursor: pointer;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 6px;
        }
        
        .payment-method-card:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .payment-method-card.selected {
            border-color: #28a745;
            background-color: #f0fff4;
            box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
        }
        
        .total-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
        }
        
        .quantity-control {
            max-width: 120px;
        }
        
        .step-header {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="row">
            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h4 class="mb-4">
                            <i class="fas fa-receipt me-2"></i>
                            Ringkasan Pesanan
                        </h4>
                        
                        <div id="selectedItems" class="mb-3">
                            @foreach($items as $item)
                                <div class="d-flex justify-content-between align-items-center mb-2" data-item-id="{{ $item['id'] }}" data-item-price="{{ $item['price'] }}" data-item-quantity="{{ $item['quantity'] }}">
                                    <div>
                                        <strong>{{ $item['name'] }}</strong>
                                        <br>
                                        <small class="text-muted">Qty: {{ $item['quantity'] }} × Rp. {{ number_format($item['price']) }}</small>
                                    </div>
                                    <span>Rp. {{ number_format($item['price'] * $item['quantity']) }}</span>
                                </div>
                            @endforeach



                        </div>
                        
                        <hr>
                        
                        @if(isset($order['discount']) && $order['discount'] > 0)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success">Diskon:</span>
                                <span class="text-success">- Rp. {{ number_format($order['discount']) }}</span>
                            </div>
                        @endif
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Total Pembayaran:</h5>
                            <h4 class="mb-0 text-primary" id="totalAmount">Rp. {{ number_format(array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items)) - ($order['discount'] ?? 0)) }}</h4>
                        </div>
                        
                        <div class="mt-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-shield-alt text-success me-2"></i>
                                <small class="text-muted">Proses pembayaran aman</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-lock text-success me-2"></i>
                                <small class="text-muted">Transaksi terenkripsi SSL</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-undo text-success me-2"></i>
                                <small class="text-muted">Jaminan uang kembali</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form -->
            <div class="col-lg-7">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title mb-4">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Selesaikan Pembelian Anda
                        </h2>
                        
                        <form id="paymentForm" action="{{ route('jinah.payment.process') }}" method="POST">
                            @csrf
                            
                            <!-- Customer Information -->
                            <div class="step-header">
                                <h4>Informasi Pelanggan</h4>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Nama Lengkap</label>
                                        <input type="text" 
                                               class="form-control {{ ($errors ?? false) && $errors->has('customer_name') ? 'is-invalid' : '' }}" 
                                               id="customer_name" 
                                               name="customer_name" 
                                               value="{{ old('customer_name', $customerInfo['name'] ?? '') }}" 
                                               required>
                                        @if(($errors ?? false) && $errors->has('customer_name'))
                                            <div class="invalid-feedback">{{ $errors->first('customer_name') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_email" class="form-label">Alamat Email</label>
                                        <input type="email" 
                                               class="form-control {{ ($errors ?? false) && $errors->has('customer_email') ? 'is-invalid' : '' }}" 
                                               id="customer_email" 
                                               name="customer_email" 
                                               value="{{ old('customer_email', $customerInfo['email'] ?? '') }}" 
                                               required>
                                        @if(($errors ?? false) && $errors->has('customer_email'))
                                            <div class="invalid-feedback">{{ $errors->first('customer_email') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_phone" class="form-label">Nomor Telepon</label>
                                        <input type="tel" 
                                               class="form-control {{ ($errors ?? false) && $errors->has('customer_phone') ? 'is-invalid' : '' }}" 
                                               id="customer_phone" 
                                               name="customer_phone" 
                                               value="{{ old('customer_phone', $customerInfo['phone'] ?? '') }}" 
                                               required>
                                        @if(($errors ?? false) && $errors->has('customer_phone'))
                                            <div class="invalid-feedback">{{ $errors->first('customer_phone') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="step-header">
                                <h4>Pilih Metode Pembayaran</h4>
                            </div>
                            
                            @include('jinah::payment.partials.payment-methods', ['paymentMethods' => $paymentMethods])
                            
                            @if(($errors ?? false) && $errors->has('payment'))
                                <div class="alert alert-danger">
                                    {{ $errors->first('payment') }}
                                </div>
                            @endif

                            <input type="hidden" name="order_id" value="{{ $orderId ?? '' }}">
                            
                            <!-- Payment Button -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" 
                                        id="submitBtn" 
                                        class="btn btn-primary btn-lg" 
                                        disabled>
                                    <i class="fas fa-credit-card me-2"></i>
                                    Lanjutkan Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('paymentForm');
            const submitBtn = document.getElementById('submitBtn');
            const totalAmount = document.getElementById('totalAmount');
            const selectedItemsContainer = document.getElementById('selectedItems');
            
            // Initialize payment functionality
            initializePaymentMethods();
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const selectedItems = getSelectedItems();
                const selectedPaymentMethod = getSelectedPaymentMethod();
                
                if (!selectedPaymentMethod) {
                    alert('Silakan pilih metode pembayaran.');
                    return;
                }
                
                // Add selected items to form
                selectedItems.forEach((item, index) => {
                    const itemIdInput = document.createElement('input');
                    itemIdInput.type = 'hidden';
                    itemIdInput.name = `items[${index}][id]`;
                    itemIdInput.value = item.id;
                    form.appendChild(itemIdInput);
                    
                    const itemQuantityInput = document.createElement('input');
                    itemQuantityInput.type = 'hidden';
                    itemQuantityInput.name = `items[${index}][quantity]`;
                    itemQuantityInput.value = item.quantity;
                    form.appendChild(itemQuantityInput);
                });
                
                // Add payment method to form
                const paymentMethodInput = document.createElement('input');
                paymentMethodInput.type = 'hidden';
                paymentMethodInput.name = 'payment_method';
                paymentMethodInput.value = selectedPaymentMethod;
                form.appendChild(paymentMethodInput);
                
                // Submit form
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                
                form.submit();
            });
            
            function initializePaymentMethods() {
                const paymentCards = document.querySelectorAll('.payment-method-card');
                
                paymentCards.forEach(card => {
                    card.addEventListener('click', function() {
                        // Remove selected class from all cards
                        paymentCards.forEach(c => c.classList.remove('selected'));
                        
                        // Add selected class to clicked card
                        this.classList.add('selected');
                        
                        // Update hidden input
                        document.getElementById('selectedPaymentMethod').value = this.dataset.method;
                        
                        // Enable submit button
                        submitBtn.disabled = false;
                    });
                });
            }
            
            function getSelectedItems() {
                const selectedItems = [];
                const itemElements = document.querySelectorAll('#selectedItems > div[data-item-id]');
                
                itemElements.forEach(element => {
                    selectedItems.push({
                        id: parseInt(element.dataset.itemId),
                        quantity: parseInt(element.dataset.itemQuantity)
                    });
                });
                
                return selectedItems;
            }
            
            function getSelectedPaymentMethod() {
                return document.getElementById('selectedPaymentMethod').value;
            }
        });
    </script>
</body>
</html>