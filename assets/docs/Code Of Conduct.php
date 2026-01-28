<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../includes/header.php');
$pageTitle = 'Code of Conduct';
date_default_timezone_set('America/Chicago');
$currentTime = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    
    <script>
        function updateClock() {
            let serverTime = new Date("<?php echo $currentTime; ?>");
            function tick() {
                serverTime.setSeconds(serverTime.getSeconds() + 1);
                let options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric', 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit', 
                    hour12: true, 
                    timeZone: 'America/Chicago' 
                };
                document.getElementById("serverTime").innerHTML = serverTime.toLocaleString("en-US", options);
            }
            setInterval(tick, 1000);
        }
    </script>
</head>
<body onload="updateClock()">
    
    <nav>
<?php include_once '../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../includes/site-notice.php'; ?>
    
    <main>
        <h1>FluffFox Community Guidelines</h1>
        <p><strong>Last Updated:</strong> February 13, 2025 @ 9:12 PM CST</p>

        <h2>1. Respect and Inclusivity</h2>
        <p>FluffFox is a community for furry artists and enthusiasts. All members must treat each other with kindness and respect. The following behaviors are strictly prohibited:</p>
        <ul>
            <li>Harassment, bullying, or hate speech.</li>
            <li>Discrimination based on race, gender, sexuality, or other personal characteristics.</li>
            <li>Personal attacks or targeted harassment of any kind.</li>
        </ul>

        <h2>2. Content Restrictions</h2>
        <p>All uploaded content must adhere to the following guidelines:</p>
        <ul>
            <li><strong>Allowed:</strong> SFW furry artwork, illustrations, digital art, and photography.</li>
            <li><strong>Prohibited:</strong> NSFW content (sexual content, nudity, excessive gore), copyrighted material without permission, stolen artwork, and AI-generated images that violate copyright laws.</li>
        </ul>

        <h2>3. Community Behavior</h2>
        <p>To maintain a safe environment, users must:</p>
        <ul>
            <li>Refrain from spamming, trolling, or disruptive behavior.</li>
            <li>Avoid excessive self-promotion or unauthorized advertising.</li>
            <li>Respect the decisions of moderators and administrators.</li>
        </ul>

        <h2>4. Privacy and Safety</h2>
        <p>To protect yourself and others, please follow these safety measures:</p>
        <ul>
            <li>Do not share personal information publicly.</li>
            <li>Do not attempt to hack, dox, or compromise other users.</li>
            <li>If you feel unsafe or experience harassment, report it immediately.</li>
        </ul>
        <p>More details are available in our <a href="/assets/docs/Privacy_Policy">Privacy Policy</a>.</p>

        <h2>5. Reporting Violations</h2>
        <p>If you witness a violation of these guidelines, please report it using the following methods:</p>
        <ul>
            <li>Email: <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a></li>
            <li>Contact Form: <a href="/report">Report an Issue</a></li>
        </ul>

        <h2>6. Rule Violations and Consequences</h2>
        <p>Users who break the rules may face the following penalties:</p>
        <ol>
            <li><strong>Warning:</strong> A verbal warning from moderators.</li>
            <li><strong>Temporary Suspension:</strong> A short-term ban from posting or accessing features.</li>
            <li><strong>Permanent Ban:</strong> A complete ban from the site, including an IP block.</li>
        </ol>
        <p>Users may appeal bans by emailing the site owner at <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a>.</p>

        <h2>7. Updates to This Code</h2>
        <p>FluffFox reserves the right to update this Code of Conduct at any time. Significant changes will be announced via site notifications.</p>
        <p>By using FluffFox, you agree to follow these guidelines and ensure a positive community experience for all members.</p>
    </main>

    <br>
    <br>
    <br>
    <br>
    <?php include('../../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
