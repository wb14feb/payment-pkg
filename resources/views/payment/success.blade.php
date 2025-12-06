<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Jinah</title>
    
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
                
                <h1 class="h2 text-success mb-4">Payment Initiated!</h1>
                
                <p class="lead text-muted mb-4">
                    Please complete your payment using the details below.
                </p>
                
                @if($transactionId)
                    <div class="alert alert-info">
                        <strong>Transaction ID:</strong> {{ $transactionId }}
                    </div>
                @endif
                
                @if($amount)
                    <div class="mb-4">
                        <span class="h4 text-primary">{{ number_format($amount, 2) }}</span>
                        <small class="text-muted">Amount to pay</small>
                    </div>
                @endif

                @if($contentType === 'va' && $content)
                    <div class="alert alert-warning mb-4">
                        <h5 class="alert-heading"><i class="fas fa-university me-2"></i>Virtual Account Number</h5>
                        <hr>
                        <div class="text-center my-3">
                            <h2 class="font-monospace text-dark">{{ $content }}</h2>
                        </div>
                        <p class="mb-0 small">Please transfer the exact amount to this virtual account number.</p>
                    </div>
                @elseif($contentType === 'qr' && $content)
                    <div class="alert alert-warning mb-4">
                        <h5 class="alert-heading"><i class="fas fa-qrcode me-2"></i>Scan QR Code</h5>
                        <hr>
                        <div class="text-center my-3">
                            <img src="{{ $content }}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                        </div>
                        <p class="mb-0 small">Scan this QR code using your mobile banking app.</p>
                    </div>
                @elseif($contentType === 'cc' && $content)
                    <div class="mb-4">
                        <h5><i class="fas fa-credit-card me-2"></i>Complete Payment</h5>
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
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-envelope text-primary me-3"></i>
                            <div>
                                <h6 class="mb-0">Email Confirmation</h6>
                                <small class="text-muted">Receipt sent to your email</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock text-primary me-3"></i>
                            <div>
                                <h6 class="mb-0">Processing Time</h6>
                                <small class="text-muted">Immediately</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>