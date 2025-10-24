<?php
// Dry-run normalized reset_password.php (shortened)
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <?php
    if (!function_exists('asset')) {
        require_once __DIR__ . '/../includes/helpers.php';
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="<?php echo asset('assets/css/unified-theme.css'); ?>">
</head>
<body>
    <form method="POST" action="<?php echo asset('reset-password'); ?>">
    </form>
    <a href="<?php echo asset('login.php'); ?>">Login</a>
</body>
</html>
<?php
// Dry-run copy: reset_password.php with public/ normalization
?>
<?php
// (content suppressed in dry-run listing) - actual replacement applied below when showing unified diff
?>