<?php
include_once('../includes/config.php');
include_once('../includes/header.php'); // Include common header

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Capture the error details if available
$errorDetails = isset($_SESSION['error_details']) ? $_SESSION['error_details'] : 'No additional error details available.';

// Clear the error details from the session
unset($_SESSION['error_details']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Internal Server Error - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #d9534f;
        }
        h2 {
            color: #5bc0de;
        }
        p {
            line-height: 1.6;
        }
        .error-details {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            border: 1px solid #ebccd1;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<nav>
<?php include_once 'E:\yifffox\yiff-fox Root Project Directory/includes/nav.php'; ?>
    </nav>
    <?php include_once 'E:\yifffox\yiff-fox Root Project Directory\includes\site-notice.php'; ?>
    
    <div class="container">
        <h1>500 Internal Server Error</h1>
        <p>We're sorry, but something went wrong on our end. Please try again later.</p>
        <h2>Possible Issues:</h2>
        <ul>
            <li><strong>MySQL Issues:</strong>
                <ul>
                    <li><p>Database connection failure</p></li>
                    <li>Incorrect database credentials</li>
                    <li>Database server is down</li>
                    <li>SQL syntax errors</li>
                    <li>Exceeding database query limits</li>
                </ul>
            </li>
            <li><strong>PHP Issues:</strong>
                <ul>
                    <li>Syntax errors in the PHP code</li>
                    <li>Undefined variables or functions</li>
                    <li>Memory limit exceeded</li>
                    <li>File inclusion errors (e.g., missing files)</li>
                    <li>Permission issues</li>
                </ul>
            </li>
            <li><strong>Server Configuration Issues:</strong>
                <ul>
                    <li>Incorrect server configuration</li>
                    <li>Missing or incorrect .htaccess rules</li>
                    <li>Server resource limits exceeded</li>
                </ul>
            </li>
        </ul>
        <div class="error-details">
            <h3>Error Details:</h3>
            <p><?php echo nl2br(htmlspecialchars($errorDetails)); ?></p>
        </div>
        <p>If the problem persists, please contact our support team.</p>
    </div>
</body>
</html>