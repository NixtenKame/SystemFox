<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Define items per page
$itemsPerPage = 50;

// Get the current page from the URL, default is 1
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

// Get filter type from URL (default to both SFW and NSFW)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'both';

// Calculate the offset
$offset = ($currentPage - 1) * $itemsPerPage;

// Build query based on filter
$whereClause = "";
if ($filter == 'sfw') {
    $whereClause = "WHERE category = 'SFW'";
} elseif ($filter == 'nsfw') {
    $whereClause = "WHERE category = 'NSFW'";
}

// Fetch total number of uploads to calculate total pages
$totalQuery = "SELECT COUNT(*) AS total FROM uploads $whereClause";

// Fetch uploads for the current page along with the uploader's username
$query = "SELECT uploads.id, uploads.file_name, uploads.category, uploads.tags, uploads.uploaded_by, 
                 COALESCE(users.username, 'Unknown') AS username 
          FROM uploads
          LEFT JOIN users ON uploads.uploaded_by = users.id
          $whereClause 
          ORDER BY uploads.upload_date DESC 
          LIMIT $itemsPerPage OFFSET $offset";

// Set timezone to Central Time (CST/CDT)
date_default_timezone_set('America/Chicago');

// Get the current server time (initial load)
$currentTime = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Page Not Found :3</title>
    <link rel="stylesheet" type="text/css" href="/public/css/styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 5%;
            text-align: center;
        }
        .container h1 {
            font-size: 72px;
            margin: 0;
            color: #ff6f61;
        }
        .container p {
            font-size: 18px;
            margin: 20px 0;
            color: black
        }
        .container img {
            max-width: 100px;
            margin: 20px 0;
        }
    </style>
</head>
<body>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <div class="container">
    <img src="/public/images/favicon.ico" alt="Logo">
    <h1>404</h1>
    <p>Oops! The page you're looking for doesn't exist.</p>
    <p>It looks like the page you are trying to reach is not available. Please check the URL or go back to the homepage.</p>
    <a href="/posts" class="button">Home</a>
    <a href="mailto:nixtenkame@gmail.com" class="button">Contact Support</a>
</div>

    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2025 FluffFox. (Property of NIXTENSSERVER (nixten.ddns.net)) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>