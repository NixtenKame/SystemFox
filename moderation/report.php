<?php
//detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to report a user.");
}

// Validate and sanitize input
$content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
$content_type = isset($_POST['content_type']) ? htmlspecialchars($_POST['content_type']) : '';
$reason = isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '';
$details = isset($_POST['details']) ? trim(htmlspecialchars($_POST['details'])) : '';

// Check required fields
if ($content_id === 0 || empty($reason)) {
    die("Error: Missing required fields.");
}

// Get the ID of the user submitting the report
$reporting_user_id = $_SESSION['user_id'];

// Insert report into the database
$query = "INSERT INTO reports (reported_user_id, reporting_user_id, content_type, reason, details, report_date) 
          VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $db->prepare($query);
$stmt->bind_param("iisss", $content_id, $reporting_user_id, $content_type, $reason, $details);

if ($stmt->execute()) {
    echo "Report submitted successfully.";

    // Notify the reporting user
    $notif_message = "Your report has been submitted and is pending review.";
    $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bind_param("is", $reporting_user_id, $notif_message);
    $notif_stmt->execute();
    $notif_stmt->close();
} else {
    echo "Error submitting report: " . $stmt->error;
}

$stmt->close();
$db->close();
?>