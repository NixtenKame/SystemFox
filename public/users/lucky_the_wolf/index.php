<?php

define('ROOT_PATH', realpath(__DIR__ . '/../../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../../includes/header.php'); // Include common header
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lucky - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
</head>
<body onload="updateClock()">

    <nav>
<?php include_once '../../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../../includes/site-notice.php'; ?>
<main>
    <style>
        .profile-pic-container {
            position: relative;
            display: inline-block;
            width: 200px;
            height: 200px;
        }
        .profile-pic-container img {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            display: block;
        }
        .profile-icons {
            position: absolute;
            top: 0px;
            right: 0px;
            display: flex;
            gap: 8px;
            z-index: 2;
        }
        .profile-icons i {
            font-size: 2.2em;
            text-shadow: 1px 1px 4px #000;
        }
        .profile-icons .fa-code { color: #1db954; }
        .profile-icons .fa-1 { color: #ff6f61; }

        @media (max-width: 600px) {
            .profile-pic-container {
                width: 120px;
                height: 120px;
            }
            .profile-icons i {
                font-size: 1.3em;
                gap: 4px;
            }
        }
    </style>
    <div class="profile-pic-container">
        <div class="profile-icons">
            <i class="fa-solid fa-code"></i>
            <i class="fa-solid fa-1"></i>
        </div>
    </div>
    <h1>About me</h1>
    <p>just some furry who likes Warrior Cats, Rain World, and other stuff</p>
    <br>
    <br>
    <h2>This user is a developer of the site and first user so please give respect, Thank You.</h2>
    <br>
    <br>
    <h3>User is a friend of <a href="/public/users/Nixten Leo Kame/Nixten_Leo_Kame.htm">Nixten</a>.</h3>
    <br>
    <br>
    </form>
    </main>
    <?php include('../../../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>