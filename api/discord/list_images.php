<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$directory = 'S:/FluffFox-Data/data/';
$base_url = 'https://nixten.ddns.net:9001/data';

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/list_images_error.log');

/**
 * Recursively scan a directory and return all file paths relative to $directory
 */
function getFilesRecursive($dir, $baseDir) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $files = array_merge($files, getFilesRecursive($path, $baseDir));
        } else {
            // Make path relative to base directory and normalize slashes for URLs
            $relativePath = str_replace('\\', '/', substr($path, strlen($baseDir)));
            $files[] = $relativePath;
        }
    }
    return $files;
}

// Check directory exists
if (!is_dir($directory)) {
    error_log("Directory not found: $directory");
    die(json_encode(["error" => "Directory not found"]));
}

$allFiles = getFilesRecursive($directory, $directory);

if (empty($allFiles)) {
    error_log("No files found in directory: $directory");
    die(json_encode(["error" => "No files found"]));
}

// Convert to full URLs
$image_urls = array_map(function($file) use ($base_url) {
    return $base_url . $file;
}, $allFiles);

header('Content-Type: application/json');
echo json_encode($image_urls);
?>
