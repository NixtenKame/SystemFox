<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to access this page.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - Reporting and Appeals</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <style>
        .help-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .help-container h2 {
            margin-bottom: 20px;
        }
        .help-container ul {
            list-style-type: none;
            padding: 0;
        }
        .help-container ul li {
            margin: 10px 0;
        }
        .help-container ul li a {
            color: #007bff;
            text-decoration: none;
        }
        .help-container ul li a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <div class="help-container">
        <h2>Reporting and Appeals</h2>
        <p>If you encounter any issues or need to report a user, you can use the following links:</p>
        <ul>
            <li><a href="/moderation/report">Report a User</a></li>
            <li><a href="/moderation/appeal">Appeal a Report</a></li>
        </ul>
    </div>
    <br>
    <br>
    <br>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>