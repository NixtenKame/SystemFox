<?php
ob_start();
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract user identifier from path: /user/{username}/posts or /user/{userId}/posts
$userIdentifier = null;
if (preg_match('#^/user/([^/]+)/posts(?:/|$)#', $path, $matches)) {
    $userIdentifier = $matches[1];
}

if (!$userIdentifier) {
    http_response_code(404);
    exit("User not specified or invalid URL.");
}

define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

// Determine if identifier is numeric (user ID) or string (username)
$isNumeric = is_numeric($userIdentifier);
$userData = null;

if ($isNumeric) {
    // Query by user ID
    $query = "SELECT id, username, bio, profile_picture, email, level, created_at, last_active, birthdate, online_status, email_visibility, birthday_visibility 
              FROM users 
              WHERE id = ? 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userIdentifier);
} else {
    // Query by username (handle both underscore and space versions)
    $decodedUsername = urldecode($userIdentifier);
    $usernameUnderscore = $decodedUsername;
    $usernameWithSpaces = str_replace('_', ' ', $decodedUsername);
    
    $query = "SELECT id, username, bio, profile_picture, email, level, created_at, last_active, birthdate, online_status, email_visibility, birthday_visibility 
              FROM users 
              WHERE username = ? OR username = ? 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $usernameUnderscore, $usernameWithSpaces);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("User not found.");
}

$userData = $result->fetch_assoc();
$stmt->close();

$profilePicture = (!empty($userData['profile_picture']))
    ? (strpos($userData['profile_picture'], '/public/') === 0
        ? $userData['profile_picture']
        : '/public/uploads/' . htmlspecialchars($userData['profile_picture'], ENT_QUOTES, 'UTF-8'))
    : '/public/images/default-profile.png';

$user_id = $_SESSION['user_id'] ?? 0;

// Load blacklisted tags
$blacklistedTags = [];
if ($user_id) {
    $blacklistQuery = $db->prepare("SELECT tag FROM user_blacklist WHERE user_id = ?");
    $blacklistQuery->bind_param("i", $user_id);
    $blacklistQuery->execute();
    $blacklistResult = $blacklistQuery->get_result();
    while ($row = $blacklistResult->fetch_assoc()) {
        $blacklistedTags[] = $row['tag'];
    }
    $blacklistQuery->close();
}

// Pagination
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch paginated uploads with tags
$mainQuery = "SELECT * FROM uploads WHERE uploaded_by = ? AND is_deleted = 0";
$params = [$userData['id']];
$types = "i";

// Exclude blacklisted tags
if (!empty($blacklistedTags)) {
    $placeholders = implode(',', array_fill(0, count($blacklistedTags), '?'));
    $mainQuery .= " AND NOT (";
    foreach ($blacklistedTags as $index => $tag) {
        if ($index > 0) $mainQuery .= " OR ";
        $mainQuery .= "FIND_IN_SET(?, tag_string)";
    }
    $mainQuery .= ")";
    $types .= str_repeat("s", count($blacklistedTags));
    $params = array_merge($params, $blacklistedTags);
}

$mainQuery .= " ORDER BY upload_date DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $itemsPerPage;
$params[] = $offset;

