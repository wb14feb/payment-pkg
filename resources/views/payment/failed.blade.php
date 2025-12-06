<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Jinah</title>
    
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
                
                <h1 class="h2 text-danger mb-4">Payment Failed</h1>
                
                <p class="lead text-muted mb-4">
                    We're sorry, but your payment could not be processed at this time.
                </p>
                
                @if($transactionId)
                    <div class="alert alert-warning">
                        <strong>Reference ID:</strong> {{ $transactionId }}
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
                            <h6>Check Payment Details</h6>
                            <small class="text-muted">Verify card info and try again</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="fas fa-wifi text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Check Connection</h6>
                            <small class="text-muted">Ensure stable internet</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="fas fa-headset text-primary mb-2" style="font-size: 2rem;"></i>
                            <h6>Contact Support</h6>
                            <small class="text-muted">We're here to help</small>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-grid gap-2 d-md-block">
                    <a href="{{ route('jinah.payment.index') }}" class="btn btn-primary">
                        <i class="fas fa-redo me-2"></i>
                        Try Again
                    </a>
                    <a href="mailto:support@example.com" class="btn btn-outline-danger">
                        <i class="fas fa-envelope me-2"></i>
                        Contact Support
                    </a>
                    <a href="/" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>
                        Back to Home
                    </a>
                </div>
                
                <div class="mt-4 pt-4 border-top">
                    <h6 class="text-muted">Common Solutions:</h6>
                    <ul class="list-unstyled small text-muted text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Verify your card details are correct</li>
                        <li><i class="fas fa-check text-success me-2"></i>Ensure sufficient funds are available</li>
                        <li><i class="fas fa-check text-success me-2"></i>Check if your card is enabled for online payments</li>
                        <li><i class="fas fa-check text-success me-2"></i>Try using a different payment method</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>