<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
require_once('../includes/header.php');

// Check if the user is a moderator or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'moderator' && $_SESSION['user_role'] !== 'admin')) {
    die("Access Denied: You must be a moderator or admin to review appeals.");
}

// If the form is submitted to approve/reject an appeal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appeal_id'], $_POST['decision'])) {
    $appeal_id = intval($_POST['appeal_id']);
    $decision = ($_POST['decision'] === 'approve') ? 'approved' : 'rejected';

    // Get the appeal details
    $query = "SELECT report_id, user_id FROM appeals WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $appeal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appeal = $result->fetch_assoc();

    // Get the ban appeal details
    $query = "SELECT id, user_id, appeal_reason, created_at, status FROM ban_appeals WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $appeal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appeal = $result->fetch_assoc();

    if (!$appeal) {
        die("Error: Appeal not found.");
    }

    $report_id = $appeal['report_id'];
    $user_id = $appeal['user_id'];

    // Update the appeal status in the database
    $update_appeal_query = "UPDATE appeals SET appeal_status = ? WHERE id = ?";
    $update_appeal_stmt = $db->prepare($update_appeal_query);
    $update_appeal_stmt->bind_param("si", $decision, $appeal_id);
    $update_appeal_stmt->execute();

    // Update the report's appeal status based on decision
    $new_report_status = ($decision === 'approved') ? 'resolved' : 'rejected';
    $update_report_query = "UPDATE reports SET appeal_status = ?, status = ? WHERE id = ?";
    $update_report_stmt = $db->prepare($update_report_query);
    $update_report_stmt->bind_param("ssi", $decision, $new_report_status, $report_id);
    $update_report_stmt->execute();

    // Update the report's ban appeal status based on decision
    $update_ban_appeal_query = "UPDATE ban_appeals SET status = ? WHERE id = ?";
    $update_ban_appeal_stmt = $db->prepare($update_ban_appeal_query);
    $update_ban_appeal_stmt->bind_param("si", $decision, $appeal_id);
    $update_ban_appeal_stmt->execute();

    // Update users status if ban appeal is approved
    if ($decision === 'approved') {
        $update_user_query = "UPDATE users SET status = 'active' WHERE id = ?";
        $update_user_stmt = $db->prepare($update_user_query);
        $update_user_stmt->bind_param("i", $user_id);
        $update_user_stmt->execute();
    }

    // Notify the user about the decision
    $notif_message = "Your appeal for Report #$report_id has been $decision.";
    $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bind_param("is", $user_id, $notif_message);
    $notif_stmt->execute();

    header("Location: review_appeals?success=Appeal decision submitted successfully.");
    exit();
}

// Fetch all pending appeals
$fetch_appeals_query = "SELECT appeals.id, appeals.report_id, appeals.user_id, appeals.appeal_text, reports.status 
                        FROM appeals 
                        JOIN reports ON appeals.report_id = reports.id
                        WHERE appeals.appeal_status = 'pending'";
$appeals_result = $db->query($fetch_appeals_query);

// Fetch all pending ban appeals
$fetch_ban_appeals_query = "SELECT id, user_id, appeal_reason, created_at, status 
                            FROM ban_appeals 
                            WHERE status = 'pending'";
$ban_appeals_result = $db->query($fetch_ban_appeals_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Report</title>
    <link rel="stylesheet" href="/public/css/styles.css">
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <h2>Review Appeals</h2>
    <?php if (isset($_GET['success'])): ?>
        <p style="color: green;"><?= htmlspecialchars($_GET['success']) ?></p>
    <?php endif; ?>

    <?php while ($appeal = $appeals_result->fetch_assoc()): ?>
        <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
            <p><strong>Report ID:</strong> <?= htmlspecialchars($appeal['report_id']) ?></p>
            <p><strong>User ID:</strong> <?= htmlspecialchars($appeal['user_id']) ?></p>
            <p><strong>Appeal Reason:</strong> <?= htmlspecialchars($appeal['appeal_text']) ?></p>
            <p><strong>Report Status:</strong> <?= htmlspecialchars($appeal['status']) ?></p>
            
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="appeal_id" value="<?= $appeal['id'] ?>">
                <button type="submit" name="decision" value="approve">Approve Appeal</button>
                <button type="submit" name="decision" value="reject">Reject Appeal</button>
            </form>
        </div>
    <?php endwhile; ?>
    <?php while ($ban_appeal = $ban_appeals_result->fetch_assoc()): ?>
        <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
            <p><strong>Appeal ID:</strong> <?php echo htmlspecialchars($ban_appeal['id']); ?></p>
            <p><strong>User ID:</strong> <?php echo htmlspecialchars($ban_appeal['user_id']); ?></p>
            <p><strong>Appeal Reason:</strong> <?php echo htmlspecialchars($ban_appeal['appeal_reason']); ?></p>
            <p><strong>Submitted At:</strong> <?php echo htmlspecialchars($ban_appeal['created_at']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($ban_appeal['status']); ?></p>
            
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="appeal_id" value="<?php echo $ban_appeal['id']; ?>">
                <button type="submit" name="decision" value="approve">Approve Appeal</button>
                <button type="submit" name="decision" value="reject">Reject Appeal</button>
            </form>
        </div>
    <?php endwhile; ?>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
