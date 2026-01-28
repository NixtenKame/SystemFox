<?php
function validateFile($file, $allowedTypes, $maxSize) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading file: " . $file['error'];
    }
    if (!in_array($file['type'], $allowedTypes)) {
        return "Unsupported file type.";
    }
    if ($file['size'] > $maxSize) {
        return "File size exceeds limit.";
    }
    return true;
}
?>
