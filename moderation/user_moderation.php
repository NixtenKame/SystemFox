<?php
// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

$conn = $db; // DB connection from config.php

// Check login and role
if (!isset($_SESSION['user_id']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'moderator')) {
    header("Location: /error/403");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'];

$error = '';
$success = '';

// Handle POST actions: ban/suspend/warn with reason
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['target_user_id'])) {
    $action = $_POST['action'];
    $targetUserId = intval($_POST['target_user_id']);
    $reason = trim($_POST['reason'] ?? '');

    // Get target user's role and status
    $stmt = $conn->prepare("SELECT user_role, status FROM users WHERE id = ?");
    $stmt->bind_param('i', $targetUserId);
    $stmt->execute();
    $stmt->bind_result($targetRole, $targetStatus);
    if (!$stmt->fetch()) {
        $error = "Target user not found.";
    }
    $stmt->close();

    // Role checks:
    if ($error === '') {
        if ($currentUserRole === 'moderator') {
            if ($targetRole === 'admin' || $targetRole === 'moderator') {
                $error = "Moderators cannot moderate admins or other moderators.";
            }
        } elseif ($currentUserRole === 'admin') {
            if ($targetUserId === $currentUserId) {
                $error = "You cannot moderate yourself.";
            }
        } else {
            $error = "You do not have permission to perform this action.";
        }
    }

    // Perform action if no errors
    if ($error === '') {
        if ($action === 'ban') {
            $stmt = $conn->prepare("UPDATE users SET status = 'banned', suspension_end = NULL, status_reason = ? WHERE id = ?");
            $stmt->bind_param('si', $reason, $targetUserId);
            $stmt->execute();
            $stmt->close();
            $success = "User banned successfully.";
        } elseif ($action === 'suspend' && !empty($_POST['suspension_days'])) {
            $days = intval($_POST['suspension_days']);
            if ($days < 1 || $days > 365) {
                $error = "Suspension days must be between 1 and 365.";
            } else {
                $suspendUntil = date('Y-m-d H:i:s', strtotime("+$days days"));
                $stmt = $conn->prepare("UPDATE users SET status = 'suspended', suspension_end = ?, status_reason = ? WHERE id = ?");
                $stmt->bind_param('ssi', $suspendUntil, $reason, $targetUserId);
                $stmt->execute();
                $stmt->close();
                $success = "User suspended for $days days.";
            }
        } elseif ($action === 'warn') {
            $stmt = $conn->prepare("UPDATE users SET status = 'warned', status_reason = ? WHERE id = ?");
            $stmt->bind_param('si', $reason, $targetUserId);
            $stmt->execute();
            $stmt->close();
            $success = "User warned successfully.";
        } elseif ($action === 'activate') {
            $stmt = $conn->prepare("UPDATE users SET status = 'active', suspension_end = NULL, status_reason = NULL WHERE id = ?");
            $stmt->bind_param('i', $targetUserId);
            $stmt->execute();
            $stmt->close();
            $success = "User status reset to active.";
        } else {
            $error = "Invalid action.";
        }
    }
}

// Fetch all users
$query = "SELECT id, username, user_role, status, suspension_end FROM users ORDER BY username ASC";
$result = $conn->query($query);
if (!$result) {
    die("Error fetching users: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Moderation Panel - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css" />
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            margin-bottom: 100px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .actions form {
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
        }
        .actions input[type=number], .actions input[type=text] {
            width: 60px;
            padding: 4px;
        }
        /* Dark theme styles */
        body.dark th {
            background-color: #444;
            color: #fff;
        }
        body.dark tr:nth-child(even) {
            background-color: #555;
        }
        body.dark tr:hover {
            background-color: #666;
        }
        body.dark td {
            background-color: #333;
            color: #fff;
        }
    </style>
    <script>
      // Add reason prompt on ban/suspend/warn buttons
      function handleModerationAction(form) {
        let action = form.action.value;
        let username = form.querySelector('button[type="submit"]').innerText;
        let promptMsg = "Please enter a reason for this " + action + " (optional):";
        let reason = prompt(promptMsg, "");
        if (reason === null) {
          // Cancel form submit if prompt cancelled
          return false;
        }
        // Create or update hidden input named "reason"
        let reasonInput = form.querySelector('input[name="reason"]');
        if (!reasonInput) {
          reasonInput = document.createElement('input');
          reasonInput.type = 'hidden';
          reasonInput.name = 'reason';
          form.appendChild(reasonInput);
        }
        reasonInput.value = reason.trim();
        return true; // allow submit
      }
    </script>
</head>
<body class="<?php echo htmlspecialchars($theme ?? ''); ?>">
    <nav>
        <?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <?php if ($error): ?>
        <div style="color: red; font-weight: bold;"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
        <div style="color: green; font-weight: bold;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Suspension Ends</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_role']); ?></td>
                    <td class="status-<?php echo htmlspecialchars($user['status']); ?>">
                        <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                    </td>
                    <td>
                        <?php 
                        if ($user['status'] === 'suspended' && $user['suspension_end']) {
                            echo htmlspecialchars(date('Y-m-d', strtotime($user['suspension_end'])));
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="actions">
                        <?php
                        if ($user['id'] === $currentUserId) {
                            echo '<em>Self</em>';
                        } else {
                            $canModerate = false;
                            if ($currentUserRole === 'admin') {
                                $canModerate = true;
                            } elseif ($currentUserRole === 'moderator') {
                                if (!in_array($user['user_role'], ['admin', 'moderator'])) {
                                    $canModerate = true;
                                }
                            }

                            if ($canModerate):
                        ?>
                            <form method="POST" onsubmit="return handleModerationAction(this);" >
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="action" value="ban" />
                                <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>" />
                                <button type="submit" <?php echo ($user['status'] === 'banned') ? 'disabled' : ''; ?>>Ban</button>
                            </form>

                            <form method="POST" onsubmit="return handleModerationAction(this);" >
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="action" value="suspend" />
                                <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>" />
                                <input type="number" name="suspension_days" min="1" max="365" placeholder="Days" required <?php echo ($user['status'] === 'suspended') ? 'disabled' : ''; ?> />
                                <button type="submit" <?php echo ($user['status'] === 'suspended') ? 'disabled' : ''; ?>>Suspend</button>
                            </form>

                            <form method="POST" onsubmit="return handleModerationAction(this);" >
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="action" value="warn" />
                                <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>" />
                                <button type="submit" <?php echo ($user['status'] === 'warned') ? 'disabled' : ''; ?>>Warn</button>
                            </form>

                            <?php if ($user['status'] !== 'active'): ?>
                            <form method="POST" onsubmit="return confirm('Reset user <?php echo htmlspecialchars($user['username']); ?> to active?');" style="display:inline-block;">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="action" value="activate" />
                                <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>" />
                                <button type="submit">Reset</button>
                            </form>
                            <?php endif; ?>

                        <?php else: ?>
                            <em>No permission</em>
                        <?php
                            endif;
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php include('../includes/version.php'); ?>
    <footer>
    <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>