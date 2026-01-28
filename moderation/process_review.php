<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

// Ensure user is a moderator or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'moderator')) {
    die("Access Denied: Insufficient permissions.");
}

// Check if POST data is set
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['report_id']) || !isset($_POST['decision'])) {
        die("Error: Missing report ID or decision.");
    }

    // Sanitize inputs
    $report_id = intval($_POST['report_id']);
    $decision = $_POST['decision'];
    $additional_notes = trim($_POST['additional_notes'] ?? ''); // Optional notes

    // Validate the decision
    $valid_decisions = ['approved', 'rejected', 'warning', 'banned'];
    if (!in_array($decision, $valid_decisions)) {
        die("Error: Invalid decision.");
    }

    // Fetch the reported user ID and reporting user ID from the report
    $user_query = "SELECT reported_user_id, reporting_user_id FROM reports WHERE id = ?";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bind_param("i", $report_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $report_data = $user_result->fetch_assoc();

    if (!$report_data) {
        die("Error: Report not found.");
    }

    $reported_user_id = $report_data['reported_user_id'];
    $reporting_user_id = $report_data['reporting_user_id'];

    // Determine new report status
    $new_report_status = ($decision === 'approved') ? 'resolved' : 'rejected';

    // Update the report status
    $stmt = $db->prepare("UPDATE reports SET status = ?, review_notes = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_report_status, $additional_notes, $report_id);
    $stmt->execute();

    // Handle user actions (warnings, bans)
    if ($decision === 'warning' || $decision === 'banned') {
        $user_status = ($decision === 'banned') ? 'banned' : 'warned';

        // Update the user's status in the database
        $update_user_stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $update_user_stmt->bind_param("si", $user_status, $reported_user_id);
        $update_user_stmt->execute();
    }

    // Notify the reporting user
    $notif_message = match ($decision) {
        'approved' => "Your report #$report_id has been approved and resolved.",
        'rejected' => "Your report #$report_id has been reviewed and rejected.",
        'warning'  => "You have received a warning due to a report (#$report_id).",
        'banned'   => "Your account has been banned due to a report (#$report_id)."
    };

    $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notif_stmt->bind_param("is", $reporting_user_id, $notif_message);
    $notif_stmt->execute();

    header("Location: moderation?success=Decision submitted.");
    exit();
}
?>