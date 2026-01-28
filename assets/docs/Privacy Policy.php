<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    
</head>
<body>
    <nav>
<?php include_once '../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../includes/site-notice.php'; ?>
    <main>
        <h1>Privacy Policy</h1>
        <p><strong>Effective Date:</strong> November 26, 2025</p>

        <h2>1. Information We Collect</h2>
        <p>At <strong>NixtensServer</strong>, we collect and store the following data when you interact with our website:</p>
        <ul>
            <li><strong>IP Address & Location:</strong> Collected for security, optimization, and maintenance purposes (This information is stored in log files and are also stored in the database).</li>
            <li><strong>File Upload Data:</strong> Includes file name, type, size, and MIME type to process uploads.</li>
            <li><strong>Device & Browser Information:</strong> Logged to improve user experience and troubleshoot issues.</li>
            <li><strong>Upload ID:</strong> A unique identifier assigned to each upload for tracking and logging.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use your data for the following purposes:</p>
        <ul>
            <li><strong>Security & Fraud Prevention:</strong> To monitor and prevent malicious activity.</li>
            <li><strong>Website Improvement:</strong> To enhance website functionality and performance.</li>
            <li><strong>Service Notifications:</strong> To inform you about uploads, server status, or updates.</li>
        </ul>

        <h2>3. Data Protection & Security</h2>
        <p>We take data protection seriously and ensure your privacy:</p>
        <ul>
            <li><strong>Confidentiality:</strong> User data is not shared or sold.</li>
            <li><strong>Secure Passwords:</strong> We never store passwords in plaintext. Passwords are encrypted and hashed.</li>
            <li><strong>Storage & Retention:</strong> Uploaded files are stored securely and deleted upon user request unless required by law.</li>
        </ul>

        <h2>4. Data Sharing</h2>
        <p>We do not sell or share personal data, except:</p>
        <ul>
            <li>When required by law (e.g., government requests).</li>
            <li>With trusted service providers assisting in website operation.</li>
        </ul>

        <h2>5. Logging & Tracking</h2>
        <p>We log limited data for operational and security reasons:</p>
        <ul>
            <li><strong>IP Address & Location:</strong> Used for security and service optimization.</li>
            <li><strong>File Information:</strong> Logged for upload tracking.</li>
            <li><strong>Device & Browser Info:</strong> Used for debugging and improvement.</li>
            <li><strong>Browser screen tracking:</strong> We use PostHog to collect anonymous browser screen tracking data to help us improve the website experience by seeing what went wrong in the recorded console we will not distribute or sell these recordings at any cost. This data does not include personal identifiers, or other sensitive information unless exposed in the recording in which again this data is not publicly available so dont worry.</li>
        </ul>

        <h2>6. User Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li>Access and correct your personal data.</li>
            <li>Request data deletion (except where legally required).</li>
            <li>Opt-out of certain data collection methods.</li>
        </ul>
        <p>To exercise these rights, contact us at <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a>.</p>

        <h2>7. Security Measures</h2>
        <p>We use encryption, secure authentication, and access controls to protect your data. However, no system can guarantee 100% security.</p>

        <h2>8. Compliance & Legal Information</h2>
        <p>This Privacy Policy complies with relevant U.S. and Illinois privacy laws. If you are under 13, you may not use this site in compliance with <strong>COPPA (Children’s Online Privacy Protection Act)</strong>.</p>

        <h2>9. Prohibited Activities</h2>
        <p>To protect all users and maintain a safe environment, the following activities are prohibited:</p>
        <ul>
            <li>Uploading or sharing illegal, harmful, or offensive content.</li>
            <li>Exploiting, hacking, or interfering with the site.</li>
            <li>Using automated bots, scrapers, or scripts to interact with the platform.</li>
            <li>Sharing login credentials or impersonating others.</li>
        </ul>

        <h2>10. Termination of Access</h2>
        <p>We reserve the right to suspend or terminate access to our services for users who violate this Privacy Policy, the <a href="/assets/docs/Terms of Use">Terms of Use</a>, or applicable laws.</p>

        <h2>11. Limitation of Liability</h2>
        <ul>
            <li>We are not responsible for service interruptions or data loss.</li>
            <li>Users are responsible for safeguarding their accounts.</li>
        </ul>

        <h2>12. Changes to This Policy</h2>
        <p>We may update this policy. Continued use of the site after updates means you accept the changes.</p>

        <h2>Agreement</h2>
        <p>By using this site, you acknowledge that you have read, understood, and agreed to this Privacy Policy.</p>

        <h2>Contact Us</h2>
        <ul>
            <li>Email: <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a></li>
        </ul>

        <p><button class="button" onclick="alert('You have accepted the Privacy Policy.')">I Accept</button></p>
            <br>
    <br>
    <br>
    <br>
    <br>
    </main>

    <?php include('../../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
    
</body>
</html>