<div class="row">
    @foreach($items as $item)
        <div class="col-md-6 mb-3">
            <div class="item-card card h-100" 
                 data-item-id="{{ $item['id'] }}" 
                 data-item-name="{{ $item['name'] }}" 
                 data-item-price="{{ $item['price'] }}">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <h5 class="card-title">{{ $item['name'] }}</h5>
                            <p class="card-text text-muted">{{ $item['description'] }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="price">
                                    <span class="h4 text-primary">${{ number_format($item['price'], 2) }}</span>
                                    <small class="text-muted">{{ strtoupper($item['currency']) }}</small>
                                </div>
                                <div class="quantity-display">
                                    <label class="form-label small">Jumlah:</label>
                                    <div class="badge bg-secondary fs-6">{{ $item['quantity'] }}</div>
                                    <input type="hidden" 
                                           class="quantity-input" 
                                           id="quantity_{{ $item['id'] }}" 
                                           value="{{ $item['quantity'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if(count($items) === 0)
    <div class="text-center py-5">
        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">Tidak ada item tersedia</h4>
        <p class="text-muted">Silakan periksa kembali nanti untuk item yang tersedia.</p>
    </div>
@endif