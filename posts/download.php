<?php
// Check if the URL is provided
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $externalFileUrl = $_GET['file']; // The external URL of the image

    // Make sure the URL is valid (basic validation)
    if (filter_var($externalFileUrl, FILTER_VALIDATE_URL)) {

        // Get the image data from the external URL
        $imageData = file_get_contents($externalFileUrl);

        // Extract the file name from the URL
        $fileName = basename($externalFileUrl);

        // Check if the file was successfully retrieved
        if ($imageData !== false) {
            // Set headers to force the browser to download the file
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . strlen($imageData));
            header('Pragma: no-cache');
            header('Expires: 0');

            // Output the image data to force download
            echo $imageData;
            exit;
        } else {
            echo 'Error fetching the image.';
        }
    } else {
        echo 'Invalid URL.';
    }
} else {
    echo 'No file URL provided.';
}
?>
