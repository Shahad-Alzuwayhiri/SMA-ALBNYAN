<?php
// Dry-run normalized diagnostic.php
?>
<!DOCTYPE html>
<html>
<head>
    <?php if (!function_exists('asset')) { require_once __DIR__ . '/includes/helpers.php'; }
    ?>
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
</head>
<body>
    <a href="<?php echo asset(''); ?>">Public root</a>
    <a href="<?php echo asset('index.php'); ?>">Front controller</a>
</body>
</html>
