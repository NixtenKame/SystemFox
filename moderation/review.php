<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Check if user is logged in and is a moderator or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'moderator')) {
    die("Access Denied: You do not have permission to view this page.");
}

// Check if a report ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid report ID.");
}

$report_id = intval($_GET['id']); // Sanitize input

// Fetch report details
$query = "SELECT * FROM reports WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    die("Error: Report not found.");
}

// Fetch reported user's details (assuming user table exists)
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->bind_param("i", $report['reported_user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$reported_user = $user_result->fetch_assoc();

// Fetch reporting user's details
$reporting_user_query = "SELECT * FROM users WHERE id = ?";
$reporting_user_stmt = $db->prepare($reporting_user_query);
$reporting_user_stmt->bind_param("i", $report['reporting_user_id']);
$reporting_user_stmt->execute();
$reporting_user_result = $reporting_user_stmt->get_result();
$reporting_user = $reporting_user_result->fetch_assoc();

// Ensure the user exists before trying to access their data
$reported_username = $reported_user ? htmlspecialchars($reported_user['username']) : "Unknown User";
$reported_user_id = $reported_user ? $reported_user['id'] : "N/A";

// Ensure the reporting user exists before trying to access their data
$reporting_username = $reporting_user ? htmlspecialchars($reporting_user['username']) : "Unknown User";
$reporting_user_id = $reporting_user ? $reporting_user['id'] : "N/A";

// Ensure details field is not null before using htmlspecialchars()
$report_reason = !empty($report['reason']) ? htmlspecialchars($report['reason']) : "No reason provided.";
$report_details = !empty($report['details']) ? nl2br(htmlspecialchars($report['details'])) : "No additional details.";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Report</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <style>
        .report-details, .review-form {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .report-details p, .review-form label {
            margin: 10px 0;
        }
        .review-form textarea {
            width: 100%;
            height: 100px;
            resize: vertical;
        }
        .review-form select {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
        }
        /* Dark theme styles */
        body.dark .report-details, body.dark .review-form {
            background-color: #333;
            color: #fff;
            border-color: #555;
        }
        body.dark .review-form select, body.dark .review-form textarea {
            background-color: #555;
            color: #fff;
            border-color: #777;
        }
    </style>
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <main>
        <div class="report-details">
            <h2>Report Details</h2>
            <p><strong>Report ID:</strong> <?php echo $report_id; ?></p>
            <p><strong>Reported User:</strong> <?php echo $reported_username . " (ID: " . $reported_user_id . ")"; ?></p>
            <p><strong>Reprorting User:</strong> <?php echo $reporting_username . " (ID: " . $reporting_user_id . ")"; ?></p>
            <p><strong>Reason:</strong> <?php echo $report_reason; ?></p>
            <p><strong>Details:</strong> <?php echo $report_details; ?></p>
        </div>

        <div class="review-form">
            <form action="/moderation/process_review" method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['id']); ?>">
                
                <label for="decision">Action Taken:</label>
                <select name="decision" required>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="warning">User Warned</option>
                    <option value="banned">User Banned</option>
                </select>

                <label for="additional_notes">Additional Notes:</label>
                <textarea name="additional_notes" placeholder="Optional notes"></textarea>

                <button class="button" type="submit">Submit Review</button>
            </form>
        </div>
    </main>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>

    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>