<?php
include_once('../includes/header.php'); // Include common header

// Sanitize the message parameter
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : "An error occurred.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting...</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <script>
        setTimeout(function() {
            window.location.href = "/login";
        }, 5000);
    </script>
</head>
<body>
    <header>
        <h1>Redirecting...</h1>
    </header>
    <main>
        <p><?php echo $message; ?></p>
        <p>You will be redirected to the login page shortly.</p>
    </main>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>