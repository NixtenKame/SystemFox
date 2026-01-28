<?php
ob_start();
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Accept either query params (web.config rewrite) or path: /user/{id}/favorites or /user/{username}/favorites
$userIdParam = $_GET['user'] ?? null;
$usernameParam = $_GET['username'] ?? null;

if (!$userIdParam && !$usernameParam) {
    if (preg_match('#^/user/([^/]+)/favorites(?:/|$)#', $path, $m)) {
        $candidate = $m[1];
        if (is_numeric($candidate)) $userIdParam = $candidate; else $usernameParam = $candidate;
    }
}

define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

// Resolve user
if ($userIdParam) {
    $stmt = $db->prepare("SELECT id, username, profile_picture FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userIdParam);
} else {
    $decoded = urldecode($usernameParam);
    $u1 = $decoded;
    $u2 = str_replace('_', ' ', $decoded);
    $stmt = $db->prepare("SELECT id, username, profile_picture FROM users WHERE username = ? OR username = ? LIMIT 1");
    $stmt->bind_param("ss", $u1, $u2);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(404);
    exit("User not found.");
}
$profileUser = $res->fetch_assoc();
$stmt->close();

$profilePicture = (!empty($profileUser['profile_picture']))
    ? (strpos($profileUser['profile_picture'], '/public/') === 0
        ? $profileUser['profile_picture']
        : '/public/uploads/' . htmlspecialchars($profileUser['profile_picture'], ENT_QUOTES, 'UTF-8'))
    : '/public/images/default-profile.png';

// Pagination
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch favorites joined with uploads (exclude soft-deleted)
$mainQuery = "SELECT uploads.*, favorites.favorited_at, users.username AS uploader_username
FROM favorites
JOIN uploads ON uploads.id = favorites.image_id
LEFT JOIN users ON uploads.uploaded_by = users.id
WHERE favorites.user_id = ? AND uploads.is_deleted = 0
ORDER BY favorites.favorited_at DESC
LIMIT ? OFFSET ?";

$stmt = $db->prepare($mainQuery);
$stmt->bind_param("iii", $profileUser['id'], $itemsPerPage, $offset);
$stmt->execute();
$uploadsResult = $stmt->get_result();

// Total favorites count
$countQuery = "SELECT COUNT(*) AS total FROM favorites JOIN uploads ON uploads.id = favorites.image_id WHERE favorites.user_id = ? AND uploads.is_deleted = 0";
$countStmt = $db->prepare($countQuery);
$countStmt->bind_param("i", $profileUser['id']);
$countStmt->execute();
$countRes = $countStmt->get_result();
$totalRow = $countRes->fetch_assoc();
$totalItems = intval($totalRow['total']);
$totalPages = max(1, (int)ceil($totalItems / $itemsPerPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($profileUser['username'], ENT_QUOTES, 'UTF-8'); ?>'s Favorites</title>
    <link rel="stylesheet" href="/public/css/styles.css" />
</head>
<body>
<nav>
<?php include_once '../includes/nav.php'; ?>
</nav>
<?php include_once '../includes/site-notice.php'; ?>

<main>
    <div class="profile-container">
        <h1>Favorites:</h1>
        <h2 id="username"><?php echo htmlspecialchars($profileUser['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <img src="<?php echo $profilePicture; ?>" alt="Profile Picture" style="width:100px;height:100px;border-radius:50%;" />
        <p>Total Favorites: <?php echo $totalItems; ?></p>
    </div>

    <?php if ($uploadsResult->num_rows > 0): ?>
        <div class="gallery">
            <?php while ($row = $uploadsResult->fetch_assoc()): ?>
                <?php
                    if (!empty($row['is_deleted']) && $row['is_deleted'] == 1) continue; // extra safety
                    $filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($row['file_name']);
                    $displayName = htmlspecialchars(pathinfo($row['display_name'], PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8');
                    $extension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
                ?>
                <div class="gallery-item">
                    <a href="/posts/<?php echo $row['id']; ?>" title="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (in_array($extension, ['mp4','webm','ogg'])): ?>
                            <video muted playsinline preload="metadata">
                                <source src="<?php echo $filePath; ?>" type="video/<?php echo $extension; ?>">
                            </video>
                        <?php else: ?>
                            <img loading="lazy" decoding="async" src="<?php echo $filePath; ?>" alt="<?php echo $displayName; ?>" />
                        <?php endif; ?>
                        <div class="gallery-caption">
                            <div class="caption-title"><?php echo $displayName; ?></div>
                            <div class="caption-meta"><?php echo htmlspecialchars($row['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($row['tag_string'])): ?>
                                    &nbsp;•&nbsp;<?php echo htmlspecialchars(implode(' ', array_slice(array_filter(array_map('trim', explode(',', $row['tag_string']))),0,8)), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>">&laquo; Previous</a>
            <?php endif; ?>

            <?php
            $pageRange = 2;
            $startPage = max(1, $currentPage - $pageRange);
            $endPage = min($totalPages, $currentPage + $pageRange);

            if ($startPage > 1):
                echo '<a href="?page=1">1</a>';
                if ($startPage > 2) echo '<span class="dots">...</span>';
            endif;

            for ($i = $startPage; $i <= $endPage; $i++):
                echo '<a href="?page=' . $i . '" class="' . ($i === $currentPage ? 'active' : '') . '">' . $i . '</a>';
            endfor;

            if ($endPage < $totalPages):
                if ($endPage < $totalPages - 1) echo '<span class="dots">...</span>';
                echo '<a href="?page=' . $totalPages . '">' . $totalPages . '</a>';
            endif;
            ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p style="margin-left:340px;">No favorites found.</p>
    <?php endif; ?>
</main>

<?php include('../includes/version.php'); ?>
<footer>
    <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. 
        <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a>
    </p>
</footer>

</body>
</html>
