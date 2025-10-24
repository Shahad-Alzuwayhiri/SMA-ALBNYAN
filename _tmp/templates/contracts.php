<?php
// Dry-run normalized copy of templates/contracts.php
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts (dry-run)</title>
    <?php
    if (!function_exists('asset')) {
        $helpers = __DIR__ . '/../../includes/helpers.php';
        if (file_exists($helpers)) require_once $helpers;
        if (!function_exists('asset')) { function asset($path) { return $path; } }
    }
    ?>
    <link rel="stylesheet" href="<?php echo asset('assets/css/unified-theme.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <a href="<?php echo asset('dashboard.php'); ?>">Dashboard</a>
    <a href="<?php echo asset('contracts.php'); ?>">Contracts</a>
</body>
</html>
