<?php

define('ROOT_PATH', realpath(__DIR__ . '/../../'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Check if database connection exists
if (!isset($db)) {
    die("Database connection error.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Access Denied: You must be logged in to appeal a report.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $report_id = intval($_POST['report_id']);
    $appeal_text = trim($_POST['appeal_text']);

    if (empty($report_id) || $report_id <= 0 || empty($appeal_text)) {
        die("Error: Report ID must be a positive number and appeal reason cannot be empty.");
    }

    // Check if the report exists, belongs to the user, and is resolved
    $query = "SELECT id, status, appeal_status FROM reports WHERE id = ? AND reported_user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();

    if (!$report) {
        die("Error: Report not found.");
    }

    if ($report['status'] !== 'resolved') {
        die("Error: Report is not resolved. Current status: " . htmlspecialchars($report['status']));
    }

    if ($report['appeal_status'] !== 'none') {
        die("Error: Report has already been appealed. Current appeal status: " . htmlspecialchars($report['appeal_status']));
    }

    // Insert appeal into the database
    $insert_query = "INSERT INTO appeals (report_id, user_id, appeal_text, appeal_status) VALUES (?, ?, ?, 'pending')";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bind_param("iis", $report_id, $user_id, $appeal_text);
    $insert_stmt->execute();

    // Update report status to indicate an appeal is pending
    $update_query = "UPDATE reports SET appeal_status = 'pending' WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bind_param("i", $report_id);
    $update_stmt->execute();

    // Notify moderators
    $mod_notif_message = "A new appeal has been submitted for Report #$report_id.";
    $mod_notif_query = "INSERT INTO notifications (user_id, message) 
                        SELECT id, ? FROM users WHERE user_role IN ('admin', 'moderator')";
    $mod_notif_stmt = $db->prepare($mod_notif_query);
    $mod_notif_stmt->bind_param("s", $mod_notif_message);
    $mod_notif_stmt->execute();

    header("Location: /moderation/appeal?success=Appeal submitted successfully.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appeal Report</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .form-container h2 {
            margin-bottom: 20px;
        }
        .form-container label {
            display: block;
            margin: 10px 0 5px;
        }
        .form-container input, .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-container button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .success-message {
            color: green;
            margin-bottom: 20px;
        }
        .error-message {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <div class="form-container">
        <h2>Submit Appeal</h2>
        <?php if (isset($_GET['success'])): ?>
            <p class="success-message"><?= htmlspecialchars($_GET['success']) ?></p>
        <?php endif; ?>
        <form method="POST" id="appeal-form">
            <?php echo csrf_input(); ?>
            <label for="report_id">Report ID:</label>
            <input type="number" name="report_id" id="report_id" min="1" required>
            <label for="appeal_text">Reason for Appeal:</label>
            <textarea name="appeal_text" id="appeal_text" required placeholder="Explain why you are appealing this decision..."></textarea>
            <button class="button" type="submit">Submit Appeal</button>
        </form>
    </div>
    <br>
    <br>
    <br>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
    <script>
        document.getElementById('appeal-form').addEventListener('submit', function(event) {
            var reportId = document.getElementById('report_id').value;
            var appealText = document.getElementById('appeal_text').value;

            if (!reportId || !appealText.trim() || reportId <= 0) {
                event.preventDefault();
                alert('Please fill in all required fields with valid values.');
            }
        });
    </script>
</body>
</html>