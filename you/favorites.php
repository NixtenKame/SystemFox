<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view your favorites.");
}

$userId = $_SESSION['user_id'];

// Fetch favorited images
$query = "SELECT uploads.* FROM uploads 
          JOIN favorites ON uploads.id = favorites.image_id 
          WHERE favorites.user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorites</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    
    </head>
<body>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

<?php if ($result->num_rows > 0): ?>
    <div class="gallery">
        <?php while ($row = $result->fetch_assoc()): ?>

            <?php 
                // Skip deleted uploads (failsafe)
                if (!empty($row['is_deleted']) && $row['is_deleted'] == 1) {
                    continue;
                }

                // Correct path
                $filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($row['file_name']);

                // Trim display name
                $maxLength = 30;
                $displayName = htmlspecialchars($row['display_name']);
                if (strlen($displayName) > $maxLength) {
                    $displayName = substr($displayName, 0, $maxLength) . '...';
                }

                // Tags
                $tagsArray = array_filter(array_map(
                    'trim',
                    explode(',', is_string($row['tag_string']) ? $row['tag_string'] : '')
                ));

                // FIXED — correct variable name
                $extension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
            ?>

            <div class="gallery-item">
                <a 
                    title="<?php echo $displayName; ?>


ID: <?php echo htmlspecialchars($row['id']); ?>

Rating: <?php echo htmlspecialchars($row['category']); ?>

Uploaded by: @<?php echo htmlspecialchars($row['uploaded_by']); ?>

<?php if (isset($_SESSION['user_id'])): ?>
Date: <?= convertToUserTimezone($row['upload_date'], $server_timezone, $user_timezone_obj); ?> (<?= htmlspecialchars($user_timezone); ?>)
<?php endif; ?>


<?php echo htmlspecialchars($row['tag_string']); ?>"

                    href="/posts/<?php echo $row['id']; ?>"
                >

                    <?php if (in_array($extension, ['mp4','webm','mov'])): ?>
                        <!-- Video preview -->
                        <video 
                            src="<?php echo $filePath; ?>" 
                            muted 
                            preload="metadata"
                            onloadedmetadata="this.currentTime = Math.random() * this.duration;"
                            onerror="this.onerror=null;this.src='../public/images/placeholder.png';"
                        ></video>
                    <?php else: ?>
                        <!-- Image fallback -->
                        <img 
                            src="<?php echo $filePath; ?>" 
                            alt="<?php echo htmlspecialchars(pathinfo($row['file_name'], PATHINFO_FILENAME)); ?>"
                            onerror="this.onerror=null;this.src='../public/images/placeholder.png';"
                        />
                    <?php endif; ?>

                </a>
            </div>

        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p>You have no favorites :3</p>
<?php endif; ?>
        <br>
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
