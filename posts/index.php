<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

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
$whereClause[] = "uploads.is_deleted = 0";

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
        uploads.id, uploads.file_name, uploads.category, uploads.uploaded_by, uploads.upload_date, uploads.display_name, uploads.tag_string, uploads.is_deleted,
        COALESCE(users.username, 'Unknown') AS username,
        GROUP_CONCAT(DISTINCT LOWER(TRIM(tags.tag_name)) ORDER BY tags.tag_name SEPARATOR ', ') AS tags,
        (SELECT COUNT(*) FROM image_likes WHERE image_id = uploads.id AND action = 'like') AS like_count,
        (SELECT COUNT(*) FROM image_likes WHERE image_id = uploads.id AND action = 'dislike') AS dislike_count,
        (SELECT COUNT(*) FROM favorites WHERE image_id = uploads.id) AS favorite_count,
        (SELECT COUNT(*) FROM comments WHERE image_id = uploads.id) AS comment_count
    FROM uploads
    LEFT JOIN users ON uploads.uploaded_by = users.id
    LEFT JOIN upload_tags ON uploads.id = upload_tags.upload_id
    LEFT JOIN tags ON upload_tags.tag_id = tags.tag_id
    $whereConditions
    GROUP BY uploads.id
    ORDER BY uploads.upload_date DESC
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

