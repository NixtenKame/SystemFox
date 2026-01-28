<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../includes/header.php');
$pageTitle = 'Site Content';
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
        <p><strong>Last Updated:</strong> March 14, 2025</p>
        <h1><span class="NOTICE">Allowed Content</span></h1>
        <p>Only SFW (Safe for Work) art is allowed on this site. Uploading NSFW (Not Safe for Work) art or any content that violates the rules stated on this page is strictly prohibited.</p>

        <h1><span class="NOTICE">Prohibited Content</span></h1>
        <p>The following types of content are not allowed on the site:</p>
        <ul>
            <li>NSFW artwork</li>
            <li>Gore or excessively violent content</li>
            <li>Any other material that violates the site's rules</li>
            <li>AI generated content is strictly prohibited</li>
        </ul>

        <h1><span class="NOTICE">Consequences of Rule Violations</span></h1>
        <p>If you break the rules, your account will be warnned and 3 strikes your out, and your IP address may be blocked from accessing the site.</p>

        <h1><span class="NOTICE">Contact the Site Owner</span></h1>
        <p>If you have any questions or concerns, please contact the site owner at <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a>. The site owner will respond within 24 hours.</p>

        <h1><span class="NOTICE">Why This Site Is SFW Only</span></h1>
        <p>This site is currently SFW-only because the site owner is a minor (15 years old). To comply with legal requirements and avoid potential issues, NSFW content is not permitted at this time. When the owner reaches the appropriate age, the site may allow both SFW and NSFW content.</p>

        <p>Thank you for reading the rules, and have a great day!</p>
    </main>
    <br>
    <br>
    <br>
    <br>
    <br>
    
    <?php include('../../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
