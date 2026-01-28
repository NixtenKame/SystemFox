<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: /auth/forgot_password_auth");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    $userId = $_SESSION['reset_user_id'];

    $query = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $query->bind_param("si", $newPassword, $userId);
    $query->execute();

    unset($_SESSION['reset_user_id']);
    header("Location: /login?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<nav>
<?php include_once '/includes/nav.php'; ?>
    </nav>
    <?php include_once '/includes/site-notice.php'; ?>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <form method="POST" action="">
            <?php echo csrf_input(); ?>
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>

            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
<?php include('/includes/version.php'); ?>
<footer>
<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
</footer>
</html>