<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Internal Server Error - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
</head>
<body>
    <h1 style="color: red; font-size: large;">500 Internal Server Error</h1>
    <p>Sorry, something went wrong on our end. Please try again later.</p>
</body>
</html>