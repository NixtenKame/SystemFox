<?php
define('ROOT_PATH', realpath(__DIR__ . '/..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('includes/header.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$itemsPerPage = $perPage ?? '75';
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;

$offset = ($currentPage - 1) * $itemsPerPage;
$whereClause = [];
$params = [];
$paramTypes = "";

// User session
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? 'moderator';
$disabledTags = array_map('strtolower', array_map('trim', $_SESSION['disabled_blacklist_tags'] ?? []));
$blacklistedTags = [];

// Fetch user blacklist tags (excluding disabled ones)
if ($user_id) {
    $blacklistQuery = $db->prepare("SELECT tag FROM user_blacklist WHERE user_id = ?");
    $blacklistQuery->bind_param("i", $user_id);
    $blacklistQuery->execute();
    $blacklistResult = $blacklistQuery->get_result();

    while ($row = $blacklistResult->fetch_assoc()) {
        $tag = strtolower(trim($row['tag']));
        if (!in_array($tag, $disabledTags)) {
            $blacklistedTags[] = $tag;
        }
    }
    $blacklistQuery->close();
}

// Apply blacklist filtering
if (!empty($blacklistedTags)) {
    $placeholders = implode(',', array_fill(0, count($blacklistedTags), '?'));
    $whereClause[] = "uploads.id NOT IN (
        SELECT ut.upload_id
        FROM upload_tags ut
        JOIN tags t ON ut.tag_id = t.tag_id
        WHERE LOWER(TRIM(t.tag_name)) IN ($placeholders)
    )";
    $params = array_merge($params, $blacklistedTags);
    $paramTypes .= str_repeat('s', count($blacklistedTags));
}

// --- Integrated search inputs (tags or filename) and category filter ---
$searchTerm = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? trim((string)$_GET['category']) : '';

// If searching, match display_name OR tag name (case-insensitive, partial)
if ($searchTerm !== '') {
    $like = '%' . mb_strtolower($searchTerm, 'UTF-8') . '%';
    $whereClause[] = "(LOWER(uploads.display_name) LIKE ? OR LOWER(TRIM(tags.tag_name)) LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $paramTypes .= "ss";
}

// Combine WHERE
$whereConditions = '';
if (!empty($whereClause)) {
    $whereConditions = "WHERE " . implode(" AND ", $whereClause);
}

// Total count (respect filters)
$totalQuery = "
    SELECT COUNT(DISTINCT uploads.id) AS total
    FROM uploads
    LEFT JOIN upload_tags ON uploads.id = upload_tags.upload_id
    LEFT JOIN tags ON upload_tags.tag_id = tags.tag_id
    $whereConditions
";
$totalStmt = $db->prepare($totalQuery);
if ($totalStmt === false) {
    die("Failed to prepare total query: " . $db->error);
}
if (!empty($params)) {
    $totalStmt->bind_param($paramTypes, ...$params);
}
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalItems = $totalRow['total'] ?? 0;
$totalPages = max(1, (int)ceil($totalItems / $itemsPerPage));
$totalStmt->close();

// Fetch paginated uploads with tags
$mainQuery = "
    SELECT 
        uploads.id, uploads.file_name, uploads.category, uploads.uploaded_by, uploads.upload_date, uploads.display_name, uploads.tag_string,
        COALESCE(users.username, 'Unknown') AS username,
        GROUP_CONCAT(DISTINCT LOWER(TRIM(tags.tag_name)) ORDER BY tags.tag_name SEPARATOR ', ') AS tags,
        (
            (SELECT COUNT(*) FROM image_likes WHERE image_likes.image_id = uploads.id AND action = 'like') +
            (SELECT COUNT(*) FROM favorites WHERE favorites.image_id = uploads.id)
        ) AS popularity
    FROM uploads
    LEFT JOIN users ON uploads.uploaded_by = users.id
    LEFT JOIN upload_tags ON uploads.id = upload_tags.upload_id
    LEFT JOIN tags ON upload_tags.tag_id = tags.tag_id
    $whereConditions
    GROUP BY uploads.id
    ORDER BY popularity DESC, uploads.upload_date DESC
    LIMIT ? OFFSET ?
";

$mainStmt = $db->prepare($mainQuery);

// Add pagination params
$paramTypesPage = $paramTypes . "ii";
$paramsPage = array_merge($params, [$itemsPerPage, $offset]);
$mainStmt->bind_param($paramTypesPage, ...$paramsPage);

$mainStmt->execute();
$result = $mainStmt->get_result();

// Fetch popular tags
$popularTagsQuery = "
    SELECT LOWER(TRIM(tag_name)) AS tag, post_count AS count
    FROM tags
    WHERE tag_name != ''
    ORDER BY post_count DESC
    LIMIT 25
";
$popularTagsResult = $db->query($popularTagsQuery);

date_default_timezone_set('America/Chicago');
$currentTime = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popular Uploads - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
</head>
<body>

    <nav>
<?php include_once 'includes\nav.php'; ?>
    </nav>
    <?php include_once 'includes\site-notice.php'; ?>

    <main>
        <?php if ($result->num_rows > 0): ?>
            <div class="gallery">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        $categoryFolder = strtolower($row['category']);
                        $filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($row['file_name']);

                        $maxLength = 30;
                        $displayName = htmlspecialchars($row['display_name']);
                        if (strlen($displayName) > $maxLength) {
                            $displayName = substr($displayName, 0, $maxLength) . '...';
                        }

                        // Prepare tags as clickable links
                        $tagsArray = array_filter(array_map('trim', explode(',', is_string($row['tags']) ? $row['tags'] : '')));
                    ?>
                    <div class="gallery-item">
                        <a 
                        title="<?php echo $displayName; ?>

ID: <?php echo htmlspecialchars($row['id']); ?>

Rating: <?php echo htmlspecialchars($row['category']); ?>

Uploaded by: @<?php echo htmlspecialchars($row['username']); ?>

<?php if (isset($_SESSION['user_id'])): ?>Date: <?= convertToUserTimezone($row['upload_date'], $server_timezone, $user_timezone_obj); ?> (<?= htmlspecialchars($user_timezone); ?>)<?php endif; ?>


<?php echo htmlspecialchars($row['tag_string']); ?>"
                            href="/posts/<?php echo $row['id']; ?>">
<?php if (in_array(strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg'])): ?>
    <!-- Video with dynamic random frame preview -->
    <video width="320" height="240" muted preload="metadata">
        <source src="<?php echo $filePath ?>" type="video/<?php echo strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION)); ?>">
    </video>
<?php else: ?>
    <img src="<?php echo $filePath ?>" alt="<?php echo $displayName; ?>" />
<?php endif; ?>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No uploads found.</p>
        <?php endif; ?>
    </main>
    <br>
    <br>
    <br>
    <?php include('includes/version.php'); ?>
    <footer>
        <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
        <p>Current Server Time: <strong id="serverTime"><?php echo date('l, F j, Y - h:i:s A'); ?> (CST/CDT)</strong></p>
    </footer>
</body>
</html>