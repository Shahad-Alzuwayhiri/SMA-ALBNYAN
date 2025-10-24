<?php
/**
 * Shared Contract Header Component (fragment)
 * This file should NOT emit a DOCTYPE or <html>/<head> tags. Those belong in the master layout.
 * Include this file after the master layout has emitted the initial HTML and <head>.
 */

// Defensive: do not output anything from this fragment. Only provide styles/scripts expected to be
// inserted into the <head> when the layout includes it via $additional_head or similar.
?>
<!-- Bootstrap 5 RTL -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- Cairo Font -->
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
        :root {
            --sma-gold: #D4AF37;
            --sma-dark-gold: #B8941F;
            --sma-light-gold: #F4E6A1;
            --sma-gray: #2C3E50;
            --sma-light-gray: #34495E;
            --sma-bg-light: #F8F9FA;
            --sma-success: #27AE60;
            --sma-warning: #F39C12;
            --sma-danger: #E74C3C;
            --sma-info: #3498DB;
        }

        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--sma-bg-light) 0%, #FFFFFF 100%);
            min-height: 100vh;
        }

        /* Header Styling */
        .sma-header {
            background: linear-gradient(135deg, var(--sma-gray) 0%, var(--sma-light-gray) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.1);
        }

        .sma-header h1 {
            color: var(--sma-gold);
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .sma-header .subtitle {
            color: #BDC3C7;
            font-size: 1.1rem;
        }

        /* Card Styling */
        .sma-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .sma-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }

        .sma-card-header {
            background: linear-gradient(135deg, var(--sma-gold) 0%, var(--sma-dark-gold) 100%);
            color: white;
            padding: 1.5rem;
            border: none;
            font-weight: 600;
        }

        .sma-card-body {
            padding: 2rem;
        }

        /* Button Styling */
        .sma-btn-primary {
            background: linear-gradient(135deg, var(--sma-gold) 0%, var(--sma-dark-gold) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sma-btn-primary:hover {
            background: linear-gradient(135deg, var(--sma-dark-gold) 0%, var(--sma-gold) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
            color: white;
        }

        .sma-btn-secondary {
            background: var(--sma-gray);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sma-btn-secondary:hover {
            background: var(--sma-light-gray);
            transform: translateY(-2px);
            color: white;
        }

        /* Form Styling */
        .form-control, .form-select {
            border: 2px solid #E8F4FD;
            border-radius: 8px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--sma-gold);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--sma-gray);
            margin-bottom: 0.8rem;
        }

        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--sma-success);
            border-left: 4px solid var(--sma-success);
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--sma-danger);
            border-left: 4px solid var(--sma-danger);
        }

        .alert-warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--sma-warning);
            border-left: 4px solid var(--sma-warning);
        }

        .alert-info {
            background: rgba(52, 152, 219, 0.1);
            color: var(--sma-info);
            border-left: 4px solid var(--sma-info);
        }

        /* Status Badges */
        .status-draft { 
            background: var(--sma-warning) !important; 
            color: white !important; 
        }
        .status-pending_review { 
            background: var(--sma-info) !important; 
            color: white !important; 
        }
        .status-approved { 
            background: var(--sma-success) !important; 
            color: white !important; 
        }
        .status-rejected { 
            background: var(--sma-danger) !important; 
            color: white !important; 
        }
        .status-signed { 
            background: var(--sma-gold) !important; 
            color: white !important; 
        }

        /* Table Styling */
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: var(--sma-gray);
            color: white;
            border: none;
            padding: 1.2rem;
            font-weight: 600;
        }

        .table tbody td {
            padding: 1rem 1.2rem;
            border-color: #F8F9FA;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Company Watermark */
        .company-watermark {
            position: fixed;
            bottom: 20px;
            right: 20px;
            opacity: 0.1;
            z-index: -1;
            pointer-events: none;
        }

        /* Loading Spinner */
        .sma-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sma-header {
                padding: 1.5rem 0;
            }
            
            .sma-card-body {
                padding: 1.5rem;
            }
            
            .sma-btn-primary, .sma-btn-secondary {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .table-responsive {
                border-radius: 10px;
            }
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .sma-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
    
    <?php if (isset($additionalStyles)): ?>
        <style><?= $additionalStyles ?></style>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation Bar -->
    <?php if (!isset($hideNavbar) || !$hideNavbar): ?>
        <?php include '../includes/navbar.php'; ?>
    <?php endif; ?>
    
    <!-- Page Header -->
    <?php if (isset($pageTitle) && isset($pageDescription)): ?>
    <div class="sma-header no-print">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>
                        <?php if (isset($pageIcon)): ?>
                            <i class="<?= $pageIcon ?> me-3"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($pageTitle) ?>
                    </h1>
                    <p class="subtitle mb-0"><?= htmlspecialchars($pageDescription) ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (isset($headerActions)): ?>
                        <?= $headerActions ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content Container -->
    <div class="container <?= isset($containerClass) ? $containerClass : '' ?>">