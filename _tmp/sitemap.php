<?php
// Dry-run normalized sitemap.php
?>
<!DOCTYPE html>
<html>
<head>
    <?php if (!function_exists('asset')) { require_once __DIR__ . '/includes/helpers.php'; }
    ?>
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
</head>
<body>
    <a href="<?php echo asset('contract_view.php'); ?>" class="btn btn-info">Contract view</a>
    <a href="<?php echo asset('manager_dashboard.php'); ?>">Manager</a>
    <a href="<?php echo asset('employee_dashboard.php'); ?>">Employee</a>
</body>
</html>
