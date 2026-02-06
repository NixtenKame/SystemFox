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
        <title>Keyboard Shortcuts - FluffFox</title>
        <link rel="stylesheet" href="../public/css/styles.css">
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
                max-width: 600px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            body.dark th, td {
                border-color: #555;
            }
            body.dark th {
                background-color: #333;
            }
        </style>
    </head>
    <body>
        <nav>
            <?php include_once '../includes/nav.php'; ?>
        </nav>
        <?php include_once '../includes/site-notice.php'; ?>
        <main>
            <h2>Keyboard Shortcuts</h2>
            <p>Enhance your navigation experience on FluffFox with these keyboard shortcuts:</p>
            <table>
                <thead>
                    <tr>
                        <th>Shortcut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>J</strong></td>
                        <td>Next Post</td>
                    </tr>
                    <tr>
                        <td><strong>K</strong></td>
                        <td>Previous Post</td>
                    </tr>
                    <tr>
                        <td><strong>L</strong></td>
                        <td>Like/Unlike Post</td>
                    </tr>
                    <tr>
                        <td><strong>S</strong></td>
                        <td>Save/Unsave Post</td>
                    </tr>
                    <tr>
                        <td><strong>Ctrl + K</strong></td>
                        <td>Open Search</td>
                    </tr>
                </tbody>
            </table>
            <p>Use these shortcuts to quickly navigate through posts and manage your favorites!</p>
        </main>
        <?php include_once '../includes/version.php'; ?>
        <footer>
	        <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
        </footer>
    </body>
</html>