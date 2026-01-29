<?php
/**
 * Underwear Content Documentation
 * 
 * Description: This document provides guidelines and best practices for underwear content on the platform.
 * This includes acceptable themes, styles, and community standards to ensure a positive experience for all users.
 * 
 * This files breaks down the types of underwear content allowed, content restrictions, and tips for creators.
 * As we allow borderline NSFW content but still SFW, it's important to adhere to these guidelines.
 */
define('ROOT_PATH', realpath(__DIR__ . '/../../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../../includes/header.php');
?>

<DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/styles.css">
    <title>Underwear Content Guidelines</title>
</head>
<style>
    .doc-body {
        max-width: 900px;
        margin: 20px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        margin-bottom: 60px;
    }
    body.dark .doc-body {
        background-color: #222;
        color: #ddd;
    }
    .doc-content h1, .doc-content h2, .doc-content h3 {
        color: #333;
    }
    body.dark .doc-content h1, body.dark .doc-content h2, body.dark .doc-content h3 {
        color: #fff;
    }
    .doc-content p, .doc-content ul {
        line-height: 1.6;
        margin-bottom: 15px;
    }
</style>
<body>
    <nav>
        <?php include_once '../../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../../includes/site-notice.php'; ?>
    <div class="doc-body">
        <div class="doc-content">
            <h1>Underwear Content Guidelines</h1>
            <p>This platform is a <strong>SFW furry artboard</strong> with limited allowance for suggestive content.</p>
            
            <h2>Underwear & Suggestive Artwork</h2>
            <p>Artwork depicting characters in underwear is allowed under strict conditions.</p>
            
            <h2>Acceptable Underwear Content</h2>
            <ul>
                <li>Adult characters (18+ only)</li>
                <li>Fully covered underwear</li>
                <li>No nudity or explicit anatomy</li>
                <li>Mildly suggestive poses only</li>
                <li>Non-sexual artistic intent</li>
            </ul>

            <h2>Not Allowed</h2>
            <ul>
                <li>Nudity or explicit sexual content</li>
                <li>Fetish or pornographic material</li>
                <li>Sexual acts or implications</li>
                <li>Minors or age-ambiguous characters</li>
            </ul>

            <h2>Tips for Creators</h2>
            <ul>
                <li>Focus on character expression and story</li>
                <li>Avoid overly sexualized poses or angles</li>
                <li>Consider community standards and feedback</li>
                <li>When in doubt, err on the side of caution</li>
            </ul>

            <h2>Enforcement</h2>
            <p>Content violating these guidelines may be removed and could lead to account suspension.</p>
            <p>Report any questionable content to the moderation team for review.</p>
        </div>
    </div>
    <?php include('../../../includes/version.php'); ?>
    <footer>
        <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>