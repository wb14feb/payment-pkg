<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - Jinah</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .success-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 0 1rem;
        }
        
        .success-icon {
            color: #28a745;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .success-card {
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="success-container">
        <div class="success-card card">
            <div class="card-body">
                <i class="fas fa-check-circle success-icon"></i>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif
                
                <h1 class="h2 text-success mb-4">Pembayaran Dimulai!</h1>
                
                <p class="lead text-muted mb-4">
                    Silakan selesaikan pembayaran Anda menggunakan detail di bawah ini.
                </p>
                
                @if($transactionId)
                    <div class="alert alert-info">
                        <strong>ID Transaksi:</strong> {{ $transactionId }}
                    </div>
                @endif
                
                @if($amount)
                    <div class="mb-4">
                        <span class="h4 text-primary">{{ number_format($amount, 2) }}</span>
                        <small class="text-muted">Jumlah yang harus dibayar</small>
                    </div>
                @endif

                @if($contentType === 'va' && $content)
                    <div class="alert alert-warning mb-4">
                        <h5 class="alert-heading"><i class="fas fa-university me-2"></i>Nomor Virtual Account{{ $paymentMethodName ? ' - ' . $paymentMethodName : '' }}</h5>
                        <hr>
                        <div class="text-center my-3">
                            <h2 class="font-monospace text-dark">{{ $content }}</h2>
                            <button class="btn btn-sm btn-outline-primary mt-2" id="copyButton" onclick="copyToClipboard('{{ $content }}', this)">
                                <i class="fas fa-copy me-1"></i>Salin Nomor
                            </button>
                        </div>
                        <p class="mb-0 small">Silakan transfer jumlah yang tepat ke nomor virtual account ini.</p>
                    </div>
                @elseif($contentType === 'qr' && $content)
                    <div class="alert alert-warning mb-4">
                        <h5 class="alert-heading"><i class="fas fa-qrcode me-2"></i>Pindai Kode QR</h5>
                        <hr>
                        <p class="text-center"><strong>CV Abinathayana</strong></p>
                        <div class="text-center my-3">
                            <img src="{{ $content }}" alt="QR Code" class="img-fluid" style="max-width: 300px;" id="qrCodeImage">
                        </div>
                        <div class="text-center mb-3">
                            <a href="{{ $content }}" download="qr-code-{{ $transactionId ?? 'payment' }}.png" class="btn btn-primary btn-sm">
                                <i class="fas fa-download me-2"></i>Unduh QR Code
                            </a>
                        </div>

                        <p class="mb-0 small">Pindai kode QR ini menggunakan aplikasi mobile banking Anda.</p>
                    </div>
                @elseif($contentType === 'cc' && $content)
                    <div class="mb-4">
                        <h5><i class="fas fa-credit-card me-2"></i>Selesaikan Pembayaran</h5>
                        <hr>
                        <iframe 
                            src="{{ $content }}" 
                            width="100%" 
                            height="500" 
                            frameborder="0"
                            class="border rounded"
                            style="min-height: 500px;"
                        ></iframe>
                    </div>
                @endif
                
                <div class="row mt-4">
                    <div class="col-12">
                        <p class="text-muted small mb-3">Setelah dirasa melakukan pembayaran, tekan tombol cek status pembayaran</p>
                        <a href="{{ route('jinah.payment.status', ['transactionId' => $transactionId ?? 0]) }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sync-alt me-2"></i>Cek Status Pembayaran
                        </a>
                    </div>
                </div>
                
                <hr class="my-4">
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(function() {
                button.innerHTML = '<i class="fas fa-check me-1"></i>Sudah tersalin';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-success');
                
                // Reset button after 2 seconds
                setTimeout(function() {
                    button.innerHTML = '<i class="fas fa-copy me-1"></i>Salin Nomor';
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Gagal menyalin: ', err);
            });
        }
    </script>
    
    <!-- Footer -->
    <footer class="text-center py-4 mt-5">
        <div class="container">
            <small class="text-muted">
                Powered by FINPAY
            </small>
        </div>
    </footer>
</body>
</html>