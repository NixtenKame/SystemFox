<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../../logs/posts/upload_errors.log');
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php"); // Redirect to login if not logged in
    exit;
}

$imageIdRaw = $_GET['id'] ?? $_POST['id'] ?? null;
if (empty($imageIdRaw) || !is_numeric($imageIdRaw)) {
    die("<h2 style='color:red'>Invalid post ID.</h2>");
}

$imageId = intval($imageIdRaw);
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

// CSRF check only for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && function_exists('csrf_check') && !csrf_check()) {
    die("<h2 style='color:red'>CSRF validation failed. Cannot delete post.</h2>");
}

// Permission check function
function canDeleteImage($image, $currentUserId, $currentUserRole) {
    return $image['uploaded_by'] == $currentUserId || in_array($currentUserRole, ['admin', 'moderator']);
}

/**
 * Delete physical file from storage
 * @param string $fileName - File name from database (e.g., "/69/1e/691e748778d11797630153c.png")
 * @return bool - True if deleted successfully, false otherwise
 */
function deleteImageFromStorage($fileName) {
    // Database stores file_name like "/69/1e/691e748778d11797630153c.png"
    // Construct full path: "S:/FluffFox-Data/data" + "/69/1e/691e748778d11797630153c.png" = "S:/FluffFox-Data/data/69/1e/691e748778d11797630153c.png"
    $filePath = "S:/FluffFox-Data/data/" . $fileName;
    
    // Ensure the path is properly formatted (handle any double slashes)
    $filePath = str_replace('//', '/', $filePath);
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            error_log("Successfully deleted file: $filePath");
            return true;
        } else {
            error_log("Failed to delete file: $filePath (unlink failed)");
            return false;
        }
    } else {
        error_log("File does not exist: $filePath");
        return false;
    }
}

// Fetch image from DB (including is_deleted)
$stmt = $db->prepare("SELECT id, uploaded_by, file_name, is_deleted FROM uploads WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $imageId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2 style='color:red'>Post not found.</h2>");
}

$image = $result->fetch_assoc();

// Check if image is already deleted
if (!empty($image['is_deleted']) && $image['is_deleted'] == 1) {
    echo "<div style='border:1px solid red;padding:20px;margin:50px auto;width:400px;text-align:center;font-family:sans-serif;'>
            <h2 style='color:red'>Image Already Deleted</h2>
            <p>This post has already been deleted and no actions can be performed on it.</p>
            <a href='/posts/' style='text-decoration:none;color:blue;'>Back to posts</a>
          </div>";
    exit;
}

$stmt->close();


// Only allow deletion if the user is the uploader or admin/mod
if (!canDeleteImage($image, $userId, $userRole)) {
    echo "<div style='border:1px solid red;padding:20px;margin:50px auto;width:400px;text-align:center;font-family:sans-serif;'>
            <h2 style='color:red'>Permission Denied</h2>
            <p>You are not the original uploader or an Admin/Moderator.</p>
            <a href='/posts/' style='text-decoration:none;color:blue;'>Back to posts</a>
          </div>";
    exit;
}

// Calculate the file path that would be deleted (for confirmation display)
$filePathForDeletion = "S:/FluffFox-Data/data" . $image['file_name'];
$filePathForDeletion = str_replace('//', '/', $filePathForDeletion);
$fileExists = file_exists($filePathForDeletion);

// If GET request, show confirmation page with file path
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Delete - Post #<?php echo htmlspecialchars($imageId); ?></title>
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
            <h2>⚠️ Confirm Delete Post #<?php echo htmlspecialchars($imageId); ?></h2>
            <p><strong>Are you sure you want to delete this post?</strong></p>
            
            <h3>File Information (Debug):</h3>
            <p><strong>Database file_name:</strong></p>
            <div class="file-path"><?php echo htmlspecialchars($image['file_name']); ?></div>
            
            <p><strong>Full file path that will be deleted:</strong></p>
            <div class="file-path"><?php echo htmlspecialchars($filePathForDeletion); ?></div>
            
            <div class="file-status <?php echo $fileExists ? 'file-exists' : 'file-not-found'; ?>">
                <strong>File Status:</strong> <?php echo $fileExists ? '✓ File exists on disk' : '⚠ File not found on disk (may have been deleted already)'; ?>
            </div>
            
            <p style="color: #d32f2f; font-size: 14px; margin-top: 20px; font-weight: bold;">
                <em>⚠️ Warning: This will permanently delete the post from the database AND the physical file from disk.<br>
                This action cannot be undone!</em>
            </p>
            
            <div class="button-group">
                <form method="POST" action="/posts/delete?id=<?php echo htmlspecialchars($imageId); ?>" style="display:inline;">
                    <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($imageId); ?>">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="btn-delete">Yes, Delete Post</button>
                </form>
                <a href="/posts/<?php echo htmlspecialchars($imageId); ?>/" class="button btn-cancel">Cancel</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If POST request with confirmation, proceed with deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for confirmation
    if (!isset($_POST['confirm']) || $_POST['confirm'] !== '1') {
        die("<h2 style='color:red'>Deletion not confirmed. Please use the confirmation page.</h2>");
    }
    
    // First, try to delete the physical file
    $fileDeleted = false;
    $fileDeleteError = null;
    if (!empty($image['file_name'])) {
        $fileDeleted = deleteImageFromStorage($image['file_name']);
        if (!$fileDeleted) {
            $fileDeleteError = "Warning: Physical file deletion failed or file not found. Database record will still be marked as deleted.";
            error_log("Post #$imageId: $fileDeleteError - Path: $filePathForDeletion");
        }
}

// Update post count: post_count = -1
$updateStmt2 = $db->prepare("UPDATE post_count SET total_posts = total_posts -1 WHERE id = 1");
$updateStmt2->execute();
$updateStmt2->close();

// Soft-delete: mark is_deleted = 1
$updateStmt = $db->prepare("UPDATE uploads SET is_deleted = 1 WHERE id = ?");
$updateStmt->bind_param('i', $imageId);

if ($updateStmt->execute()) {
    $updateStmt->close();
        
        // Log the deletion with file path for debugging
        $logMessage = "Post #$imageId deleted. File path: $filePathForDeletion (File deleted: " . ($fileDeleted ? 'Yes' : 'No') . ")";
        error_log($logMessage);
        
        // If there was a file deletion error, show it but still redirect
        if ($fileDeleteError) {
            $_SESSION['delete_warning'] = $fileDeleteError;
        }
        
    header("Location: /posts/"); // Redirect back to posts page
    exit;
} else {
    $updateStmt->close();
    die("<h2 style='color:red'>Failed to delete post. Please try again later.</h2>");
    }
}
?>
