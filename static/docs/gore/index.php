<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../../..'));
include_once ROOT_PATH . '/connections/config.php';
include_once('../../../includes/header.php');
$pageTitle = 'Candygore Policy';
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
        <?php include_once '../../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../../includes/site-notice.php'; ?>
    <main>
        <section class="policy">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

            <h2>Candygore Policy</h2>
            <p>This site is intended to remain <strong>SFW (Safe For Work)</strong>. 
            Explicit sexual content, realistic gore, or content intended to shock or disturb is not permitted.</p>

            <p>We understand that some artists enjoy creating <strong>“candygore”</strong> — a stylized and colorful form of art that may include 
            minor cuts, cartoonish wounds, or colorful blood/effects. Candygore is <u>permitted</u> under the following conditions:</p>

            <ul>
                <li><strong>Stylized Only</strong> – Candygore must be clearly fictional, cartoony, or colorful. Realistic depictions of injury, organs, or heavy blood are not allowed.</li>
                <li><strong>Minor Themes</strong> – Small cuts, scratches, or limited candy-colored blood effects are acceptable. Extreme gore, dismemberment, or realistic body horror is not.</li>
                <li><strong>Proper Tagging</strong> – All candygore artwork must be tagged with “candygore” so that users who do not want to see it can filter it out.</li>
                <li><strong>Respect Viewer Comfort</strong> – Candygore submissions may be blurred or marked with a content warning preview. Users must choose to view it.</li>
                <li><strong>Moderator Discretion</strong> – Moderators reserve the right to remove any content that appears too graphic, disturbing, or otherwise inappropriate for the community.</li>
            </ul>

            <p>By posting candygore, you agree that your artwork follows these rules and that the responsibility for tagging and presentation lies with you as the artist.</p>

            <h2>Actual Gore & Realistic Gore Policy</h2>
            <p><strong>Actual gore and realistic gore artwork are not permitted on this site under any circumstances.</strong></p>
            <p>This includes, but is not limited to:</p>
            <ul>
                <li>Realistic blood or wounds drawn in detail (red blood, flesh, or injuries).</li>
                <li>Dismemberment, entrails, or organs shown in a realistic manner.</li>
                <li>Content intended to shock, disturb, or mimic real-life violence.</li>
            </ul>
            <p>Any artwork that appears to cross into realistic gore will be removed immediately, regardless of intent or style. 
            Repeated violations may result in account suspension or banning.</p>
        </section>
    </main>
    <br><br><br><br><br>
    <?php include_once '../../../includes/version.php'; ?>
    <footer>
        <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. 
        <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>