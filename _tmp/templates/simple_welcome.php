<?php
// Dry-run normalized simple_welcome.php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php
    if (!function_exists('asset')) {
        $helpers = __DIR__ . '/../includes/helpers.php';
        if (file_exists($helpers)) require_once $helpers;
        if (!function_exists('asset')) { function asset($path) { return $path; } }
    }
    ?>
    <link rel="stylesheet" href="<?php echo asset('assets/css/unified-theme.css'); ?>">
</head>
<body>
Welcome (dry-run)
</body>
</html>