date_default_timezone_set('UTC');
$currentTime = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Posts - FluffFox</title>
    <link rel="stylesheet" href="../public/css/styles.css" />
    <link rel="stylesheet" href="assets/index.css" />
    
    <script>
        let serverTime = new Date(Date.UTC(
            <?php $t = explode(' ', $currentTime); $d = explode('-', $t[0]); $tm = explode(':', $t[1]); ?>
            <?php echo $d[0] ?>, <?php echo ((int)$d[1] - 1) ?>, <?php echo $d[2] ?>,
            <?php echo $tm[0] ?>, <?php echo $tm[1] ?>, <?php echo $tm[2] ?>
        ));

        function tick() {
            let options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: true, 
                timeZone: 'America/Chicago' 
            };

            let elem = document.getElementById("serverTime");
            if (elem) {
                elem.innerHTML = serverTime.toLocaleString("en-US", options);
            }
            serverTime.setSeconds(serverTime.getSeconds() + 1);
        }

        // Start clock immediately
        tick();
        setInterval(tick, 1000);

        // Sync with server every 30 seconds to correct drift
        setInterval(function() {
            fetch(window.location.href)
                .then(r => r.text())
                .then(html => {
                    let match = html.match(/serverTime">\s*(.+?)\s*<\/strong>/);
                    if (match) {
                        // Reset serverTime to current server time
                        serverTime = new Date(Date.UTC(
                            <?php echo $d[0] ?>, <?php echo ((int)$d[1] - 1) ?>, <?php echo $d[2] ?>,
                            <?php echo $tm[0] ?>, <?php echo $tm[1] ?>, <?php echo $tm[2] ?>
                        ));
                    }
                })
                .catch(err => console.log('Clock sync skipped'));
        }, 30000);
    </script>
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
<div class="main-content">

    <?php if ($result->num_rows > 0): ?>
        <div class="gallery">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                    $filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($row['file_name']);
                    
                    // Determine poster path for videos, fallback to default
                    $posterPath = !empty($row['poster']) 
                        ? "https://nixten.ddns.net:9001/data" . htmlspecialchars($row['poster']) 
                        : "/images/default-poster.jpg";

                    // Shorten display name if too long
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
    <img src="<?php echo $filePath ?>" loading="lazy" decoding="async" alt="<?php echo $displayName; ?>" />
<?php endif; ?>


                    </a>
                    <div class="post-info">
                        <div class="post-stats">
                            <span class="stat"><i class="fa-solid fa-up-down"></i><?php echo (int)$row['like_count'] - (int)$row['dislike_count']; ?></span>
                            <span class="stat"><i class="fa-solid fa-star"></i><?php echo (int)$row['favorite_count']; ?></span>
                            <span class="stat"><i class="fa-regular fa-comments"></i><?php echo (int)$row['comment_count']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No uploads found.</p>
    <?php endif; ?>

</div>
    <div class="pagination">
        <?php 
            $filterParam = isset($_GET['filter']) ? '&filter=' . htmlspecialchars($_GET['filter'], ENT_QUOTES, 'UTF-8') : '';
            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

            if ($currentPage < 1) {
                $currentPage = 1;
            }

            if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1 . $filterParam; ?>">&laquo; Previous</a>
            <?php endif; ?>

            <?php
            $pageRange = 2;
            $startPage = max(1, $currentPage - $pageRange);
            $endPage = min($totalPages, $currentPage + $pageRange);

            if ($startPage > 1):
                echo '<a href="?page=1' . $filterParam . '">1</a>';
                if ($startPage > 2) echo '<span class="dots">...</span>';
            endif;

            for ($i = $startPage; $i <= $endPage; $i++):
                echo '<a href="?page=' . $i . $filterParam . '" class="' . ($i === $currentPage ? 'active' : '') . '">' . $i . '</a>';
            endfor;

            if ($endPage < $totalPages):
                if ($endPage < $totalPages - 1) echo '<span class="dots">...</span>';
                echo '<a href="?page=' . $totalPages . $filterParam . '">' . $totalPages . '</a>';
            endif;
            ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1 . $filterParam; ?>">Next &raquo;</a>
            <?php endif; ?>
    </div>
</div>
</main>

<?php include('../includes/version.php'); ?>
<footer>
    <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    <p>Current Server Time: <strong id="serverTime"><?php echo date('l, F j, Y - h:i:s A'); ?> (CST/CDT)</strong></p>
</footer>
<script>
document.addEventListener("DOMContentLoaded", function() {
    function getCsrfToken() {
        const selectors = [
            'input[name="csrf_token"]',
            'input[name="csrf"]',
            'input[name="_csrf"]',
            'input[name="_token"]'
        ];
        for (const sel of selectors) {
            const el = document.querySelector(sel);
            if (el && el.value) return el.value;
        }
        const any = Array.from(document.querySelectorAll('input[type="hidden"]')).find(i => /csrf/i.test(i.name || ''));
        return any && any.value ? any.value : null;
    }

    document.querySelectorAll(".toggle-blacklist").forEach(button => {
        button.addEventListener("click", function () {
            const tag = (this.dataset.tag || "").trim();
            if (!tag) {
                console.error("Invalid tag detected");
                return;
            }

            const params = new URLSearchParams();
            params.append("tag", tag);

            const token = getCsrfToken();
            if (token) params.append("csrf_token", token);

            fetch("/actions/toggle_blacklist", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded", "X-Requested-With": "XMLHttpRequest" },
                body: params.toString()
            })
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok: " + response.status);
                return response.json();
            })
            .then(data => {
                if (data && data.status === "success") {
                    location.reload();
                } else {
                    console.error("Toggle blacklist failed:", data);
                    alert(data && data.error ? data.error : "Failed to toggle blacklist tag.");
                }
            })
            .catch(error => console.error("Error:", error));
        });
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const videos = document.querySelectorAll(".gallery video");

    videos.forEach(video => {
        // Load video metadata first
        video.addEventListener("loadedmetadata", () => {
            if (video.duration > 0) {
                // Pick a random time between 0% – 80% of the video
                const randomTime = Math.random() * (video.duration * 0.8);

                // Seek to that time
                video.currentTime = randomTime;
            }
        });

        // When frame is ready, capture it
        video.addEventListener("seeked", () => {
            try {
                const canvas = document.createElement("canvas");
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const ctx = canvas.getContext("2d");
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert to image for poster
                const frameImage = canvas.toDataURL("image/jpeg");

                // Set poster dynamically
                video.setAttribute("poster", frameImage);
            } catch (e) {
                // Ignore capture issues (cross-origin, etc.)
            }
        });
    });
});
</script>
</body>
</html>