<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to reset your password.");
}

$message = "";
$error = "";

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Fetch user data
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    $stmt->fetch();
    $stmt->close();

    // Verify current password
    if (!password_verify($currentPassword, $storedPassword)) {
        $error = "Incorrect current password!";
    } 
    // Validate new password
    elseif (strlen($newPassword) < 8 || 
            !preg_match('/[0-9]/', $newPassword) || 
            !preg_match('/[A-Z]/', $newPassword) || 
            !preg_match('/[\W]/', $newPassword)) {
        $error = "New password must be at least 8 characters long and include a number, uppercase letter, and special character.";
    } 
    // Check if new password matches confirmation
    elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match!";
    } 
    else {
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password in database
        $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        $updateStmt->execute();
        $updateStmt->close();

        $message = "Password successfully updated!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/css/styles.css">
    
    <title>Reset Password</title>
    </head>
<body onload="updateClock()">
<body>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <body>

<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($message): ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<div class="login">
<form action="/auth/reset_password" method="POST" onsubmit="return confirm('Are you sure you want to change your password?');">
    <?php echo csrf_input(); ?>
    <label>Current Password:</label>
    <input type="password" name="current_password" required>

    <label>New Password:</label>
    <input type="password" name="new_password" required>

    <label>Confirm New Password:</label>
    <input type="password" name="confirm_password" required>

    <button type="submit">Reset Password</button>
    <button type="button" onclick="window.location.href='/auth/forgot_password'">Forgot your current password.</button>
    <button type="button" onclick="window.location.href='/you/edit'">Cancel</button>
</form>
</div>
<br>
<br>
<br>
<br>
<?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
