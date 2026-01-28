<?php
ob_start(); // start output buffering
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract username from path (either /user/username, /users/username or /username)
if (preg_match('#^/users?/([^/]+)$#', $path, $matches)) {
    $usernameFromPath = $matches[1];
} elseif (preg_match('#^/([^/]+)$#', $path, $matches)) {
    $usernameFromPath = $matches[1];
} else {
    $usernameFromPath = $_GET['user'] ?? null;
}

if (!$usernameFromPath) {
    http_response_code(404);
    exit("User not specified or invalid URL.");
}

$decodedUsername = urldecode($usernameFromPath);

if (strpos($usernameFromPath, '+') !== false || strpos($decodedUsername, ' ') !== false) {
    $redirectUsername = str_replace(['+', ' '], '_', $usernameFromPath);
    $redirectUrl = '/user/' . rawurlencode($redirectUsername);
    header("Location: $redirectUrl", true, 301);
    exit;
}

define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

$usernameUnderscore = $decodedUsername;
$usernameWithSpaces = str_replace('_', ' ', $decodedUsername);

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Reserved names check
$reservedNames = [
    '.gitignore', 'bingsiteauth.xml', 'composer.json', 'composer.lock', 'error_redirect', 'favicon.ico', 'favicon',
    'google000728f721d3ed82', 'index', 'login', 'popular_uploads', 'random_image',
    'register', 'robots.txt', 'save_token', 'search',
    'spotify-callback', 'save_push'
];

if (in_array(strtolower($usernameUnderscore), array_map('strtolower', $reservedNames))) {
    http_response_code(404);
    exit("Page not found.");
}

// Query user from DB
$query = "SELECT id, username, bio, profile_picture, email, level, created_at, last_active, birthdate, online_status, email_visibility, birthday_visibility 
          FROM users 
          WHERE username = ? OR username = ? 
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bind_param("ss", $usernameUnderscore, $usernameWithSpaces);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("User not found.");
}

$userData = $result->fetch_assoc();
$username = $decodedUsername;

$profilePicture = (!empty($userData['profile_picture']))
    ? (strpos($userData['profile_picture'], '/public/') === 0
        ? $userData['profile_picture']
        : '/public/uploads/' . htmlspecialchars($userData['profile_picture'], ENT_QUOTES, 'UTF-8'))
    : '/public/images/default-profile.png';

$user_id = $_SESSION['user_id'] ?? 0;

$relationshipStatus = null;

