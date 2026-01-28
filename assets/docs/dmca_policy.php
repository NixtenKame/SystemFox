<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../includes/header.php');
$pageTitle = 'Copyright & DMCA Policy';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    
</head>
<body>
    
    <nav>
<?php include_once '../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../includes/site-notice.php'; ?>
    
    <main>
        <h1>Copyright & DMCA Policy</h1>
        <p><strong>Last Updated:</strong> February 13, 2025</p>

        <h2>1. Overview</h2>
        <p>FluffFox respects intellectual property rights and follows the Digital Millennium Copyright Act (DMCA). We process copyright infringement claims promptly.</p>

        <h2>2. Reporting Copyright Violations</h2>
        <p>To file a DMCA takedown request, provide the following:</p>
        <ul>
            <li>Your full legal name and contact information.</li>
            <li>A description of the copyrighted work.</li>
            <li>The URL(s) of the infringing content.</li>
            <li>A statement that you have a good faith belief the use is unauthorized.</li>
            <li>A statement, under penalty of perjury, that the information is accurate.</li>
            <li>Your physical or electronic signature.</li>
        </ul>
        <p>Send DMCA requests to <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a>.</p>

        <h2>3. Counter-Notification</h2>
        <p>If you believe content was removed in error, submit a counter-notice including:</p>
        <ul>
            <li>Your full legal name and contact details.</li>
            <li>Identification of the removed content and its location before removal.</li>
            <li>A statement under penalty of perjury that you believe the content was removed in error.</li>
            <li>Your consent to jurisdiction in U.S. federal court.</li>
            <li>Your physical or electronic signature.</li>
        </ul>

        <h2>4. Repeat Infringers</h2>
        <p>Accounts with repeated copyright violations may face permanent suspension.</p>

        <p>For additional questions, contact <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a>.</p>
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