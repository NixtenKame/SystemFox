<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include('../includes/version.php'); 
include_once('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Map - FluffFox</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>

        .menu-title {
            font-size: 1.5em;
            margin-top: 20px;
            color: #333;
        }

        .menu-links {
            margin-bottom: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            color: #333;
            width: 100%;
        }

        .menu-links a {
            display: flex;
            margin: 10px 0;
            color: #007bff;
            text-decoration: none;
            background-color: #eee;
            width: 100%;
            height: 50px;
            font-size: 1.2em;
            justify-content: center;
            align-items: center;
            border-radius: 50px;
        }

        .menu-links a:hover {
            background-color: #555;
        }

        body.dark .menu-links {
            background-color: #333;
            color: #ddd;
        }

        body.dark .menu-links a {
            background-color: #555;
            color: #ddd;
        }

        body.dark .menu-links a:hover {
            background-color: #777;
        }

    </style>
</head>
<body>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <main>
        <h2>Links</h2>
        <div class="menu-links a">
            <a href="/"><i class="fa-solid fa-home"></i> Home</a>
        </div>
        <div class="menu-links">
            <h1><i class="fa-solid fa-user"></i>Account</h1>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/you/edit"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                <a href="/actions/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                <a href="/you/favorites"><i class="fa-solid fa-star"></i> Favorites</a>
                <a href="/auth/reset_password"><i class="fa-solid fa-key"></i> Reset Password</a>
                <a href="/you/settings"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="/settings/blacklist"><i class="fa-solid fa-ban"></i> Blacklist</a>
                <a href="/help/"><i class="fa-solid fa-circle-question"></i> Help</a>
                <a href="/search_profile"><i class="fa-solid fa-magnifying-glass"></i> Search Profile</a>
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'moderator')): ?>
                    <a href="/moderation/moderation"><i class="fa-solid fa-shield-halved"></i> Moderation Panel</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="/login"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                <a href="/register"><i class="fa-solid fa-user-plus"></i> Signup</a>
            <?php endif; ?>
        </div>

        <div class="menu-links">
            <h1><i class="fa-solid fa-comments"></i>Chat</h1>
            <a href="/c/"><i class="fa-solid fa-user-group"></i> Private Chat</a>
            <a href="/public_chat/chat"><i class="fa-solid fa-users"></i> Public Group Chat</a>
        </div>

        <div class="menu-links">
            <h1><i class="fa-solid fa-info-circle"></i>Information</h1>
            <a href="/assets/docs/Terms of Use"><i class="fa-solid fa-file-contract"></i> Terms of Use</a>
            <a href="/assets/docs/Privacy Policy"><i class="fa-solid fa-user-shield"></i> Privacy Policy</a>
            <a href="/assets/docs/Code Of Conduct"><i class="fa-solid fa-scale-balanced"></i> Code of Conduct</a>
            <a href="/assets/docs/content_moderation"><i class="fa-solid fa-gavel"></i> Content Moderation</a>
            <a href="/assets/docs/dmca_policy"><i class="fa-solid fa-copyright"></i> DMCA Policy</a>
            <a href="/assets/docs/version"><i class="fa-solid fa-code-branch"></i> Site Version</a>
            <a href="/assets/docs/news"><i class="fa-solid fa-newspaper"></i> Site News</a>
            <a href="/forums/forum"><i class="fa-solid fa-comments"></i> Forums</a>
            <a href="/assets/docs/FluffFox"><i class="fa-solid fa-paw"></i>About FluffFox</a>
            <a href="/static/site_map"><i class="fa-solid fa-sitemap"></i> Site Map</a>
            <a href="/static/discord"><i class="fa-brands fa-discord"></i> Join Our Discord Server</a>
        </div>

        <div class="menu-links" style="margin-bottom: 140px;">
            <h1><i class="fa-solid fa-users"></i>About Us</h1>
            <a href="/assets/docs/About Server Owner"><i class="fa-solid fa-user-tie"></i> About Server Owner</a>
            <a href="/public/users/Nixten%20Leo%20Kame/Nixten_Leo_Kame"><i class="fa-solid fa-crown"></i> Owner's Profile</a>
            <a href="/public/users/Lucky%20The%20Wolf/Lucky"><i class="fa-solid fa-code"></i> First Developer/Beta Tester</a>
        </div>
    </main>
    <br>
    <br>
    <br>
    <br>
    <br>

    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>