<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/posts/upload_errors.log');
error_reporting(E_ALL);

// Auth check
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Validate ID
$imageIdRaw = $_GET['id'] ?? $_POST['id'] ?? null;
if (!$imageIdRaw || !is_numeric($imageIdRaw)) {
    die("<h2 style='color:red'>Invalid post ID.</h2>");
}

$imageId  = (int)$imageIdRaw;
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

// CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && function_exists('csrf_check') && !csrf_check()) {
    die("<h2 style='color:red'>CSRF validation failed.</h2>");
}

// Permissions
function canDeleteImage($image, $uid, $role) {
    return $image['uploaded_by'] == $uid || in_array($role, ['admin', 'moderator'], true);
}

// Delete physical file
function deleteImageFromStorage($fileName) {
    $filePath = "S:/FluffFox-Data/data/" . ltrim($fileName, '/');
    $filePath = str_replace('//', '/', $filePath);

    if (!file_exists($filePath)) {
        error_log("File not found: $filePath");
        return false;
    }

    if (unlink($filePath)) {
        error_log("Deleted file: $filePath");
        return true;
    }

    error_log("Failed to delete file: $filePath");
    return false;
}

// Fetch post
$stmt = $db->prepare("SELECT id, uploaded_by, file_name, is_deleted FROM uploads WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $imageId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2 style='color:red'>Post not found.</h2>");
}

$image = $result->fetch_assoc();
$stmt->close();

// Already deleted
if ((int)$image['is_deleted'] === 1) {
    echo "<h2 style='color:red;text-align:center'>Post already deleted.</h2>";
    exit;
}

// Permission check
if (!canDeleteImage($image, $userId, $userRole)) {
    die("<h2 style='color:red'>Permission denied.</h2>");
}

// File info
$filePathForDeletion = "S:/FluffFox-Data/data" . $image['file_name'];
$filePathForDeletion = str_replace('//', '/', $filePathForDeletion);
$fileExists = file_exists($filePathForDeletion);

/* ===========================
   CONFIRMATION PAGE (GET)
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Confirm Delete #<?= htmlspecialchars($imageId) ?></title>
<style>
 body {
        font-family: Arial, sans-serif;
        max-width: 600px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    .confirmation-box {
        background: white;
        border: 2px solid #d32f2f;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    h2 {
        color: #d32f2f;
        margin-top: 0;
    }
    .file-path {
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin: 20px 0;
        font-family: 'Courier New', monospace;
        word-break: break-all;
        font-size: 14px;
    }
    .file-status {
        padding: 10px;
        border-radius: 4px;
        margin: 10px 0;
    }
    .file-exists {
        background: #c8e6c9;
        color: #2e7d32;
        border: 1px solid #4caf50;
    }
    .file-not-found {
        background: #ffecb3;
        color: #f57c00;
        border: 1px solid #ffc107;
    }
    .button-group {
        margin-top: 30px;
        display: flex;
        gap: 10px;
    }
    button, a.button {
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
    }
    .btn-delete {
        background: #d32f2f;
        color: white;
    }
    .btn-delete:hover {
        background: #b71c1c;
    }
    .btn-cancel {
        background: #757575;
        color: white;
    }
    .btn-cancel:hover {
        background: #616161;
    }
    </style>
</head>
<body>
    <div class="confirmation-box">
<h2>Confirm Delete Post #<?= htmlspecialchars($imageId) ?></h2>

<p><strong>File:</strong></p>
<pre><?= htmlspecialchars($image['file_name']) ?></pre>

<p>Status: <?= $fileExists ? 'Exists' : 'Missing' ?></p>

<form method="POST" action="/posts/delete?id=<?= htmlspecialchars($imageId) ?>">
    <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
    <input type="hidden" name="id" value="<?= htmlspecialchars($imageId) ?>">
    <textarea name="deletion_reason" rows="4" cols="50"
        placeholder="Optional: reason for deletion"></textarea><br><br>
    <input type="hidden" name="confirm" value="1">
    <button type="submit">Delete Post</button>
</form>

<a href="/posts/<?= htmlspecialchars($imageId) ?>/">Cancel</a>
</div>
</body>
</html>
<?php
exit;
}

/* ===========================
   DELETE LOGIC (POST)
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['confirm']) || $_POST['confirm'] !== '1') {
        die("<h2 style='color:red'>Deletion not confirmed.</h2>");
    }

    // Delete physical file
    $fileDeleted = false;
    $fileDeleteError = null;

    if (!empty($image['file_name'])) {
        $fileDeleted = deleteImageFromStorage($image['file_name']);
        if (!$fileDeleted) {
            $fileDeleteError = "Physical file missing or failed to delete.";
        }
    }

    // Extract MD5 from filename
    $fileName = $image['file_name'] ?? '';
    $md5 = 'unknown';

    if ($fileName) {
        $base = basename($fileName);
        $md5Candidate = pathinfo($base, PATHINFO_FILENAME);

        if (preg_match('/^[a-f0-9]{32}$/i', $md5Candidate)) {
            $md5 = $md5Candidate;
        }
    }

    // Reason
    $deletionReason = trim($_POST['deletion_reason'] ?? '');
    $deletionReason = mb_substr($deletionReason, 0, 500);

    // Insert uploaders ID into deleted_posts table from posts table
    $stmt = $db->prepare("SELECT uploaded_by FROM uploads WHERE id = ?");
    $stmt->bind_param('i', $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $uploaderId = $row['uploaded_by'];
    } else {
        $uploaderId = 0;
    }
    $stmt->close();

    // Insert destroyed record
    $postData = json_encode($image);

    $stmt = $db->prepare("
        INSERT INTO destroyed_posts
            (post_id, destroyer_id, md5, destroyer_ip_addr, post_data, reason, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->bind_param(
        'iissss',
        $imageId,
        $userId,
        $md5,
        $_SERVER['REMOTE_ADDR'],
        $postData,
        $deletionReason
    );
    $stmt->execute();
    $stmt->close();

    // Update post count
    $db->query("UPDATE post_count SET total_posts = total_posts - 1 WHERE id = 1");

    // Soft delete
    $stmt = $db->prepare("UPDATE uploads SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param('i', $imageId);

    if ($stmt->execute()) {
        $stmt->close();
        if ($fileDeleteError) {
            $_SESSION['delete_warning'] = $fileDeleteError;
        }
        header("Location: /posts/");
        exit;
    }

    $stmt->close();
    die("<h2 style='color:red'>Failed to delete post.</h2>");
}