<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit;
}

// Handle account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    $user_id = $_SESSION['user_id'];

    // First, delete related data (e.g., images, favorites, forum posts)
    $db->begin_transaction();

    try {
        // Delete user's uploaded images
        $stmt = $db->prepare("DELETE FROM uploads WHERE uploaded_by = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete user's favorites
        $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete user's forum posts
        $stmt = $db->prepare("DELETE FROM forums WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Finally, delete the user account
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Commit transaction
        $db->commit();

        // Destroy session and log out the user
        session_destroy();

        // Redirect to goodbye page
        header("Location: goodbye");
        exit;
    } catch (Exception $e) {
        $db->rollback();
        echo "Error: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/css/styles.css">
    
    <title>Delete Account</title>
    <style>
        .delete-btn {
            background-color: red;
            color: white;
            padding: 10px;
            border: none;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .delete-btn.enabled {
            cursor: pointer;
            opacity: 1;
        }
    </style>
    </head>
<body onload="updateClock()">
<body>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
        <?php include_once '../includes/site-notice.php'; ?>

    <h2>Delete Your Account</h2>
<p>Warning: This action is permanent and cannot be undone.</p>

<form method="POST" action="">
    <?php echo csrf_input(); ?>
    <input type="checkbox" id="confirm" onchange="toggleDeleteButton()">
    <label for="confirm">I understand that deleting my account is irreversible.</label>
    <br><br>

    <button class="button" type="submit" name="confirm_delete" id="deleteButton" class="delete-btn" disabled>
        Delete My Account
    </button>
    <a href="/you/edit" class="button">Cancel</a>
</form>

<script>
    function toggleDeleteButton() {
        var checkbox = document.getElementById("confirm");
        var button = document.getElementById("deleteButton");

        if (checkbox.checked) {
            button.disabled = false;
            button.classList.add("enabled");
        } else {
            button.disabled = true;
            button.classList.remove("enabled");
        }
    }
</script>
<?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
