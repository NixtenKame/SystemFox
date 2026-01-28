<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../../includes/header.php'); // Include common header

// Fetch news from the database
$newsQuery = "SELECT * FROM news ORDER BY created_at DESC";
$newsResult = $db->query($newsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/styles.css">
    <title>News</title>
    <style>
        .news-item {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .news-item h3 {
            margin: 0 0 10px;
        }
        .news-item p {
            margin: 0 0 10px;
        }
        .news-item small {
            color: #888;
        }
        body.dark .news-item {
    margin-bottom: 20px;
    padding: 20px;
    border: 1px solid #444; /* Darker border for dark mode */
    border-radius: 8px;
    background-color: #333; /* Dark background */
    color: #ddd; /* Light text color */
}

body.dark .news-item h3 {
    margin: 0 0 10px;
    color: #fff; /* White text for headings */
}

body.dark .news-item p {
    margin: 0 0 10px;
    color: #ccc; /* Slightly lighter text for paragraphs */
}

body.dark .news-item small {
    color: #aaa; /* Subtle text for small elements */
}
    </style>
</head>
<body>
    <nav>
        <?php include_once '../../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../../includes/site-notice.php'; ?>
    <main>
        <?php while ($row = $newsResult->fetch_assoc()): ?>
            <div class="news-item">
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                <small>Posted by <?php echo htmlspecialchars($row['author']); ?> on <?php echo htmlspecialchars($row['created_at']); ?></small>
            </div>
        <?php endwhile; ?>
    </main>
    <br>
    <br>
    <br>
    <?php include('../../../includes/version.php'); ?>
    <footer>
        <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>