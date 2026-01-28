<?php
//detailed errors reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);


define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once '../includes/header.php';

// Check if user is logged in and is a moderator or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'moderator')) {
    header("Location: /error/403");
    exit();
}

// Fetch only unresolved reports (status = 'pending')
$query = "SELECT reports.id, reports.reported_user_id, reports.reason, reports.report_date, users.username AS reported_username 
          FROM reports 
          LEFT JOIN users ON reports.reported_user_id = users.id 
          WHERE reports.status = 'pending' 
          ORDER BY reports.report_date DESC";
$result = $db->query($query);

if (!$result) {
    die("Error retrieving reports: " . $db->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation Panel</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
        .actions a {
            margin-right: 10px;
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
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
    <nav>
        <?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <main>
        <h2>Reported Content</h2>
        <table>
            <tr>
                <th>Report ID</th>
                <th>Reported User</th>
                <th>Reason</th>
                <th>Report Date</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['reported_username']); ?></td>
                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                    <td><?php echo htmlspecialchars($row['report_date']); ?></td>
                    <td class="actions">
                        <a href="review?id=<?php echo $row['id']; ?>">Review</a>
                        <a href="resolve_report?id=<?php echo $row['id']; ?>&action=approve">Approve</a>
                        <a href="resolve_report?id=<?php echo $row['id']; ?>&action=reject">Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
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