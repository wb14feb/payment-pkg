<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - Jinah</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .cancelled-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 0 1rem;
        }
        
        .cancelled-icon {
            color: #6c757d;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .cancelled-card {
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="cancelled-container">
        <div class="cancelled-card card">
            <div class="card-body">
                <i class="fas fa-ban cancelled-icon"></i>
                
                <h1 class="h2 text-muted mb-4">Payment Cancelled</h1>
                
                <p class="lead text-muted mb-4">
                    Your payment has been cancelled. No charges were made to your account.
                </p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt text-success me-3"></i>
                            <div>
                                <h6 class="mb-0">Secure Process</h6>
                                <small class="text-muted">No data was stored</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-credit-card text-success me-3"></i>
                            <div>
                                <h6 class="mb-0">No Charges</h6>
                                <small class="text-muted">Your card was not charged</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-grid gap-2 d-md-block">
                    <a href="{{ route('jinah.payment.index') }}" class="btn btn-primary">
                        <i class="fas fa-redo me-2"></i>
                        Try Again
                    </a>
                    <a href="/" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>
                        Back to Home
                    </a>
                </div>
                
                <div class="mt-4 pt-4 border-top">
                    <p class="small text-muted mb-0">
                        Changed your mind? You can always come back and complete your purchase later.
                    </p>
                    <p class="small text-muted">
                        If you experienced any issues, please contact our support team at 
                        <a href="mailto:support@example.com">support@example.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>