<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'moderator')) {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appeal_id = intval($_POST['appeal_id']);
    $decision = $_POST['decision']; // 'approved' or 'rejected'
    $review_notes = !empty($_POST['review_notes']) ? trim($_POST['review_notes']) : "No additional notes.";
    $moderator_id = $_SESSION['user_id'];

    // Fetch appeal details
    $query = "SELECT * FROM appeals WHERE id = ?";
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

    // Update appeal status
    $update_query = "UPDATE appeals SET appeal_status = ?, reviewed_by = ?, review_notes = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bind_param("sisi", $decision, $moderator_id, $review_notes, $appeal_id);
    $update_stmt->execute();

    // Update report status
    if ($decision === 'approved') {
        $report_update = "UPDATE reports SET status = 'pending', appeal_status = 'approved' WHERE id = ?";
    } else {
        $report_update = "UPDATE reports SET appeal_status = 'rejected' WHERE id = ?";
    }
    $stmt = $db->prepare($report_update);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    // Notify user about appeal decision
    $notif_message = "Your appeal for Report #$report_id has been $decision. Notes: $review_notes";
    $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bind_param("is", $user_id, $notif_message);
    $notif_stmt->execute();

    header("Location: review_appeals?success=Decision submitted.");
    exit();
}
?>