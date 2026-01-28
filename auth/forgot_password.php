<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Set a page title dynamically
$pageTitle = 'Forgot Password?';
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
        <h1>Forgot your password?</h1>
        <p>Don't worry! We can help you reset your password. Please click the button bellow to reset your password. If you would like to reset the password via the security questions click the "Reset Via Questions" button bellow.</p>
        <p>You will have to fill out a email stating that you forgot your password and you would like to reset it. You will have to provide your username and email address that you used to sign up with. If you do not provide the correct information then you will not be able to reset your password. Make sure that the email you used to sign up to the site is the same email that you will be using to email the message you will be sending.</p>
        <a class="button" href="mailto:nixtenkame@gmail.com">Reset Password</a>
        <button class="button" onclick="window.history.back()">I remembered my password</button>
        <a class="button" href="/auth/forgot_password_auth">Reset Via Questions</a>
        <p>If you have any questions or concerns include them in the email</p>
	<br>
	<br>
	<br>
	<br>
    </main>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
    
</body>
</html>
