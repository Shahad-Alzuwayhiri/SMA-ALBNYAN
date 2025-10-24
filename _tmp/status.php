<?php
// Dry-run normalized status.php
?>
<!DOCTYPE html>
<html>
<head>
    <?php if (!function_exists('asset')) { require_once __DIR__ . '/includes/helpers.php'; }
    ?>
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
</head>
<body>
    <a href="<?php echo asset('index.php'); ?>">Home</a>
    <a href="<?php echo asset('create_contract.php'); ?>">Create</a>
</body>
</html>
