<?php
// Dry-run normalized forgot_password.php (shortened)
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    if (!function_exists('asset')) {
        $helpers = __DIR__ . '/../../includes/helpers.php';
        if (file_exists($helpers)) require_once $helpers;
        if (!function_exists('asset')) { function asset($path) { return $path; } }
    }
    ?>
    <link rel="stylesheet" href="<?php echo asset('assets/css/unified-theme.css'); ?>">
</head>
<body>
    <form method="POST" action="<?php echo asset('forgot-password'); ?>"></form>
    <a href="<?php echo asset('login.php'); ?>">Login</a>
    <a href="<?php echo asset('register.php'); ?>">Register</a>
</body>
</html>
