<?php

define('ROOT_PATH', realpath(__DIR__ . '/../../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Old Icon Information</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="/favicon.ico">
    <script src="https://nixten.ddns.net:3001/js/v<?php echo $version; ?>/scripts.js"></script>
</head>
<body>
    <nav>
        <?php include_once '../../../includes/nav.php'; ?>
    </nav>
        <?php include_once '../../../includes/site_notice.php'; ?>
    <div class="past-body">
        <div class="past">
            <h1>Old Icon Information</h1>
            <p>The old icon was a icon that was used in the early stages of development for the website and wasnt removed till later versions of the public releases until it made a comeback on version 1.2.7 as a icon in the selection menu on the settings page for anyone.</p>
            <h2>Icon Description:</h2>
            <p>The icon contains a blue paw with pink gradient starting from the bottom and going a orangeish yellowish color at the top.</p>
            <p>The blue paw to make it look like the creator's fursona (Nixten L Kame) was the one who put his paw in the icon until the creator later on decided to make it pink with a progress pride flag.</p>
            <h2>Icon Creator:</h2>
            <p>The icon was created by Nixten L Kame (the creator of FluffFox) himself. Which took only 10 minutes to make and complete using special softwares.</p>
            <h2>The icon:</h2>
            <img src="assets/img/old.png" alt="Old Icon" class="icon-image">
        </div>
    </div>
    <div class="other-info-body">
        <div class="other-info">
            <h1>Other Information</h1>
            <h2>Why did the icon make a comeback?</h2>
            <p>The icon made a comeback by one of my friends requesting that the icon looked good and that they liked it, and i admit i liked it too.</p>
            <p>To read the request look at it below lol :3</p>
        </div>
    </div>
</body>
</html>