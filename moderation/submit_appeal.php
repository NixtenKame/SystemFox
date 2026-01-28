<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $report_id = intval($_POST['report_id']);
    $appeal_text = trim($_POST['appeal_text']);

    if (empty($report_id) || empty($appeal_text)) {
        die("Error: Report ID and appeal reason cannot be empty.");
    }

    // Check if the report exists and is appealable
    $stmt = $db->prepare("SELECT id FROM reports WHERE id = ? AND reported_user_id = ? AND status = 'resolved' AND appeal_status = 'none'");
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Error: Report not found or cannot be appealed.");
    }

    // Insert appeal
    $stmt = $db->prepare("INSERT INTO appeals (report_id, user_id, appeal_text, appeal_status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iis", $report_id, $user_id, $appeal_text);
    $stmt->execute();

    echo "Appeal submitted successfully.";
}
?>
