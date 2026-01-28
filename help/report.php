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
    <title>Report Image</title>
    
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
            <h1>Report Image</h1>
            <p>If you believe that a image violates our guidelines email us at <a href="mailto:nixtenkame@gmail.com">nixtenkame@gamil.com</a> and include your username, user ID if you know it, the image ID, the username and or userID of the uploader, and a brief description of why you believe the image violates our guidelines. If we see that the image does in fact violate guidelines the user of the upload will recieve a email stating that there post was taken down and that it was a warning and if it happens again there account will be taken down.</p>
        </main>
        <br>
        <br>
        <br>
        <br>
        <br>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
