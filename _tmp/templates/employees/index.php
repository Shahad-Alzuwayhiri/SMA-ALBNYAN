<?php
// Dry-run copy of templates/employees/index.php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
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
Employees (dry-run)
</body>
</html>
