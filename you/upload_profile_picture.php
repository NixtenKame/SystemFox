<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    // respond with structured error when possible
    header('HTTP/1.1 401 Unauthorized', true, 401);
    echo "Unauthorized access.";
    exit();
}

// debug mode detection: ?debug=1 or client accepts JSON
$debug = (!empty($_GET['debug'])) || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

function respondError($message, $httpCode = 400, $extra = []) {
    global $debug;
    http_response_code($httpCode);
    if ($debug) {
        $payload = [
            'success' => false,
            'error' => (string)$message,
            'timestamp' => date('c'),
        ];
        if (!empty($extra) && is_array($extra)) $payload['details'] = $extra;
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_PRETTY_PRINT);
    } else {
        // non-debug path: echo plain message for existing frontend
        echo (string)$message;
    }
    exit();
}

function respondSuccess($message = 'success', $data = []) {
    global $debug;
    if ($debug) {
        $payload = ['success' => true, 'message' => (string)$message, 'data' => $data, 'timestamp' => date('c')];
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_PRETTY_PRINT);
    } else {
        echo (string)$message;
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Not a POST request', 405, ['method' => $_SERVER['REQUEST_METHOD'] ?? '']);
}

if (!isset($_FILES['profile_picture'])) {
    respondError('profile_picture not found in FILES', 400, ['available_files' => array_keys($_FILES)]);
}

if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['profile_picture']['error'];
    $codes = [
        UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE',
        UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE',
        UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL',
        UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE',
        UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
        UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
        UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION'
    ];
    respondError('File upload error', 400, ['code' => $code, 'name' => $codes[$code] ?? 'UNKNOWN']);
}
    $userId = $_SESSION['user_id'];
    // Update your upload directory path as needed
    $uploadDir = 'E:/yifffox/yiff-fox Root Project Directory/public/uploads/profile_pictures/' . $userId . '/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2 MB

    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            respondError('Failed to create upload directory', 500, ['path' => $uploadDir]);
        }
    }

    $file = $_FILES['profile_picture'];
    
    // Use finfo_file instead of deprecated mime_content_type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $fileSize = $file['size'];
    $fileName = 'profile_' . $userId . '.png'; // Always save as PNG

    // Validate file type and size
    if (!in_array($fileType, $allowedTypes)) {
        respondError('Invalid file type', 400, ['detected' => $fileType, 'allowed' => $allowedTypes]);
    }
    if ($fileSize > $maxFileSize) {
        respondError('File size exceeds maximum', 400, ['size' => $fileSize, 'max' => $maxFileSize]);
    }

    // Load the image
    $src = null;
    switch ($fileType) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $src = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            respondError('Unsupported image type', 400, ['file_type' => $fileType]);
    }
    
    if (!$src) {
        respondError('Failed to load image (imagecreatefrom* returned false).', 500, ['tmp_name' => $file['tmp_name']]);
    }

    // Center-crop to square (1:1) then resize down to max 1024 if necessary
    $width = imagesx($src);
    $height = imagesy($src);
    $min = min($width, $height);

    // Source crop coordinates (center)
    $src_x = (int) floor(($width - $min) / 2);
    $src_y = (int) floor(($height - $min) / 2);

    // Determine final size: if the square side is larger than 1024, scale down to 1024
    $final_size = ($min > 1024) ? 1024 : $min;

    $dst = imagecreatetruecolor($final_size, $final_size);
    if (!$dst) {
        imagedestroy($src);
        respondError('Failed to create destination image', 500, []);
    }

    // Preserve transparency for PNG and GIF
    if (in_array($fileType, ['image/png', 'image/gif'])) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $final_size, $final_size, $transparent);
    }

    // Copy and resample cropped square to destination (will scale if final_size != $min)
    imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $final_size, $final_size, $min, $min);

    // Save the cropped image as PNG
    $savePath = $uploadDir . $fileName;
    if (!imagepng($dst, $savePath)) {
        imagedestroy($src);
        imagedestroy($dst);
        respondError('Failed to save image to disk', 500, ['save_path' => $savePath]);
    }

    // Free memory
    imagedestroy($src);
    imagedestroy($dst);

    // Update the user's profile picture in the database
    $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    if (!$stmt) {
        respondError('Database prepare failed', 500, ['db_error' => $db->error]);
    }
    $filePath = 'profile_pictures/' . $userId . '/' . $fileName;
    $stmt->bind_param("si", $filePath, $userId);
    if ($stmt->execute()) {
        // success
        if ($debug) {
            respondSuccess('success', ['file' => $filePath, 'save_path' => $savePath]);
        } else {
            echo "success";
            $stmt->close();
            exit();
        }
    } else {
        respondError('Failed to update profile picture in the database', 500, ['stmt_error' => $stmt->error]);
    }
    $stmt->close();
?>