$stmt = $db->prepare($mainQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$uploadsResult = $stmt->get_result();

// Total uploads count
$totalCountQuery = "SELECT COUNT(*) AS total FROM uploads WHERE uploaded_by = ? AND is_deleted = 0";
$totalParams = [$userData['id']];
$totalTypes = "i";

if (!empty($blacklistedTags)) {
    $totalCountQuery .= " AND NOT (";
    foreach ($blacklistedTags as $index => $tag) {
        if ($index > 0) $totalCountQuery .= " OR ";
        $totalCountQuery .= "FIND_IN_SET(?, tag_string)";
    }
    $totalCountQuery .= ")";
    $totalTypes .= str_repeat("s", count($blacklistedTags));
    $totalParams = array_merge($totalParams, $blacklistedTags);
}

// Fetch popular tags
$popularTagsQuery = "
    SELECT LOWER(TRIM(tag_name)) AS tag, post_count AS count
    FROM tags
    WHERE tag_name != ''
    ORDER BY post_count DESC
    LIMIT 25
";
$popularTagsResult = $db->query($popularTagsQuery);

$totalStmt = $db->prepare($totalCountQuery);
$totalStmt->bind_param($totalTypes, ...$totalParams);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalItems = intval($totalRow['total']);
$totalPages = ceil($totalItems / $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?>'s Posts</title>
    <link rel="stylesheet" href="/public/css/styles.css" />
</head>
<body>

<nav>
<?php include_once '../includes/nav.php'; ?>
</nav>
<?php include_once '../includes/site-notice.php'; ?>

<main>
    <div class="content">
    <div class="sidebar">
        <div class="search-section">
            <form action="/posts/" method="GET">
                <h2>Search:</h2>
                <label for="tags"><i class="fa-solid fa-magnifying-glass"></i></label>
                <input class="search-bar" type="text" name="q" placeholder="Search posts by tag" required />
            </form>
        </div>

        <?php if (!empty($blacklistedTags) || !empty($_SESSION['disabled_blacklist_tags'])): ?>
            <h3 style="color: red;">Blacklisted Tags</h3>
            <?php echo csrf_input(); ?>
            <ul>
                <?php
                $disabledTags = $_SESSION['disabled_blacklist_tags'] ?? [];
                $filteredBlacklistedTags = array_diff($blacklistedTags, $disabledTags);

                foreach ($filteredBlacklistedTags as $tag): ?>
                    <li>
                        <?php echo htmlspecialchars($tag); ?>
                        <button class="toggle-blacklist" data-tag="<?php echo htmlspecialchars($tag); ?>">Disable</button>
                    </li>
                <?php endforeach; ?>

                <?php foreach ($disabledTags as $tag): ?>
                    <li>
                        <strike><?php echo htmlspecialchars($tag); ?></strike>
                        <button class="toggle-blacklist" data-tag="<?php echo htmlspecialchars($tag); ?>">Enable</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h3>Popular Tags</h3>
        <ul>
            <?php while ($tagRow = $popularTagsResult->fetch_assoc()): ?>
                <li>
                    <a href="/posts/?q=<?php echo urlencode($tagRow['tag']); ?>"><abbr title="View posts tagged '<?php echo htmlspecialchars($tagRow['tag']); ?>'"><?php echo htmlspecialchars($tagRow['tag']); ?> (<?php echo $tagRow['count']; ?>)</abbr></a>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
    <?php if ($uploadsResult->num_rows > 0): ?>
        <div class="gallery">
            <?php while ($upload = $uploadsResult->fetch_assoc()): ?>
                <?php
                    $filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($upload['file_name']);
                    $displayName = htmlspecialchars(pathinfo($upload['display_name'], PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8');
                    $extension = strtolower(pathinfo($upload['file_name'], PATHINFO_EXTENSION));
                ?>
                <div class="gallery-item">
                    <?php
                        $tooltip = $displayName . "\n";
                        $tooltip .= "ID: " . ($upload['id'] ?? '') . "\n";
                        $tooltip .= "Rating: " . ($upload['category'] ?? '') . "\n";
                        $tooltip .= "Uploaded by: @" . htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8') . "\n";
                        if (isset($_SESSION['user_id'])) {
                            $tooltip .= "Date: " . (isset($upload['upload_date']) ? convertToUserTimezone($upload['upload_date'], $server_timezone, $user_timezone_obj) : '') . " ($user_timezone)" . "\n" . "\n";
                        }
                        $tooltip .= ($upload['tag_string'] ?? '');
                    ?>

                    <a title="<?php echo htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8'); ?>" href="/posts/<?php echo $upload['id']; ?>">
                        <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                            <video muted playsinline preload="metadata">
                                <source src="<?php echo $filePath; ?>" type="video/<?php echo $extension; ?>">
                            </video>
                        <?php else: ?>
                            <img loading="lazy" decoding="async" src="<?php echo $filePath; ?>" alt="<?php echo $displayName; ?>" />
                        <?php endif; ?>

                        <div class="gallery-caption">
                            <div class="caption-title"><?php echo $displayName; ?></div>
                            <div class="caption-meta">
                                <?php echo htmlspecialchars($upload['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($upload['tag_string'])): ?>
                                    &nbsp;•&nbsp;<?php echo htmlspecialchars(implode(' ', array_slice(array_filter(array_map('trim', explode(',', $upload['tag_string']))), 0, 8)), ENT_QUOTES, 'UTF-8'); ?>
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
        <p style="margin-left:340px;">No posts found.</p>
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