<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// Pagination
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get logged-in user's info
$user_id = $_SESSION['user_id'];
$query = "SELECT id, username, bio, profile_picture, email, level, created_at, last_active, birthdate, online_status, email_visibility, birthday_visibility 
          FROM users WHERE id = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("User not found.");
}

$userData = $result->fetch_assoc();

// Profile picture setup
$profilePicture = (!empty($userData['profile_picture']))
    ? (strpos($userData['profile_picture'], '/public/') === 0
        ? $userData['profile_picture']
        : '/public/uploads/' . htmlspecialchars($userData['profile_picture'], ENT_QUOTES, 'UTF-8'))
    : '/public/images/default-profile.png';

// Main query: use tag_string only, no join on tags table

$whereClause = [];
$whereClause[] = "uploads.user_id = ?";
$whereClause[] = "uploads.is_deleted = 0"; // ✅ exclude deleted posts

$mainQuery = "
    SELECT *
    FROM uploads
    WHERE uploaded_by = ?
      AND is_deleted = 0
";

$params = [$userData['id']];
$types = "i";


// Total uploads count for pagination
$totalCountQuery = "
SELECT COUNT(DISTINCT up.id) AS total
FROM uploads up
LEFT JOIN upload_tags ut ON ut.upload_id = up.id
WHERE up.uploaded_by = ?
";

$mainQuery .= " ORDER BY upload_date DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $itemsPerPage;
$params[] = $offset;

$stmt = $db->prepare($mainQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$uploadsResult = $stmt->get_result();

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
    <title>Your Public Profile</title>
    <link rel="stylesheet" href="/public/css/styles.css" />
    
    <style>
        .status-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
            position: relative;
        }
        .online { background: green; }
        .offline { background: red; }

        /* New badge styles */
        .badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 6px;
            font-weight: bold;
            color: white;
        }
        .legacy {
            background-color: orange;
        }
        .super-legacy {
            background-color: red;
        }
        .owner-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 6px;
            font-weight: bold;
            color: white;
            background-color: purple;
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
        <h2 id="username"><?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
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
            <a class="button" href="/you/edit">Edit Profile</a>
        </div>
    </div>

    <h3 style="gap: 20px;padding-left: 20px;position: relative;display: grid;">User uploads:</h3>
    <?php if ($uploadsResult->num_rows > 0): ?>
<div class="gallery">
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
        <p style="gap: 20px;padding-left: 20px;position: relative;display: grid;">No uploads found.</p>
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