<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Set a page title dynamically
$pageTitle = 'Good Bye';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <main>
        <h1>Sorry to see you go.</h1>
        <p>You can always recreate your account but if you recreate your account all posts and comments you made is gone forever. If you would like to recreate your account then head to the <a href="/register">registration page</a> to recreate your account. If you would like to fill out a form about how you felt with your experience with FluffFox then email me at <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a> Thank you for your support with FluffFox have a wonderful day.</p>
    </main>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
