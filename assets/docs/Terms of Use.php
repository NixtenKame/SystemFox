<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../includes/header.php'); // Include common header
// Set a page title dynamically
$pageTitle = 'Terms of Use';

// Add Content Security Policy (CSP) header
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
        <h1>Terms of Use</h1>
        <h2>By Accessing or Using This Site, You Agree to These Terms</h2>
        <p><strong>Effective Date:</strong> 5/14/25 @ 9:11 PM Chicago Time</p>

        <h2>1. Eligibility & Age Restrictions</h2>
        <p>Users must be at least <strong>13 years old</strong> to register. If under <strong>18 years old</strong>, parental consent is required.</p>

        <h2>2. User Responsibilities</h2>
        <ul>
            <li>Provide accurate information when registering.</li>
            <li>Do not share login credentials with anyone.</li>
            <li>Keep your account secure; we are not responsible for compromised accounts.</li>
            <li>Absolutly no HACKING or Exploiting the site.</li>
            <li>Art is only allowed, no AI generated content, or real life photos or selfies, these images can only be uploaded via Private Messages and nsfw images sent in PM is strictly prohibited.</li>
        </ul>

        <h2>3. Acceptable Use Policy</h2>
        <p>Users must adhere to the following:</p>
        <ul>
            <li>Be respectful—no harassment, hate speech, or bullying.</li>
            <li>No illegal or offensive content, including threats, racism, or derogatory language.</li>
            <li>No spamming, hacking, or attempting to exploit the site.</li>
            <li>No impersonation of other users or administrators.</li>
        </ul>

        <h2>4. Content Ownership & Licensing</h2>
        <p>Users retain ownership of uploaded content but grant FluffFox a license to display and store it. You must have permission to upload copyrighted material.</p>

        <h2>5. Moderation & Rule Enforcement</h2>
        <ul>
            <li>FluffFox moderators can remove content that violates these rules.</li>
            <li>Users can report inappropriate content for review.</li>
            <li>Repeated violations may result in account suspension or permanent banning.</li>
        </ul>

        <h2>6. Account Termination</h2>
        <p>You may delete your account at any time. We reserve the right to suspend or ban accounts violating these terms.</p>

        <h2>7. Limitation of Liability</h2>
        <p><strong>FluffFox</strong> and its owners are not liable for:</p>
        <ul>
            <li>Any loss of data or service interruptions.</li>
            <li>Unauthorized access to accounts due to user negligence.</li>
            <li>Third-party services linked or used within this site.</li>
        </ul>

        <h2>8. Privacy & Data Protection</h2>
        <p>We collect minimal user data and do not sell it to third parties. Refer to our <a href="/assets/docs/Privacy Policy">Privacy Policy</a> for full details.</p>

        <h2>9. Changes to the Terms of Use</h2>
        <p>We reserve the right to update these Terms of Use at any time. Significant changes will be announced on this page.</p>

        <h2>10. Agreement</h2>
        <p>By using this site, you acknowledge that you have read, understood, and agreed to these Terms of Use.</p>

        <h2>Contact Us</h2>
        <ul>
            <li>Email: <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a></li>
            <li>Phone: (618)-578-3926</li>
        </ul>
        <br>
        <button class="button" onclick="window.history.back()">Continue</button>
        <br>
        <br>
        <br>
        <br>
    </main>
    <?php include('../../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
    
</body>
</html>