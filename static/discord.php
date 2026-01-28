<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Discord</title>
        <link rel="stylesheet" href="../public/css/styles.css">
        
        <link rel="json" href="https://discord.com/api/guilds/1329634027799314443/widget.json">
        <style>
            .discord-container {
                text-align: center;
                margin-top: 20px;
                margin-bottom: 20px;
                display: block;
            }

            .discord-button {
                display: inline-block;
                padding: 4px 12px;
                margin: 20px;
                border-radius: 8px;
                text-align: center;
                background-color: #5865F2;
                color: white;
                text-decoration: none;
                min-height: 32px;
            }
            .discord-button:hover {
                background-color: #4654C0;
            }
        </style>
    </head>

<body>
<nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <main>
        <div class="discord-container">
        <h2>Discord Links</h2>
            <iframe src="https://discord.com/widget?id=1329634027799314443&theme=dark" width="350" height="500" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
                <br>
            <a class="discord-button" href="https://discord.com/invite/yQ3bCRMcKB?utm_source=nixten.ddns.net">Discord</a>
        </div>
    </main>

    <?php include '../includes/version.php'; ?>
    <footer>
    <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>