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
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: scaleIn 0.5s ease-in-out;
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .success-card {
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .success-title {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .transaction-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #6c757d;
        }

        .detail-value {
            font-weight: 500;
            color: #212529;
        }

        .amount-highlight {
            color: #28a745;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .btn-home {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .redirect-notice {
            color: #6c757d;
            font-size: 0.95rem;
            padding: 1rem;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .success-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="success-container">
        <div class="success-card card border-0">
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

                <div class="success-badge">
                    <i class="fas fa-shield-alt me-2"></i>TERVERIFIKASI
                </div>
                
                <h1 class="h2 success-title mb-3">Pembayaran Berhasil!</h1>
                
                <p class="lead text-muted mb-4">
                    Terima kasih! Pembayaran Anda telah berhasil diproses.
                </p>

                <div class="redirect-notice mt-4">
                    <i class="fas fa-clock me-2"></i>
                    <span>Anda akan dialihkan dalam <strong id="countdown">3</strong> detik</span>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Countdown Timer and Redirect -->
    <script>
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                // Redirect to home or specified URL
                window.location.href = '{{ $redirectUrl }}';
            }
        }, 1000);
    </script>
    
    <!-- Footer -->
    <footer class="text-center py-4 mt-5">
        <div class="container">
            <small class="text-muted">
                Powered by Converso
            </small>
        </div>
    </footer>
</body>
</html>
