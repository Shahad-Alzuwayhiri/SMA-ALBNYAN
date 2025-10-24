<?php
// Dry-run copy of master_layout.php - public/ normalization applied
?>
<?php
/* original header preserved; only asset('public/...') occurrences replaced */
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Layout (dry-run)</title>
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
Master layout (dry-run)
</body>
</html>
