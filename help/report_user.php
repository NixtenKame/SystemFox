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


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report User</title>
    
    <style>
    </style>
    <link rel="stylesheet" type="text/css" href="/public/css/styles.css">
</head>
<body>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
</form>
<body>
        <main>
            <h1>Report User</h1>
            <p>If you believe a user is violating our guidelines, please email us at <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a>. Include the following details in your report:</p> <ul> <li>Your username</li> <li>The reported user’s username (and user ID if known)</li> <li>The image ID (if applicable)</li> <li>A brief description of the violation</li> </ul> <p>We take all reports seriously. If we determine that a user has violated our guidelines, they will receive an email notification explaining the violation, and their account may be suspended or permanently removed.</p> <p>As this is a child-friendly website, we prioritize user safety. If you notice any inappropriate behavior—such as an adult engaging in concerning conversations with minors—please report it immediately. Your vigilance helps us maintain a safe and respectful community.</p>
        </main>
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
