<?php
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Normalize path without query string
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // If no trailing slash and matches /posts/{id}
    if ($path === "/posts/$id") {
        $query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
            ? '?' . $_SERVER['QUERY_STRING']
            : '';

        header("Location: /posts/$id/$query", true, 301);
        exit();
    }
}
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

/**
 * Safely output HTML from comments, allowing only safe tags and CSS classes
 * @param string $html - The HTML string to sanitize
 * @return string - Sanitized HTML safe for display
 */
function sanitizeCommentHTML($html) {
    // Allowed HTML tags with their allowed attributes
    $allowedTags = '<p><span><div><strong><em><b><i><u><br><a><ul><ol><li><code><pre>';
    
    // Allowed CSS classes (from comment-designs.css)
    $allowedClasses = [
        'rainbow', 'fire', 'water', 'neon', 'galaxy', 'glitch', 'sparkle'
    ];
    
    // Use DOMDocument to parse and sanitize
    $dom = new DOMDocument('1.0', 'UTF-8');
    // Suppress warnings for malformed HTML
    libxml_use_internal_errors(true);
    // Wrap in a container div to handle fragments
    // Use a meta tag to specify UTF-8 encoding instead of deprecated mb_convert_encoding
    $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body><div>' . $html . '</div></body></html>';
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    
    // Get all elements
    $xpath = new DOMXPath($dom);
    $elements = $xpath->query('//*');
    
    if ($elements) {
        foreach ($elements as $element) {
            /** @var DOMElement $element */
            $tagName = strtolower($element->tagName);
            
            // Store class and href before removing attributes
            $classValue = $element->hasAttribute('class') ? $element->getAttribute('class') : '';
            $hrefValue = $element->hasAttribute('href') ? $element->getAttribute('href') : '';
            
            // Remove all attributes first
            while ($element->attributes->length > 0) {
                $element->removeAttribute($element->attributes->item(0)->nodeName);
            }
            
            // Allow class attribute for specific tags
            if (in_array($tagName, ['span', 'div', 'p']) && !empty($classValue)) {
                $classes = preg_split('/\s+/', trim($classValue));
                $safeClasses = array_intersect($classes, $allowedClasses);
                if (!empty($safeClasses)) {
                    $element->setAttribute('class', implode(' ', $safeClasses));
                }
            }
            
            // Allow href, target, rel for links
            if ($tagName === 'a' && !empty($hrefValue)) {
                // Only allow http, https, and relative URLs
                if (preg_match('/^(https?:\/\/|\/|#)/i', $hrefValue)) {
                    $element->setAttribute('href', htmlspecialchars($hrefValue, ENT_QUOTES, 'UTF-8'));
                    $element->setAttribute('target', '_blank');
                    $element->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }
    }
    
    // Get the sanitized HTML from the body div
    $body = $dom->getElementsByTagName('body')->item(0);
    if ($body) {
        $div = $body->getElementsByTagName('div')->item(0);
        if ($div) {
            $sanitized = '';
            foreach ($div->childNodes as $node) {
                $sanitized .= $dom->saveHTML($node);
            }
            return $sanitized;
        }
    }
    
    // Fallback: get all HTML and extract content
    $sanitized = $dom->saveHTML();
    // Remove HTML/head/body tags and extract just the div content
    $sanitized = preg_replace('/^.*?<body[^>]*>/is', '', $sanitized);
    $sanitized = preg_replace('/<\/body>.*?$/is', '', $sanitized);
    $sanitized = preg_replace('/^<div>/', '', $sanitized);
    $sanitized = preg_replace('/<\/div>$/', '', $sanitized);
    
    return $sanitized;
}

$userId = $_SESSION['user_id'] ?? 0;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid image ID.");
}

$imageId = intval($_GET['id']);
if ($imageId === 0) {
    die("Invalid image ID.");
}

// Handle temporary per-image blacklist toggles (disable / re-enable) - MUST be before header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['disable_blacklist'])) {
        // mark this image as temporarily un-blacklisted for this user
        $_SESSION['temp_disable_blacklist'] = $_SESSION['temp_disable_blacklist'] ?? [];
        $_SESSION['temp_disable_blacklist'][$imageId] = true;
        // Redirect to prevent duplicate submissions
        header("Location: /posts/$imageId/");
        exit();
    }

    if (isset($_POST['enable_blacklist'])) {
        if (!empty($_SESSION['temp_disable_blacklist'][$imageId])) {
            unset($_SESSION['temp_disable_blacklist'][$imageId]);
        }
        // Redirect to prevent duplicate submissions
        header("Location: /posts/$imageId/");
        exit();
    }

    if (isset($_POST['disable_all_blacklist'])) {
        // Get image tags first (we need them to know which tags to disable)
        $tempImageTags = [];
        if ($userId) {
            $tempTagStmt = $db->prepare("
                SELECT t.tag_name
                FROM tags t
                JOIN upload_tags ut ON t.tag_id = ut.tag_id
                WHERE ut.upload_id = ?
            ");
            if ($tempTagStmt) {
                $tempTagStmt->bind_param("i", $imageId);
                $tempTagStmt->execute();
                $tempTagResult = $tempTagStmt->get_result();
                while ($tempTagRow = $tempTagResult->fetch_assoc()) {
                    $tempImageTags[] = $tempTagRow['tag_name'];
                }
                $tempTagStmt->close();
            }
        }
        
        // Get blacklisted tags for this image (case-insensitive)
        $tagsToDisable = [];
        if (!empty($tempImageTags) && $userId) {
            $placeholders = implode(',', array_fill(0, count($tempImageTags), '?'));
            $types = str_repeat('s', count($tempImageTags));
            // Normalize tempImageTags to lowercase for comparison
            $tempImageTagsLower = array_map('strtolower', array_map('trim', $tempImageTags));
            $query = "SELECT tag FROM user_blacklist WHERE user_id = ? AND LOWER(TRIM(tag)) IN ($placeholders)";
            $tempBlacklistQuery = $db->prepare($query);
            $tempParams = array_merge([$userId], $tempImageTagsLower);
            $tempRefs = [];
            foreach ($tempParams as $key => $value) {
                $tempRefs[$key] = &$tempParams[$key];
            }
            call_user_func_array([$tempBlacklistQuery, 'bind_param'], array_merge(["i" . $types], $tempRefs));
            $tempBlacklistQuery->execute();
            $tempBlacklistResult = $tempBlacklistQuery->get_result();
            while ($tempRow = $tempBlacklistResult->fetch_assoc()) {
                // Store as lowercase to match how toggle_blacklist.php stores them
                $tagsToDisable[] = strtolower(trim($tempRow['tag']));
            }
            $tempBlacklistQuery->close();
        }
        
        // Add all blacklisted tags to disabled_blacklist_tags
        if (!isset($_SESSION['disabled_blacklist_tags'])) {
            $_SESSION['disabled_blacklist_tags'] = [];
        }
        foreach ($tagsToDisable as $tag) {
            $tagLower = strtolower(trim($tag));
            // Check if already disabled
            $alreadyDisabled = false;
            foreach ($_SESSION['disabled_blacklist_tags'] as $disabledTag) {
                if (strtolower(trim($disabledTag)) === $tagLower) {
                    $alreadyDisabled = true;
                    break;
                }
            }
            if (!$alreadyDisabled) {
                $_SESSION['disabled_blacklist_tags'][] = $tag;
            }
        }
        
        // Redirect to prevent duplicate submissions
        header("Location: /posts/$imageId/");
        exit();
    }

    // Handle comment submission - MUST be before header.php
    if (isset($_POST['comment']) && isset($_SESSION['user_id'])) {
        $commentText = trim($_POST['comment'] ?? '');  
        $userId = $_SESSION['user_id'];
        $parentId = isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

        if (!empty($commentText)) {
            $commentQuery = $db->prepare("INSERT INTO comments (image_id, user_id, comment, parent_id) VALUES (?, ?, ?, ?)");
            $commentQuery->bind_param("iisi", $imageId, $userId, $commentText, $parentId);
            $commentQuery->execute();
            $commentQuery->close();
            // Redirect to prevent duplicate submissions
            header("Location: /posts/$imageId/");
            exit();
        }
    }
}

