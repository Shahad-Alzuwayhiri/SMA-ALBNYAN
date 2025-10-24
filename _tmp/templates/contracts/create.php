<?php
// Dry-run normalized copy of templates/contracts/create.php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Contract (dry-run)</title>
    <?php
    if (!function_exists('asset')) {
        $helpers = __DIR__ . '/../../includes/helpers.php';
        if (file_exists($helpers)) require_once $helpers;
        if (!function_exists('asset')) { function asset($path) { return $path; } }
    }
    ?>
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
</head>
<body>
    <a class="navbar-brand" href="<?php echo asset('index.php'); ?>">
        <img src="<?php echo asset('assets/images/sma-logo.svg'); ?>" alt="logo">
    </a>
</body>
</html>
