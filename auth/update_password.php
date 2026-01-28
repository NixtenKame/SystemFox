<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['reset_user_id'])) {
    die("Unauthorized request.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        die("Passwords do not match.");
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $userId = $_SESSION['reset_user_id'];

    // Update password
    $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $hashedPassword, $userId);
    $update->execute();

    // Delete the used token
    $delete = $db->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $delete->bind_param("i", $userId);
    $delete->execute();

    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_token']);

    echo "Password updated successfully.";
}
?>
