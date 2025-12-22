<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Gagal - Jinah</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .failed-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 0 1rem;
        }
        
        .failed-icon {
            color: #dc3545;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .failed-card {
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="failed-container">
        <div class="failed-card card">
            <div class="card-body">
                <i class="fas fa-times-circle failed-icon"></i>
                
                <h1 class="h2 text-danger mb-4">Pembayaran Gagal</h1>
                
                <p class="lead text-muted mb-4">
                    Maaf, pembayaran Anda tidak dapat diproses saat ini.
                </p>
                
                @if($transactionId)
                    <div class="alert alert-warning">
                        <strong>ID Referensi:</strong> {{ $transactionId }}
                    </div>
                @endif
                
                @if($error)
                    <div class="alert alert-danger">
                        <strong>Error:</strong> {{ $error }}
                    </div>
                @endif
                
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="fas fa-credit-card text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Periksa Detail Pembayaran</h6>
                            <small class="text-muted">Verifikasi info kartu dan coba lagi</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="fas fa-wifi text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Periksa Koneksi</h6>
                            <small class="text-muted">Pastikan internet stabil</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="fas fa-headset text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Hubungi Dukungan</h6>
                            <small class="text-muted">Kami siap membantu</small>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-grid gap-2 d-md-block">
                    <a href="{{ route('jinah.payment.index', ['order_id' => $transactionId]) }}" class="btn btn-primary">
                        <i class="fas fa-redo me-2"></i>
                        Coba Lagi
                    </a>
                    <a href="mailto:support@example.com" class="btn btn-outline-danger">
                        <i class="fas fa-envelope me-2"></i>
                        Hubungi Dukungan
                    </a>
                    <a href="/" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>
                        Kembali ke Beranda
                    </a>
                </div>
                
                <div class="mt-4 pt-4 border-top">
                    <h6 class="text-muted">Solusi Umum:</h6>
                    <ul class="list-unstyled small text-muted text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Verifikasi detail kartu Anda sudah benar</li>
                        <li><i class="fas fa-check text-success me-2"></i>Pastikan saldo mencukupi</li>
                        <li><i class="fas fa-check text-success me-2"></i>Periksa apakah kartu Anda diaktifkan untuk pembayaran online</li>
                        <li><i class="fas fa-check text-success me-2"></i>Coba gunakan metode pembayaran yang berbeda</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Footer -->
    <footer class="text-center py-4 mt-5">
        <div class="container">
            <small class="text-muted">
                Powered by 
                <img src="https://i.postimg.cc/W3X5cx2h/finpay-logo.png" alt="FinPay" style="height: 20px; vertical-align: middle; margin-left: 5px;">
            </small>
        </div>
    </footer>
</body>
</html>