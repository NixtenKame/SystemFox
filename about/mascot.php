<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FluffFox Mascot</title>
        <link rel="stylesheet" href="/public/css/styles.css">
    </head>
    <body>
        <nav>
            <?php include_once '../includes/nav.php'; ?>
        </nav>
        <?php include_once '../includes/site-notice.php'; ?>
    </body>
</html>