include_once('../includes/header.php');

$params = [];
$paramTypes = "";

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

$imageTags = [];
$tagStmt = $db->prepare("
    SELECT t.tag_name, t.description
    FROM tags t
    JOIN upload_tags ut ON t.tag_id = ut.tag_id
    WHERE ut.upload_id = ?
");
if ($tagStmt) {
    $tagStmt->bind_param("i", $imageId);
    $tagStmt->execute();
    $tagResult = $tagStmt->get_result();
    while ($tagRow = $tagResult->fetch_assoc()) {
        $imageTags[] = $tagRow['tag_name'];
    }
    $tagStmt->close();
}

// Find which blacklisted tags from this image are currently disabled (in disabled_blacklist_tags)
$disabledTagsForImage = [];
if (!empty($blacklistedTagsForImage) && !empty($disabledTags)) {
    foreach ($blacklistedTagsForImage as $blacklistedTag) {
        $tagLower = strtolower(trim($blacklistedTag));
        // Check if this tag is in the disabled list (case-insensitive)
        foreach ($_SESSION['disabled_blacklist_tags'] ?? [] as $disabledTag) {
            if (strtolower(trim($disabledTag)) === $tagLower) {
                $disabledTagsForImage[] = $blacklistedTag; // Use original case from database
                break;
            }
        }
    }
}

$isBlacklisted = false;
$blacklistedTagsForImage = []; // Tags from this image that are in user's blacklist
if (!empty($imageTags) && $userId) {
    // Use case-insensitive comparison to match tags regardless of case in database
    $placeholders = implode(',', array_fill(0, count($imageTags), '?'));
    $types = str_repeat('s', count($imageTags));
    // Normalize imageTags to lowercase for comparison
    $imageTagsLower = array_map('strtolower', array_map('trim', $imageTags));
    $query = "SELECT tag FROM user_blacklist WHERE user_id = ? AND LOWER(TRIM(tag)) IN ($placeholders)";
    $blacklistQuery = $db->prepare($query);
    $params = array_merge([$userId], $imageTagsLower);
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    call_user_func_array([$blacklistQuery, 'bind_param'], array_merge(["i" . $types], $refs));
    $blacklistQuery->execute();
    $blacklistResult = $blacklistQuery->get_result();
    while ($row = $blacklistResult->fetch_assoc()) {
        $blacklistedTagsForImage[] = $row['tag']; // Keep original case from database for display
    }
    $isBlacklisted = count($blacklistedTagsForImage) > 0;
    $blacklistQuery->close();
}

// Ensure $temporarilyDisabled is always defined (per-image first, fallback to legacy flag)
 $temporarilyDisabled = false;
 if (!empty($_SESSION['temp_disable_blacklist']) && is_array($_SESSION['temp_disable_blacklist'])) {
     $temporarilyDisabled = !empty($_SESSION['temp_disable_blacklist'][$imageId]);
 } elseif (!empty($_SESSION['disabled_blacklist'])) {
     // legacy global flag fallback
     $temporarilyDisabled = true;
 }

$query = "SELECT uploads.*, users.username, uploads.uploaded_by, uploads.upload_date, uploads.display_name, description, source, is_deleted
          FROM uploads 
          JOIN users ON uploads.uploaded_by = users.id 
          WHERE uploads.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $imageId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($result->num_rows === 0) {
    die("Hmmmmmmmmmm looks like that image was not found on our database or was deleted, <a href='/posts/'>go back to posts?</a>");
}

$image = $row; // Use the already-fetched row, don't fetch again
$imageOwnerId = $image['uploaded_by'];

$imageWidth = $image['image_width'];
$imageHeight = $image['image_height'];
$imageFileSizeMB = round($image['file_size'] / 1024 / 1024, 2); // Convert bytes to MB


$filePath = "https://nixten.ddns.net:9001/data" . htmlspecialchars($image['file_name']);

$isDeleted = $image['is_deleted'] == 1;
$isVideo = in_array($image['file_ext'], ['mpeg', 'ogv', 'ts', '3gp', '3g2', 'avi', 'webm', 'mp4']);

$likesQuery = $db->prepare("SELECT COUNT(*) FROM image_likes WHERE image_id = ? AND action = 'like'");
$likesQuery->bind_param("i", $imageId);
$likesQuery->execute();
$likesResult = $likesQuery->get_result();
$imageLikes = $likesResult->fetch_row()[0];
$likesQuery->close();

$dislikesQuery = $db->prepare("SELECT COUNT(*) FROM image_likes WHERE image_id = ? AND action = 'dislike'");
$dislikesQuery->bind_param("i", $imageId);
$dislikesQuery->execute();
$dislikesResult = $dislikesQuery->get_result();
$imageDislikes = $dislikesResult->fetch_row()[0];
$dislikesQuery->close();

// Get current user's vote
$userVoteQuery = $db->prepare("SELECT action FROM image_likes WHERE image_id = ? AND user_id = ?");
$userVoteQuery->bind_param("ii", $imageId, $userId);
$userVoteQuery->execute();
$userVoteResult = $userVoteQuery->get_result();
$userVote = $userVoteResult->fetch_row()[0] ?? null;
$userVoteQuery->close();

$isLiked = ($userVote === 'like');
$isDisliked = ($userVote === 'dislike');


// Note: Comment handling has been moved to the POST handler section at the top (before header.php)
// to prevent "headers already sent" errors

$comments = [];
$commentsById = [];

$commentsQuery = $db->prepare("SELECT comments.*, users.username
                               FROM comments
                               JOIN users ON comments.user_id = users.id
                               WHERE comments.image_id = ?
                               ORDER BY comments.created_at ASC");
$commentsQuery->bind_param("i", $imageId);
$commentsQuery->execute();
$commentsResult = $commentsQuery->get_result();

while ($row = $commentsResult->fetch_assoc()) {
    $row['replies'] = [];
    $row['likes'] = 0;
    $row['dislikes'] = 0;
    $commentsById[$row['id']] = $row;
}
$commentsQuery->close();

foreach ($commentsById as $id => &$comment) {
    if ($comment['parent_id']) {
        if (isset($commentsById[$comment['parent_id']])) {
            $commentsById[$comment['parent_id']]['replies'][] = &$comment;
        }
    } else {
        $comments[] = &$comment;
    }
}
unset($comment);

foreach ($commentsById as &$comment) {
    $likesQuery = $db->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND action = 'like'");
    $likesQuery->bind_param("i", $comment['id']);
    $likesQuery->execute();
    $likesResult = $likesQuery->get_result();
    $comment['likes'] = $likesResult->fetch_row()[0];

    $dislikesQuery = $db->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND action = 'dislike'");
    $dislikesQuery->bind_param("i", $comment['id']);
    $dislikesQuery->execute();
    $dislikesResult = $dislikesQuery->get_result();
    $comment['dislikes'] = $dislikesResult->fetch_row()[0];
}
unset($comment);

$isFavorited = false;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $checkQuery = $db->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND image_id = ?");
    $checkQuery->bind_param("ii", $userId, $imageId);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();
    $isFavorited = $checkResult->num_rows > 0;
    $checkQuery->close();
}

$fileExtension = pathinfo($image['file_name'], PATHINFO_EXTENSION);


$tagCounts = [];
if (!empty($imageTags)) {
    $tagCountStmt = $db->prepare("SELECT COUNT(*) FROM upload_tags ut JOIN uploads u ON ut.upload_id = u.id JOIN tags t ON ut.tag_id = t.tag_id WHERE t.tag_name = ?");
    foreach ($imageTags as $tag) {
        $trimmedTag = trim($tag);
        $tagCountStmt->bind_param("s", $trimmedTag);
        $tagCountStmt->execute();
        $tagCountStmt->bind_result($count);
        $tagCountStmt->fetch();
        $tagCounts[$trimmedTag] = $count;
    }
    $tagCountStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (isset($_SESSION['csrf_token'])): ?>
    <script>
        window.csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>";
    </script>
    <?php endif; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>#<?php echo htmlspecialchars(pathinfo($image['id'], PATHINFO_FILENAME)); ?> - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <link rel="stylesheet" href="/posts/assets/view.css">
    <link rel="stylesheet" href="/posts/assets/comment-designs.css">   
    <link href="https://nixten.ddns.net:3001/js/<?php echo $version; ?>/video-js.min.css" rel="stylesheet">
    <script src="https://nixten.ddns.net:3001/js/<?php echo $version; ?>/video.min.js"></script>
    <script src="https://nixten.ddns.net:3001/js/<?php echo $version; ?>/Vplayer.js"></script>
    <script src="https://kit.fontawesome.com/8d091fb1f3.js" crossorigin="Nixten Kame"></script>
</head>
<body>
    <nav><?php include_once '../includes/nav.php'; ?></nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <main>
        <div class="page-layout" style="display:flex; gap:24px; align-items:flex-start;">
            <aside class="sidebar" style="flex: 0 0 280px;">
                <div class="sidebar-container">
                    <div class="search-section">
                        <form action="/posts/" method="GET">
                            <h2>Search:</h2>
                            <label for="tags"><i class="fa-solid fa-magnifying-glass"></i></label>
                            <input class="search-bar" type="text" name="q" placeholder="Search posts by tag" required />
                        </form>
                    </div>
                    <h2><?php echo htmlspecialchars(pathinfo($image['display_name'], PATHINFO_FILENAME)); ?></h2>
                    <p><strong>Tags:</strong>
                        <?php
                        if (!empty($imageTags)) {
                            $outputTags = [];
                            foreach ($imageTags as $tag) {
                                $trimmedTag = trim($tag);
                                $count = $tagCounts[$trimmedTag] ?? 0;
                                $outputTags[] = '<a href="/posts/?q=' . urlencode($trimmedTag) . '" class="tag-link">'
                                                . htmlspecialchars($trimmedTag) . ' (' . $count . ')</a>';
                            }
                            echo implode(' ', $outputTags);
                        } else {
                            echo '<em>No tags</em>';
                        }
                        ?>
                    </p>
                    <h3 style="color: white;">Information:</h3>
                    <ul>
                        <li>Source: <?php echo htmlspecialchars($image['source'] ?: 'N/A'); ?></li>
                    </ul>
                    <ul style="text-decoration: underline;">
                        <li>ID: <?php echo htmlspecialchars($image['id']); ?></li>
                        <?php
                        $fileName = $image['file_name'] ?? 'unknown';
                        $fileName = preg_replace('/^sfw\//', '', $fileName);
                        $maxLength = 10;
                        if (strlen($fileName) > $maxLength) {
                            $fileName = substr($fileName, 0, $maxLength - 3) . '...';
                        }
                        ?>
                        <li>File Name: <?= htmlspecialchars($fileName) ?></li>
                        <li>Size: <?php echo $imageWidth . ' x ' . $imageHeight; ?> (<?php echo $imageFileSizeMB . ' MB)'; ?></li>
                        <li>Type: <?php echo strtoupper($fileExtension); ?></li>
                        <br>
                        <li>Rating: <?php echo htmlspecialchars(ucfirst($image['category'])); ?></li>
                        <li>Score: <?php echo ($imageLikes - $imageDislikes); ?> (<?php echo $imageLikes; ?> Likes, <?php echo $imageDislikes; ?> Dislikes)</li>
                        <li>Faves: <?php
                            $faveQuery = $db->prepare("SELECT COUNT(*) FROM favorites WHERE image_id = ?");
                            $faveQuery->bind_param("i", $imageId);
                            $faveQuery->execute();
                            $faveResult = $faveQuery->get_result();
                            $faveCount = $faveResult->fetch_row()[0];
                            $faveQuery->close();
                            echo $faveCount;
                        ?></li>
                        <li>Uploaded by: <?php echo htmlspecialchars($image['username']); ?></li>
                    </ul>
                    <br>
                    <ul>
                        <li>Posted: <?php echo htmlspecialchars($image['upload_date']); ?></li>
                    </ul>
                    <h3 style="color: white;">Options:</h3>
                        <a class="side-bar-link" href="/posts/<?php echo $imageId; ?>/edit">Edit</a>
                        <a class="side-bar-link" href="https://nixten.ddns.net:9001/data<?php echo $image['file_name']; ?>">Download</a>
                        <a class="side-bar-link" href="/help/report">Report</a>
                    <br>
                    <h3 style="color: white;">Related:</h3>
                        <button class="side-bar-link" type="button" onclick="window.location.href='/random_image'">Random</button>
                        <button class="side-bar-link" type="button" onclick="window.location.href='/tags'">Tags</button>
                        <button class="side-bar-link" type="button" onclick="window.location.href='/posts/'">Posts</button>
                        <button class="side-bar-link" type="button" onclick="window.location.href='/popular_uploads'">Popular</button>


                    <section>
                        <h3 style="color: white;">Actions:</h3>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="/actions/favorite" method="POST" style="margin-bottom: 0%;">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($imageId); ?>">

                                <button type="submit" class="side-bar-link" id="favorite-btn" data-image-id="<?php echo htmlspecialchars($imageId); ?>">
                                    <?php echo $isFavorited ? "Unfavorite" : "Favorite"; ?>
                                </button>

                                <a class="side-bar-link" href="/you/favorites">Favorites</a>
                                <a class="side-bar-link" href="/help/report">Report</a>

                                <button type="button" class="side-bar-link image-like-btn" data-image-id="<?php echo $imageId; ?>">
                                    Like (<?php echo $imageLikes; ?>)
                                </button>
                                <button type="button" class="side-bar-link image-dislike-btn" data-image-id="<?php echo $imageId; ?>">
                                    Dislike (<?php echo $imageDislikes; ?>)
                                </button>

                                <a href="<?php echo htmlspecialchars($filePath); ?>" class="side-bar-link" download>Download</a>
                                <a href="<?php echo htmlspecialchars($filePath); ?>" class="side-bar-link" target="_blank" rel="noopener">View Source</a>
                                <a class="side-bar-link" href="/posts/<?php echo $imageId; ?>/edit">Edit Tags</a>
                                <?php if ($_SESSION['user_id'] == $imageOwnerId): ?>
                                    <div style="margin-top:8px;">
                                        <strong>Creator Tools:</strong><br>
                                        <a class="side-bar-link delete-btn" href="/posts/delete?id=<?php echo $imageId; ?>" style="margin-top:6px; cursor: pointer;">
                                            Delete Post
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </section>
                </div>
            </aside>
<div class="content-right" style="flex:1; min-width:0;">
    <div class="image-panel" style="margin-bottom:16px;">

        <?php if (!empty($image['is_deleted']) && $image['is_deleted'] == 1): ?>
            <p class="deleted-warning" style="color: #b71c1c; font-weight: bold;">This image has been deleted.</p>
            <div class="view-image-container" style="text-align:center;">
                <img id="view-image-850" src="/public/images/deleted-preview.png" alt="Deleted Content" style="max-width: 100%;">
            </div>

        <?php elseif ($isBlacklisted && !$temporarilyDisabled): ?>
            <div class="view-image-container" style="text-align:center;">
                <p class="blacklisted-warning">⚠️ This image has been blacklisted by you.</p>
                <img src="/public/images/blacklisted-preview.png" alt="Blacklisted Content" style="max-width:100%;">
                <form method="POST" action="">
                    <?php echo csrf_input(); ?>
                    <button class="toggle_blacklist" name="disable_blacklist" type="submit">Temporarily Disable Blacklist</button>
                </form>
            </div>

        <?php elseif ($isBlacklisted && $temporarilyDisabled): ?>
            <div class="view-image-container" style="text-align:center;">
                <p class="blacklisted-warning">⚠️ This image has been blacklisted by you (temporarily disabled).</p>
                <img id="view-image"
                     src="<?php echo htmlspecialchars($filePath); ?>"
                     alt="<?php echo htmlspecialchars(pathinfo($image['file_name'], PATHINFO_FILENAME)); ?>"
                     onerror="this.onerror=null; this.src='/public/images/image-not-found.png';"
                     class="view-image-850" style="cursor:zoom-in; max-width:100%;">
                <form method="POST" action="" style="margin-top: 10px;">
                    <?php echo csrf_input(); ?>
                    <button name="enable_blacklist" type="submit">Re-enable Blacklist</button>
                </form>
            </div>

        <?php else: ?>
            <div class="view-image-container" style="text-align:center;">
                <?php if ($isVideo): ?>
                    <video id="view-image" controls preload="auto" data-setup='{}'>
                        <source src="<?php echo htmlspecialchars($filePath); ?>" type="video/webm">
                        <p class="vjs-no-js"></p>
                    </video>
                <?php else: ?>
                    <img id="view-image"
                         src="<?php echo htmlspecialchars($filePath); ?>"
                         alt="<?php echo htmlspecialchars(pathinfo($image['file_name'], PATHINFO_FILENAME)); ?>"
                         onerror="this.onerror=null; this.src='/public/images/image-not-found.png';"
                         class="view-image-850" style="cursor:zoom-in; max-width:100%;">
                <?php endif; ?>

                <section class="image-size-right-sidebar" style="margin-bottom:20px;">
                    <div class="size-toggle-group" aria-hidden="false" style="text-align:center;">
                        <div class="score-info">
                            <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($imageId); ?>">
                            <button type="button" id="like-btn" class="button image-like-btn" data-image-id="<?php echo $imageId; ?>" style="color:<?php echo $isLiked ? 'white' : 'green'; ?>;margin-right:0;border-right:solid 5px green;border-top-right-radius:0;border-bottom-right-radius:0;<?php echo $isLiked ? 'background:green;border-color:green;' : ''; ?>">▲</button>
                            <ul style="list-style: none; margin: 10px 0 10px 0; border-radius: 0;" class="score">
                                <li title="Likes: <?php echo $imageLikes; ?>, Dislikes: <?php echo $imageDislikes; ?>"><?php echo ($imageLikes - $imageDislikes); ?></li>
                            </ul>
                            <button type="button" id="dislike-btn" class="button image-dislike-btn" data-image-id="<?php echo $imageId;?>" style="color: <?php echo $isDisliked ? 'white' : 'red'; ?>; margin-left: 0; border-left: solid 5px red; border-top-left-radius: 0; border-bottom-left-radius: 0;<?php echo $isDisliked ? 'background:red;border-color:red;' : ''; ?>">▼</button>
                        </div>

                        <form action="/actions/favorite" method="POST">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($imageId); ?>">
                            <button data-favorited="true" type="submit" class="button favorite-btn" id="favorite-btn" data-image-id="<?php echo htmlspecialchars($imageId); ?>"><?php echo $isFavorited ? "<i class='fa-solid fa-star'></i>" : "<i class='fa-regular fa-star'></i>"; ?></button>
                        </form>

                        <div class="ptbr-resize">
                            <select class="button">
                                <option id="size-toggle-full" class="button">Original</option>
                                <option id="size-toggle-850" class="button" aria-pressed="true" selected>Sample (850px)</option>
                            </select>
                        </div>

                        <a class="button" href="<?php echo $filePath; ?>">View</a>

                        <div class="ptbr-etc">
                            <button style="min-width: min-content;" class="button toggle-ptbr-etc-menu"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            <div style="display: none;" class="ptbr-etc-menu">
                                <a href="/posts/download?file=<?php echo urlencode($filePath); ?>">Download</a>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if (!empty($disabledTagsForImage)): ?>
                    <div style="margin-top: 10px; padding: 10px; border-radius: 4px;">
                        <p style="margin-bottom: 8px; font-weight: bold;">Temporarily disabled blacklist tags:</p>
                        <?php foreach ($disabledTagsForImage as $disabledTag): ?>
                            <?php $tagLower = strtolower(trim($disabledTag)); ?>
                            <button type="button" class="reenable-blacklist side-bar-link" data-tag="<?php echo htmlspecialchars($tagLower); ?>" style="margin: 4px;">
                                Re-enable: <?php echo htmlspecialchars($disabledTag); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div> <!-- end view-image-container -->
        <?php endif; ?>

    </div> <!-- end image-panel -->

    <!-- Description Section -->
    <section class="description-right-sidebar">
        <div class="description-box">
            <h3>Description</h3>
            <p>
                <?php if (!empty($image['description'])): ?>
                    <?php echo nl2br(htmlspecialchars($image['description'])); ?>
                <?php else: ?>
                    <em>No description available.</em>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <!-- Comments Section -->
    <section class="comment-section">
        <h3>Comments</h3>
        <?php if (count($comments) > 0): ?>
            <ul>
                <?php foreach ($comments as $index => $comment): ?>
                    <li class="comment">
                        <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong> 
                        <span><?php echo sanitizeCommentHTML($comment['comment']); ?></span>  
                        <br>
                        <span class="comment-meta">ID: <?php echo $comment['id']; ?> | #<?php echo $index; ?></span>
                        <br>
                        <span class="comment-meta">Date created: <?php echo $comment['created_at']; ?></span>
                        <div class="button-row">
                            <button class="like-btn" data-comment-id="<?php echo $comment['id']; ?>"><i class="fa-solid fa-thumbs-up"></i> (<?php echo $comment['likes']; ?>)</button>
                            <button class="dislike-btn" data-comment-id="<?php echo $comment['id']; ?>"><i class="fa-solid fa-thumbs-down"></i> (<?php echo $comment['dislikes']; ?>)</button>
                            <button class="reply-btn" data-comment-id="<?php echo $comment['id']; ?>"><i class="fa-solid fa-reply"></i> Reply</button>
                        </div>

                        <?php if (!empty($comment['replies'])): ?>
                            <ul class="replies">
                                <?php foreach ($comment['replies'] as $reply): ?>
                                    <li class="comment reply">
                                        <strong><?php echo htmlspecialchars($reply['username']); ?>:</strong>
                                        <span><?php echo sanitizeCommentHTML($reply['comment']); ?></span>
                                        <br>
                                        <span class="comment-meta">Reply ID: <?php echo $reply['id']; ?></span>
                                        <div class="button-row">
                                            <?php echo csrf_input(); ?>
                                            <button class="like-btn" data-comment-id="<?php echo $reply['id']; ?>"><i class="fa-solid fa-thumbs-up"></i> (<?php echo $reply['likes'] ?? 0; ?>)</button>
                                            <button class="dislike-btn" data-comment-id="<?php echo $reply['id']; ?>"><i class="fa-solid fa-thumbs-down"></i> (<?php echo $reply['dislikes'] ?? 0; ?>)</button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-comments">No comments yet.</p>
        <?php endif; ?>
    </section>

    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="POST" class="add-comment-form">
            <?php echo csrf_input(); ?>
            <textarea name="comment" rows="5" placeholder="Add your comment..."></textarea>
            <input class="post-comment button" type="submit" value="Post Comment">
            <a class="comment-guides-button" href="/static/docs/comment-ee/">How to do styled comments?</a>
        </form>
    <?php else: ?>
        <p>You must <a href="/login">login</a> to post a comment.</p>
    <?php endif; ?>

</div>

        </div>
    </main>
    <?php include('../includes/version.php'); ?>
    <footer>
        <p>&copy; 2025 FluffFox. All Rights Reserved.
        <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
<script src="https://cdn.tiny.cloud/1/ps49nsqt16otrzd8qtk8mvmpp3s87geescqvseq15vwf0bqs/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
const CSRF = (function () {
  const token = (typeof csrfToken !== 'undefined' ? csrfToken : (document.querySelector('meta[name="csrf-token"]')?.content || null));
  return {
    token,
    params(obj = {}) {
      const p = new URLSearchParams(obj);
      if (this.token) p.append('csrf_token', this.token);
      return p;
    },
    appendToFormData(fd) {
      if (this.token && fd instanceof FormData) fd.append('csrf_token', this.token);
      return fd;
    }
  };
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const isDark = body.classList.contains('dark');

    tinymce.init({
        selector: 'textarea[name="comment"]',
        plugins: 'link lists code',
        toolbar: 'undo redo | bold italic | link | bullist numlist | code | source',
        menubar: false,
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
        valid_elements: 'p,span,div,strong,em,b,i,u,br,a[href|target|rel],ul,ol,li,code,pre',
        valid_classes: 'rainbow,fire,water,neon,galaxy,glitch,sparkle',
        extended_valid_elements: 'span[class],div[class],p[class]'
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const favBtn = document.getElementById("favorite-btn");
    if (!favBtn) return;

    favBtn.addEventListener("click", function(event) {
        event.preventDefault();

        const imageId = this.getAttribute("data-image-id");
        const button = this;

        fetch("/actions/favorite", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" },
            body: CSRF.params({ image_id: imageId }).toString()
        })
        .then(async response => {
            const text = await response.text();
            try { return JSON.parse(text); } catch(e) { return { error: "invalid_json", raw: text }; }
        })
        .then(data => {
            if (data && data.status) {
                button.textContent = data.status === "added" ? "Unfavorite" : "Favorite";
            } else {
                console.error("Favorite error:", data);
                alert("Failed to update favorite.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Network error while updating favorite.");
        });
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".like-btn, .dislike-btn").forEach(function(button) {
        button.addEventListener("click", function () {
            const commentId = this.getAttribute("data-comment-id");
            const action = this.classList.contains("like-btn") ? "like" : "dislike";
            const btn = this;

            fetch("/includes/like_comment", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" },
                body: CSRF.params({ comment_id: commentId, action }).toString()
            })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); } catch (e) { return { success: false, raw: text }; }
            })
            .then(data => {
                if (data && data.success) {
                    const parent = btn.closest(".comment");
                    if (!parent) return;
                    const likeBtn = parent.querySelector(".like-btn");
                    const dislikeBtn = parent.querySelector(".dislike-btn");
                    if (likeBtn) likeBtn.textContent = `Like (${data.likes})`;
                    if (dislikeBtn) dislikeBtn.textContent = `Dislike (${data.dislikes})`;
                    if (likeBtn) likeBtn.classList.toggle("voted", data.userAction === 'like');
                    if (dislikeBtn) dislikeBtn.classList.toggle("voted", data.userAction === 'dislike');
                } else {
                    console.warn("Like/dislike failed:", data);
                    alert("Failed to update like/dislike.");
                }
            })
            .catch(err => {
                console.error("Like error:", err);
                alert("Network error while updating like/dislike.");
            });
        });
    });
});
</script>
<script>
document.getElementById("like-btn").addEventListener("click", () => {
    // Create popup
    const popup = document.createElement("div");
    popup.textContent = "Image successfully liked/removed like, reloading page in 5 seconds...";

    // Style popup
    popup.style.position = "fixed";
    popup.style.display = "flex";
    popup.style.background = "green";
    popup.style.top = "20px";
    popup.style.right = "20px";
    popup.style.left = "20px";
    popup.style.Width = "100%";
    popup.style.color = "#fff";
    popup.style.padding = "10px 15px";
    popup.style.borderRadius = "8px";
    popup.style.fontSize = "14px";
    popup.style.boxShadow = "0 0 10px rgba(0,0,0,0.3)";
    popup.style.zIndex = "9999";

    // Add to page
    document.body.appendChild(popup);

    // Reload after 5 seconds
    setTimeout(() => {
        window.location.reload();
    }, 5000);
});
</script>
<script>
const menuElement = document.querySelector('.ptbr-etc-menu');
document.querySelector('.toggle-ptbr-etc-menu').addEventListener('click', function (e) {
    menuElement.style.display = menuElement.style.display === 'none' ? 'flex' : 'none';
});
</script>
<script>
document.addEventListener('click', function (e) {
    if (!e.target.closest('body')) {  // If the click is outside the menu
        menuElement.classList.remove('show'); // Close the dropdown
    }
});
</script>
<script>
document.getElementById("dislike-btn").addEventListener("click", () => {
    // Create popup
    const popup = document.createElement("div");
    popup.textContent = "Image successfully disliked/removed dislike, reloading page in 5 seconds...";

    // Style popup
    popup.style.position = "fixed";
    popup.style.display = "flex";
    popup.style.background = "red";
    popup.style.top = "20px";
    popup.style.right = "20px";
    popup.style.left = "20px";
    popup.style.Width = "100%";
    popup.style.color = "#fff";
    popup.style.padding = "10px 15px";
    popup.style.borderRadius = "8px";
    popup.style.fontSize = "14px";
    popup.style.boxShadow = "0 0 10px rgba(0,0,0,0.3)";
    popup.style.zIndex = "9999";

    // Add to page
    document.body.appendChild(popup);

    // Reload after 5 seconds
    setTimeout(() => {
        window.location.reload();
    }, 5000);
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".reply-btn").forEach(function(button) {
        button.addEventListener("click", function () {
            const commentId = this.getAttribute("data-comment-id");
            document.querySelectorAll(".reply-form").forEach(f => f.remove());

            const form = document.createElement("form");
            form.className = "reply-form";
            form.innerHTML = `
                <textarea name="reply" rows="2" cols="60" placeholder="Write your reply..." required></textarea>
                <input type="hidden" name="parent_id" value="${commentId}">
                <button type="submit" class="button">Post Reply</button>
            `;
            this.parentElement.appendChild(form);

            form.addEventListener("submit", function (e) {
                e.preventDefault();
                const replyText = form.querySelector("textarea[name='reply']").value.trim();
                if (!replyText) return alert("Reply cannot be empty.");

                fetch(window.location.pathname, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "text/html, application/json" },
                    body: CSRF.params({ comment: replyText, parent_id: commentId }).toString()
                })
                .then(res => {
                    if (res.ok) {
                        // ideally update the thread via DOM manipulation; fallback to reload
                        window.location.reload();
                    } else {
                        return res.text().then(t => { throw new Error(t || "Failed to post reply"); });
                    }
                })
                .catch(err => {
                    console.error("Reply error:", err);
                    alert("Failed to post reply.");
                });
            }, { once: true });
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const likeButtons = document.querySelectorAll(".image-like-btn");
    const dislikeButtons = document.querySelectorAll(".image-dislike-btn");

    function updateButtons(imageId, data) {
        const likeBtn = document.querySelector(`.image-like-btn[data-image-id="${imageId}"]`);
        const dislikeBtn = document.querySelector(`.image-dislike-btn[data-image-id="${imageId}"]`);
        if (likeBtn && dislikeBtn) {
            likeBtn.textContent = `Like (${data.likes})`;
            dislikeBtn.textContent = `Dislike (${data.dislikes})`;
            likeBtn.classList.toggle("voted", data.userAction === 'like');
            dislikeBtn.classList.toggle("voted", data.userAction === 'dislike');
        }
    }

    likeButtons.forEach(button => {
        button.addEventListener("click", function () {
            const imageId = this.dataset.imageId;
            const btn = this;
            fetch("/includes/like_image", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" },
                body: CSRF.params({ image_id: imageId, action: "like" }).toString()
            })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text);} catch(e){ return {success:false, raw:text}; }
            })
            .then(data => {
                if (data && data.success) updateButtons(imageId, data);
                else { console.warn("Like image failed:", data); alert("Failed to like image."); }
            })
            .catch(err => {
                console.error("Image like error:", err);
                alert("Network error while liking image.");
            });
        });
    });

    dislikeButtons.forEach(button => {
        button.addEventListener("click", function () {
            const imageId = this.dataset.imageId;
            const btn = this;
            fetch("/includes/like_image", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" },
                body: CSRF.params({ image_id: imageId, action: "dislike" }).toString()
            })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text);} catch(e){ return {success:false, raw:text}; }
            })
            .then(data => {
                if (data && data.success) updateButtons(imageId, data);
                else { console.warn("Dislike image failed:", data); alert("Failed to dislike image."); }
            })
            .catch(err => {
                console.error("Image dislike error:", err);
                alert("Network error while disliking image.");
            });
        });
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".menu > li").forEach(function(menuItem) {
        menuItem.addEventListener("mouseover", function() {
            const dropdown = this.querySelector(".dropdown");
            if (dropdown) dropdown.style.display = "block";
        });
        menuItem.addEventListener("mouseout", function() {
            const dropdown = this.querySelector(".dropdown");
            if (dropdown) dropdown.style.display = "none";
        });
    });
});
</script>
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

    // Handle both .toggle-blacklist and .toggle_blacklist class names
    document.querySelectorAll(".toggle-blacklist, .toggle_blacklist").forEach(button => {
        button.addEventListener("click", function (e) {
            // If it's a form submit button, let it submit normally unless it has data-tag
            if (this.type === "submit" && !this.dataset.tag) {
                return; // Let form submit normally
            }
            
            e.preventDefault();
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
            .then(async response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Invalid JSON response:", text);
                    throw new Error("Invalid response from server");
                }
            })
            .then(data => {
                if (data && data.status === "success") {
                    location.reload();
                } else {
                    console.error("Toggle blacklist failed:", data);
                    alert(data && data.message ? data.message : (data && data.error ? data.error : "Failed to toggle blacklist tag."));
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while toggling the blacklist tag. Please try again.");
            });
        });
    });
});
</script>
<script>
(function(){
    const STORAGE_KEY = 'viewImageSize';

    function setMode(mode) {
        const img = document.getElementById('view-image');
        const btn850 = document.getElementById('size-toggle-850');
        const btnFull = document.getElementById('size-toggle-full');
        if (!img || !btn850 || !btnFull) return;
        img.classList.remove('view-image-850','view-image-full');
        btn850.classList.remove('active');
        btnFull.classList.remove('active');
        if (mode === 'full') {
            img.classList.add('view-image-full');
            btnFull.classList.add('active');
            btnFull.setAttribute('aria-pressed','true');
            btn850.setAttribute('aria-pressed','false');
        } else {
            img.classList.add('view-image-850');
            btn850.classList.add('active');
            btn850.setAttribute('aria-pressed','true');
            btnFull.setAttribute('aria-pressed','false');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const btn850 = document.getElementById('size-toggle-850');
        const btnFull = document.getElementById('size-toggle-full');
        const img = document.getElementById('view-image');
        if (!img || !btn850 || !btnFull) return;

        const saved = (function(){ try { return localStorage.getItem(STORAGE_KEY); } catch(e){ return null; } })();
        setMode(saved === 'full' ? 'full' : '850');

        btn850.addEventListener('click', function(){ setMode('850'); });
        btnFull.addEventListener('click', function(){ setMode('full'); });

        // Optional: clicking image toggles mode
        img.addEventListener('click', function(){
            const current = img.classList.contains('view-image-full') ? 'full' : '850';
            setMode(current === 'full' ? '850' : 'full');
        });
    });
})();
</script>
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

    document.querySelectorAll(".reenable-blacklist").forEach(button => {
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

            fetch("/actions/reenable_blacklist", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded", "X-Requested-With": "XMLHttpRequest" },
                body: params.toString()
            })
            .then(async response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Invalid JSON response:", text);
                    throw new Error("Invalid response from server");
                }
            })
            .then(data => {
                if (data && data.status === "success") {
                    location.reload();
                } else {
                    console.error("Re-enable failed:", data);
                    alert(data && data.message ? data.message : "Failed to re-enable blacklist tag.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while re-enabling the blacklist tag. Please try again.");
            });
        });
    });
});
</script>

</body>
</html>