if ($user_id && $user_id != $userData['id']) {
    $relStmt = $db->prepare("
        SELECT relationship_status 
        FROM user_relationships 
        WHERE user_id = ? AND target_id = ?
        LIMIT 1
    ");
    $relStmt->bind_param("ii", $user_id, $userData['id']);
    $relStmt->execute();
    $relResult = $relStmt->get_result();
    $relationshipStatus = $relResult->fetch_assoc()['relationship_status'] ?? 'none';
    $relStmt->close();
}


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

$blacklistedTagIds = [];
if (!empty($blacklistedTags)) {
    $placeholders = implode(',', array_fill(0, count($blacklistedTags), '?'));
    $tagIdQuery = "SELECT tag_id FROM tags WHERE tag_name IN ($placeholders)";
    $stmtTagIds = $db->prepare($tagIdQuery);
    $types = str_repeat('s', count($blacklistedTags));
    $stmtTagIds->bind_param($types, ...$blacklistedTags);
    $stmtTagIds->execute();
    $resultTagIds = $stmtTagIds->get_result();
    while ($row = $resultTagIds->fetch_assoc()) {
        $blacklistedTagIds[] = $row['tag_id'];
    }
    $stmtTagIds->close();
}

// Pagination
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;


$whereClause = [];
$whereClause[] = "uploads.user_id = ?";

// Determine whether we're viewing the deleted tab (URL ends with /deleted or ?tab=deleted)
$showDeleted = false;
if (preg_match('#/deleted$#', $path) || (isset($_GET['tab']) && $_GET['tab'] === 'deleted')) {
    $showDeleted = true;
}

// Use is_deleted filter depending on the selected tab
$isDeletedFilter = $showDeleted ? 1 : 0;
// default: exclude deleted posts
$whereClause[] = "uploads.is_deleted = " . intval($isDeletedFilter);

// Main query: use tag_string only, no join on tags table
$mainQuery = "
    SELECT *
    FROM uploads
    WHERE uploaded_by = ? AND is_deleted = ?
";

$params = [$userData['id'], $isDeletedFilter];
$types = "ii";

// Fetch this user's favorites (show recent favorites below uploads)
$favLimit = 24;
$favQuery = "SELECT uploads.*, favorites.favorited_at FROM favorites JOIN uploads ON uploads.id = favorites.image_id WHERE favorites.user_id = ? AND uploads.is_deleted = 0 ORDER BY favorites.favorited_at DESC LIMIT ?";
$favStmt = $db->prepare($favQuery);
if ($favStmt) {
    $favStmt->bind_param('ii', $userData['id'], $favLimit);
    $favStmt->execute();
    $favResult = $favStmt->get_result();
} else {
    $favResult = null;
}

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
$types .= "ii"; // pagination params
$params[] = $itemsPerPage;
$params[] = $offset;

$stmt = $db->prepare($mainQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$uploadsResult = $stmt->get_result();

$stmt = $db->prepare("SELECT COUNT(*) AS total FROM uploads WHERE uploaded_by = ? AND is_deleted = 1");
$stmt->bind_param("i", $userData['id']);
$stmt->execute();
$deletedCount = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total uploads count
$totalCountQuery = "SELECT COUNT(*) AS total FROM uploads WHERE uploaded_by = ?";
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
    <title><?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?>'s Profile</title>
    <link rel="stylesheet" href="/public/css/styles.css" />
    <link rel="stylesheet" href="/user/<?php echo urlencode($userData['id']); ?>/profile_style.css" />
    <style>
        /* Small utility styles kept minimal; gallery will be a horizontal scroller */
        .status-indicator{ width:10px; height:10px; border-radius:50%; display:inline-block; margin-left:5px; }
        .online{ background:green; }
        .offline{ background:red; }
        .badge{ display:inline-block; margin-top:6px; padding:4px 8px; font-size:12px; border-radius:6px; font-weight:bold; color:#fff; }
        .legacy{ background:orange; }
        .super-legacy{ background:red; }
        .owner-badge{ display:inline-block; margin-top:6px; padding:4px 8px; font-size:12px; border-radius:6px; font-weight:bold; color:#fff; background:purple; }
        .button{ display:block; }

        /* Layout: keep profile container narrow and float it left so gallery can sit to its right */
        .profile-container{ width:320px; float:left; box-sizing:border-box; padding:12px; }

        /* Horizontal gallery: scrollable row of items to the right of the profile container */
        .gallery{ display:flex; gap:12px; overflow-x:auto; overflow-y:hidden; -webkit-overflow-scrolling:touch; padding:12px; margin-left:340px; scroll-snap-type:x mandatory; background-color: #1E1E1E; }
        .gallery::-webkit-scrollbar{ height:10px; }
        .gallery::-webkit-scrollbar-thumb{ background:rgba(0,0,0,0.12); border-radius:8px; }
        .gallery-caption{ padding:8px; font-size:13px; color:#111; }

        .profile-header {
            display: flex;
            margin-left: 340px;
        }

        .profile-sample-header{
            font-size: 18px;
            font-weight: bold;
            background-color: #313131ff;
            width: fit-content;
            border-radius: 6px 6px 0 0;
            line-height: 1.25rem;
        }

        .profile-sample-header:hover {
            background-color: #FF91F0;
        }

        .profile-sample-header a {
            color: #ffffff;
            text-decoration: none;
            padding: 8px 12px;
            display: block;
        }

        .profile-sample-links {
            display: flex;
            margin-left: .25rem;
        }

        .profile-sample-links a {
            display: block;
            box-sizing: border-box;
            align-content: center;
            text-align: center;
            height: 100%;
            padding: .5em;
            border-radius: 6px;
            background-color: #313131ff;
            margin-left: .25rem;
        }

        @media (min-width: 50rem) {
            .profile-sample-links a {
                height: min-content;
            }
        }

        /* Pagination should clear floats */
        .pagination{ clear:both; }

        /* Responsive: stack on narrow screens */
        @media(max-width:900px){
            .profile-container{ width:100%; float:none; margin-bottom:12px; }
            .gallery{ margin-left:0; display:flex; gap:10px; padding:8px; }
            .gallery-item{ flex:0 0 48%; width:48%; }
            .gallery-item img, .gallery-item video{ height:160px; }
        }
        @media (min-width: 50rem) {
            .gallery-item {
                min-height: calc(var(--thumb-image-size, 150px) + 1rem);
                min-width: 200px;
                width: unset;
            }
        }
    </style>
</head>
<body>

<nav>
<?php include_once '../includes/nav.php'; ?>
</nav>
<?php include_once '../includes/site-notice.php'; ?>

<main>
    <div class="profile-container">
        <h1>User profile:</h1>
        <h2 id="username">
            <?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?>
        </h2>
        <img src="<?php echo $profilePicture; ?>" alt="Profile Picture" class="profile-picture" 
             style="width: 100px; height: 100px; border-radius: 50%;">
        <p>Status: <?php echo nl2br(htmlspecialchars($userData['online_status'] ?: "", ENT_QUOTES, 'UTF-8')); ?>
            <span class="status-indicator <?php echo $userData['online_status'] == 'online' ? 'online' : 'offline'; ?>"></span>
        </p>
        <p>Email: 
            <?php if ($userData['email_visibility']): ?>
                <a href="mailto:<?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php else: ?>
                Hidden
            <?php endif; ?>
        </p>
        <div id="legacy-badges"></div>
        <?php
        if (isset($userData['username']) && $userData['username'] == "NotNixtenLeoKame") {
            echo '<div class="owner-badge">Owner</div>';
        }
        ?>
        <?php
        if (isset($userData['username']) && $userData['username'] == "NotNixtenLeoKame") {
            echo '<div class="badge super-legacy">Super Legacy User</div>';
        }
        ?>
        <?php
        if (isset($userData['username']) && $userData['username'] == "Nixten Leo Kame") {
            echo '<div class="owner-badge">Owner</div>';
        }
        ?>

        <p class="bio"><?php echo nl2br(htmlspecialchars($userData['bio'] ?: "No bio available.", ENT_QUOTES, 'UTF-8')); ?></p>
        <br>
        <h3>Profile Information:</h3>
        <div class="profile-info">
        <p>Id: <?php echo nl2br(htmlspecialchars($userData['id'], ENT_QUOTES, 'UTF-8')); ?></p>
        <p>Join Date: <?php echo nl2br(htmlspecialchars($userData['created_at'], ENT_QUOTES, 'UTF-8')); ?></p>
        <p>Account Level: <?php echo nl2br(htmlspecialchars($userData['level'] ?: "No account type available", ENT_QUOTES, 'UTF-8')); ?></p>
        <p>Last Active: <?php echo nl2br(htmlspecialchars($userData['last_active'], ENT_QUOTES, 'UTF-8')); ?></p>
        <p>Birthday: 
            <?php if ($userData['birthday_visibility']): ?>
                <?php echo nl2br(htmlspecialchars($userData['birthdate'], ENT_QUOTES, 'UTF-8')); ?>
            <?php else: ?>
                Hidden
            <?php endif; ?>
        </p>
        <p>Age: <?php 
        if (!empty($userData['birthdate'])) {
            $birthdate = new DateTime($userData['birthdate']);
            $today = new DateTime();
            $age = $today->diff($birthdate)->y;
            echo htmlspecialchars($age, ENT_QUOTES, 'UTF-8');
        } else {
            echo "Age not available";
        }
        ?></p>
        <p>Total Uploads: <?php echo $totalItems; ?></p>
        </div>
        <div class="chat-button">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="button" href="/c/<?php echo urlencode($userData['id']); ?>">Chat with <?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
                <p><a class="button" href="/login">Login to chat</a></p>
            <?php endif; ?>
        </div>
        <?php if ($user_id && $user_id != $userData['id']): ?>
    <div class="friend-button" style="margin-top:10px;">

        <?php if ($relationshipStatus === 'friends'): ?>

            <!-- Already friends -->
            <button disabled style="padding:8px 15px; background:green; color:white; border-radius:6px; border:none;">
                Friends
            </button>
            <form method="POST" action ="/api/friends/block">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="target_username" value="<?php echo htmlspecialchars($userData['username']); ?>">
                <button style="padding:8px 15px; background:red; color:white; border-radius:6px; border:none;">
                    Block
                </button>
            </form>

        <?php elseif ($relationshipStatus === 'pending_out'): ?>

            <button disabled style="padding:8px 15px; background:#888; color:white; border-radius:6px; border:none;">
                Request Sent...
            </button>

        <?php elseif ($relationshipStatus === 'pending_in'): ?>

            <form method="POST" action="/api/friends/accept">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="from_username" value="<?php echo $userData['username']; ?>">
                <button type="submit" style="padding:8px 15px; background:blue; color:white; border-radius:6px; border:none;">
                    Accept Friend Request
                </button>
            </form>

        <?php elseif ($relationshipStatus === 'blocked' || $relationshipStatus === 'blocked_by'): ?>

            <!-- Do not show friend button if blocked -->
            <p style="color:red;">Cannot add this user.</p>

        <?php else: ?>

            <form method="POST" action="/api/friends/add">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="target_username" value="<?php echo $userData['username']; ?>">
                <button type="submit" style="padding:8px 15px; background:#007bff; color:white; border-radius:6px; border:none;">
                    Add Friend
                </button>
            </form>
        <?php endif; ?>

    </div>
<?php endif; ?>

    </div>

    <div class="profile-header">
        <div class="profile-sample-header"><a href="/user/<?php echo $username; ?>/posts">Uploads</a></div>
        <div class="profile-sample-links">
            <a href="/user/<?php echo $username; ?>/posts"><b><?php echo $uploadsResult->num_rows; ?></b>total</a>
            <a href="/user/<?php echo $username; ?>/deleted"><b><?php echo $deletedCount; ?></b>Deleted</a>
        </div>
    </div>
    <?php if ($uploadsResult->num_rows > 0): ?>
<div style="margin-bottom: 1em;" class="gallery">
<?php while ($upload = $uploadsResult->fetch_assoc()): ?>
    <?php
        // Build full file path
        $filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($upload['file_name']);

        // Short display name
        $maxLength = 30;
        $displayName = htmlspecialchars(pathinfo($upload['display_name'], PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8');
        if (strlen($displayName) > $maxLength) {
            $displayName = substr($displayName, 0, $maxLength) . '...';
        }

        // Tooltip
        $tooltip  = $displayName . "\n";
        $tooltip .= "ID: " . $upload['id'] . "\n";
        $tooltip .= "Rating: " . $upload['category'] . "\n";
        $tooltip .= "Uploaded by: @" . htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8') . "\n";

        if (isset($_SESSION['user_id'])) {
            $tooltip .= "Date: " . convertToUserTimezone($upload['upload_date'], $server_timezone, $user_timezone_obj)
                     . " (" . htmlspecialchars($user_timezone) . ")\n\n";
        }

        $tooltip .= str_replace(',', ' ', $upload['tag_string']);

        // Detect file extension
        $extension = strtolower(pathinfo($upload['file_name'], PATHINFO_EXTENSION));
    ?>
    <div class="gallery-item">
        <a title="<?php echo htmlspecialchars($tooltip); ?>" href="/posts/<?php echo $upload['id']; ?>">
            
            <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                <!-- Video thumbnail using JS random-frame generator -->
                <video width="320" height="240" muted preload="metadata" class="gallery-video">
                    <source src="<?php echo $filePath; ?>" type="video/<?php echo $extension; ?>">
                </video>
            <?php else: ?>
                <img src="<?php echo $filePath; ?>" alt="<?php echo $displayName; ?>" />
            <?php endif; ?>
        </a>
    </div>
<?php endwhile; ?>
    <?php else: ?>
        <div style="margin-bottom: 1em;" class="gallery">
            <p style="gap: 20px;padding-left: 20px;position: relative;display: grid;">No uploads found.</p>
        </div>
    <?php endif; ?>
</div>
<div class="profile-header">
    <div class="profile-sample-header"><a href="/user/<?php echo $username; ?>/favorites">Favorites</a></div>
</div>
    <?php if ($favResult && $favResult->num_rows > 0): ?>
        <div class="gallery">
            <?php while ($fav = $favResult->fetch_assoc()): ?>
                <?php
                    $filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($fav['file_name']);
                    $displayName = htmlspecialchars(pathinfo($fav['display_name'], PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8');
                    if (strlen($displayName) > 30) $displayName = substr($displayName, 0, 30) . '...';
                    $extension = strtolower(pathinfo($fav['file_name'], PATHINFO_EXTENSION));
                ?>
                <div class="gallery-item">
                    <a title="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>" href="/posts/<?php echo $fav['id']; ?>">
                        <?php if (in_array($extension, ['mp4','webm','ogg'])): ?>
                            <video muted playsinline preload="metadata">
                                <source src="<?php echo $filePath; ?>" type="video/<?php echo $extension; ?>">
                            </video>
                        <?php else: ?>
                            <img loading="lazy" decoding="async" src="<?php echo $filePath; ?>" alt="<?php echo $displayName; ?>" />
                        <?php endif; ?>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="gallery">
            <p style="gap: 20px;padding-left: 20px;position: relative;display: grid;">No favorites found.</p>
        </div>
    <?php endif; ?>
</main>

<?php include('../includes/version.php'); ?>
<footer>
    <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. 
        <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a>
    </p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Parse createdAt from PHP and convert to Date object
    const createdAtRaw = "<?php echo $userData['created_at']; ?>";
    const createdAt = new Date(createdAtRaw);

    // Normalize createdAt date (strip time)
    const createdAtMidnight = new Date(createdAt.getFullYear(), createdAt.getMonth(), createdAt.getDate());

    // Target date: August 27, 2025 (midnight)
    const targetDate = new Date(2025, 7, 27); // Months are zero-indexed: 7 = August

    const username = "<?php echo addslashes($userData['username']); ?>";

    const badgeContainer = document.getElementById("legacy-badges");

    // Show legacy badge if createdAt is before August 27, 2025
    if (createdAtMidnight < targetDate) {
        if (badgeContainer) {
            const legacyBadge = document.createElement("div");
            legacyBadge.className = "badge legacy";
            legacyBadge.textContent = "Legacy User";
            badgeContainer.appendChild(legacyBadge);
        } else {
            console.error("Badge container not found");
        }
    }

    // Show super legacy badge if username contains space
    if (username.includes(" ")) {
        if (badgeContainer) {
            const superBadge = document.createElement("div");
            superBadge.className = "badge super-legacy";
            superBadge.textContent = "Super Legacy User";
            badgeContainer.appendChild(superBadge);
        }
    }

    function fetchNotifications() {
        fetch('../api/fetch_notifications')
            .then(response => response.json())
            .then(data => {
                let notificationCount = document.getElementById('notification-count');
                let notificationList = document.getElementById('notification-list');
                notificationList.innerHTML = '';

                if (data.length > 0) {
                    notificationCount.textContent = data.length;
                    data.forEach(notification => {
                        let div = document.createElement('div');
                        div.classList.add('notification-item');
                        div.innerHTML = `<a href="/you/notifications">${escapeHTML(notification.message)}</a>`;
                        notificationList.appendChild(div);
                    });
                } else {
                    notificationCount.textContent = "0";
                    notificationList.innerHTML = "<p>No new notifications</p>";
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    fetchNotifications();
    setInterval(fetchNotifications, 10000);

    document.getElementById('notification-icon').addEventListener('click', function () {
        let notificationList = document.getElementById('notification-list');
        notificationList.style.display = notificationList.style.display === 'none' ? 'block' : 'none';
    });
});

// Escapes HTML to prevent injection
function escapeHTML(str) {
    return str.replace(/[&<>"'`]/g, (char) => {
        const escapeMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
            '`': '&#096;'
        };
        return escapeMap[char];
    });
}
</script>
</body>
</html>