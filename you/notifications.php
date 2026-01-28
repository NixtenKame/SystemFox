<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Detailed errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

// Generate a CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// Fetch active notifications for the user
$query = "SELECT id, message, status, created_at FROM notifications WHERE user_id = ? AND dismissed = 0 ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Fetch dismissed notifications for the user
$query = "SELECT id, message, status, created_at FROM notifications WHERE user_id = ? AND dismissed = 1 ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$dismissed_notifications = [];
while ($row = $result->fetch_assoc()) {
    $dismissed_notifications[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/public/css/styles.css" />
    <title>Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .notification-container { width: 50%; margin: auto; }
        .notification {
            padding: 10px; margin: 5px 0; border-radius: 5px; background-color: #f9f9f9;
            border-left: 5px solid #007bff; display: flex; justify-content: space-between; align-items: center;
        }
        .notification.unread {
            font-weight: bold; background-color: #e3f2fd;
        }
        .timestamp { font-size: 12px; color: gray; }
        .dismiss-btn, .unread-btn, .delete-btn {
            padding: 5px 10px; cursor: pointer; border-radius: 3px; border: none; color: white;
        }
        .dismiss-btn { background-color: #ff4d4d; }
        .dismiss-btn:hover { background-color: #cc0000; }
        .unread-btn { background-color: #4CAF50; }
        .unread-btn:hover { background-color: #388E3C; }
        .delete-btn { background-color: #ff4d4d; }
        .delete-btn:hover { background-color: #cc0000; }
        .dismissed-notifications { margin-top: 20px; }
    </style>
</head>
<body>
<nav>
<?php include_once '../includes/nav.php'; ?>
</nav>
<?php include_once '../includes/site-notice.php'; ?>

<div class="notification-container">
    <h2>Your Notifications</h2>
    <?php if (empty($notifications)) : ?>
        <p>No notifications yet.</p>
    <?php else : ?>
        <?php foreach ($notifications as $notification) : ?>
            <div class="notification <?= $notification['status'] ? '' : 'unread' ?>" id="notification-<?= htmlspecialchars($notification['id']) ?>">
                <div>
                    <p><?= htmlspecialchars($notification['message']) ?></p>
                    <p class="timestamp"><?= htmlspecialchars($notification['created_at']) ?></p>
                </div>
                <div>
                    <button class="dismiss-btn" data-id="<?= htmlspecialchars($notification['id']) ?>">Dismiss</button>
                    <button class="delete-btn" data-id="<?= htmlspecialchars($notification['id']) ?>">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="dismissed-notifications">
        <h2>Dismissed Notifications</h2>
        <?php if (empty($dismissed_notifications)) : ?>
            <p>No dismissed notifications.</p>
        <?php else : ?>
            <?php foreach ($dismissed_notifications as $notification) : ?>
                <div class="notification <?= $notification['status'] ? '' : 'unread' ?>" id="notification-<?= htmlspecialchars($notification['id']) ?>">
                    <div>
                        <p><?= htmlspecialchars($notification['message']) ?></p>
                        <p class="timestamp"><?= htmlspecialchars($notification['created_at']) ?></p>
                    </div>
                    <div>
                        <button class="unread-btn" data-id="<?= htmlspecialchars($notification['id']) ?>">Unread</button>
                        <button class="delete-btn" data-id="<?= htmlspecialchars($notification['id']) ?>">Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<br><br><br><br>

<?php include('../includes/version.php'); ?>
<footer>
<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?= htmlspecialchars($version); ?></a></p>
</footer>

<script>
// Pass the CSRF token from PHP to JS
const csrfToken = <?= json_encode($_SESSION['csrf_token']); ?>;

document.addEventListener("DOMContentLoaded", function () {

    function removeNotification(id) {
        const el = document.getElementById(`notification-${id}`);
        if (el) el.remove();
    }

    // Move a dismissed notification from active list to dismissed list
    function moveToDismissed(notificationElement) {
        // Remove from active container
        notificationElement.remove();

        // Remove buttons except "Unread" and "Delete"
        const btnContainer = notificationElement.querySelector('div:last-child');
        btnContainer.innerHTML = `
            <button class="unread-btn" data-id="${notificationElement.id.replace('notification-', '')}">Unread</button>
            <button class="delete-btn" data-id="${notificationElement.id.replace('notification-', '')}">Delete</button>
        `;

        // Append to dismissed container
        const dismissedContainer = document.querySelector('.dismissed-notifications');
        dismissedContainer.appendChild(notificationElement);

        // Re-bind event listeners for new buttons
        bindUnreadButtons();
        bindDeleteButtons();
    }

    // Move an unread notification from dismissed list to active list
    function moveToActive(notificationElement) {
        // Remove from dismissed container
        notificationElement.remove();

        // Update notification class to unread
        notificationElement.classList.add('unread');

        // Change buttons to Dismiss and Delete
        const btnContainer = notificationElement.querySelector('div:last-child');
        btnContainer.innerHTML = `
            <button class="dismiss-btn" data-id="${notificationElement.id.replace('notification-', '')}">Dismiss</button>
            <button class="delete-btn" data-id="${notificationElement.id.replace('notification-', '')}">Delete</button>
        `;

        // Append to active container (above dismissed)
        const activeContainer = document.querySelector('.notification-container > h2 + div') || document.querySelector('.notification-container');
        activeContainer.insertBefore(notificationElement, activeContainer.firstChild);

        // Re-bind event listeners for new buttons
        bindDismissButtons();
        bindDeleteButtons();
    }

    function bindDismissButtons() {
        document.querySelectorAll(".dismiss-btn").forEach(button => {
            button.onclick = function () {
                const id = this.dataset.id;
                fetch("/includes/dismiss_notification", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `notification_id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const notifEl = document.getElementById(`notification-${id}`);
                        if (notifEl) {
                            // Remove unread class if any
                            notifEl.classList.remove('unread');
                            moveToDismissed(notifEl);
                        }
                    } else {
                        alert("Error dismissing notification.");
                    }
                })
                .catch(() => alert("Network error while dismissing notification."));
            };
        });
    }

    function bindUnreadButtons() {
        document.querySelectorAll(".unread-btn").forEach(button => {
            button.onclick = function () {
                const id = this.dataset.id;
                fetch("/includes/unread_notification", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `notification_id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const notifEl = document.getElementById(`notification-${id}`);
                        if (notifEl) {
                            notifEl.classList.add('unread');
                            moveToActive(notifEl);
                        }
                    } else {
                        alert("Error marking notification as unread.");
                    }
                })
                .catch(() => alert("Network error while marking notification as unread."));
            };
        });
    }

    function bindDeleteButtons() {
        document.querySelectorAll(".delete-btn").forEach(button => {
            button.onclick = function () {
                const id = this.dataset.id;
                if (!confirm("Are you sure you want to delete this notification?")) return;

                fetch("/includes/delete_notification", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `notification_id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        removeNotification(id);
                    } else {
                        alert("Error deleting notification.");
                    }
                })
                .catch(() => alert("Network error while deleting notification."));
            };
        });
    }

    // Initial binding
    bindDismissButtons();
    bindUnreadButtons();
    bindDeleteButtons();

});
</script>
</body>
</html>