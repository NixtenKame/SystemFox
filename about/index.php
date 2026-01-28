<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');
$unreadCount = 0;
$notifications = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
        $unreadQuery = "SELECT COUNT(*) AS unread_count 
                    FROM notifications 
                    WHERE user_id = ? AND status = 0 AND dismissed = 0";
    $stmt = $db->prepare($unreadQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount = ($result) ? $result->fetch_assoc()['unread_count'] : 0;
    $stmt->close();
        $notifQuery = "SELECT id, message, created_at 
                   FROM notifications 
                   WHERE user_id = ? AND dismissed = 0 
                   ORDER BY created_at DESC 
                   LIMIT 5";
    $stmt = $db->prepare($notifQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}
?>
<DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>What is FluffFox?</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <style>
        .ffx-doc-container {
            max-width: 650px;
            margin: 60px auto 0 auto;
            background: rgba(255,255,255,0.18);
            border-radius: 18px;
            box-shadow: 0 4px 24px #0002;
            padding: 36px 22px 28px 22px;
            text-align: center;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .ffx-doc-container h1 {
            color: #4f8cff;
            font-size: 2.2em;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }
        .ffx-doc-container h2 {
            color: #ff6f61;
            margin-top: 28px;
            font-size: 1.3em;
        }
        .ffx-doc-container p {
            font-size: 1.08em;
            line-height: 1.7;
            color: #222;
            margin-bottom: 16px;
        }
        .ffx-doc-author {
            margin-top: 32px;
            color: #888;
            font-size: 1em;
        }
        @media (max-width: 700px) {
            .ffx-doc-container { padding: 16px 2vw; }
            .ffx-doc-container h1 { font-size: 1.3em; }
        }
        body.dark .ffx-doc-container {
            background: rgba(30, 34, 44, 0.45); /* more transparent */
            box-shadow: 0 4px 24px #0008;
            backdrop-filter: blur(16px);
        }
        body.dark .ffx-doc-container h1 {
            color: #7dbbff;
        }
        body.dark .ffx-doc-container h2 {
            color: #ffb6b6;
        }
        body.dark .ffx-doc-container p {
            color: #e0e0e0;
        }
        body.dark .ffx-doc-author {
            color: #aaa;
        }       
        @media (max-width: 700px) {
            body.dark .ffx-doc-container { padding: 16px 2vw; }
            body.dark .ffx-doc-container h1 { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
        <main>
        <div class="ffx-doc-container">
            <h1>What is FluffFox?</h1>
            <p>
                <strong>FluffFox</strong> is a friendly online community and art-sharing platform created and run by <b>Nixten Leo Kame</b>. FluffFox is not a company, LLC, or business—it's simply a passion project and a safe space for furries, artists, and friends to connect, share, and have fun together.
            </p>
            <h2>Our Purpose</h2>
            <p>
                FluffFox exists to bring together people who love art, creativity, and the furry fandom. Here, you can share your artwork, meet new friends, and be part of a positive, welcoming environment. There are no corporate interests—just a community built by and for its members.
            </p>
            <h2>Who Runs FluffFox?</h2>
            <p>
                FluffFox is built, maintained, and moderated by Nixten Leo Kame. This project was started to give myself and others a place to express themselves freely, without the pressure or rules of big social media platforms.
            </p>
            <h2>How Can I Join?</h2>
            <p>
                Anyone who shares our values of kindness, creativity, and respect is welcome! You don’t need to be a professional artist or a long-time furry—just bring your positive vibes and be yourself.
            </p>
            <div class="ffx-doc-author">
                &mdash; Nixten Leo Kame<br>
                <span style="font-size:0.95em;">Founder &amp; Community Fox</span>
            </div>
        </div>
    </main>
    <br>
    <br>
    <br>
    <br>
</body>
    <?php include('../includes/version.php'); ?>
    <footer>
        <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
    </html>