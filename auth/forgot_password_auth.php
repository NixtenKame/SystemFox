<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_or_email']);
    $answer1 = trim($_POST['answer1']);
    $answer2 = trim($_POST['answer2']);

    // Fetch user data based on username or email
    $query = $db->prepare("SELECT id, security_question1, security_answer1, security_question2, security_answer2 FROM users WHERE username = ? OR email = ?");
    $query->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify security question answers
        if (
            password_verify($answer1, $user['security_answer1']) &&
            password_verify($answer2, $user['security_answer2'])
        ) {
            // Store user ID in session for password reset
            $_SESSION['reset_user_id'] = $user['id'];
            header("Location: /actions/reset_password");
            exit();
        } else {
            $error = "Incorrect answers to the security questions.";
        }
    } else {
        $error = "No user found with the provided username or email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <p>Please answer the security questions to reset your password.</p>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrf_input(); ?>
            <label for="username_or_email">Username or Email:</label>
            <input type="text" name="username_or_email" id="username_or_email" required>

            <label for="answer1">Answer to Security Question 1:</label>
            <input type="text" name="answer1" id="answer1" required>

            <label for="answer2">Answer to Security Question 2:</label>
            <input type="text" name="answer2" id="answer2" required>

            <button type="submit">Submit</button>
        </form>
    </div>
    <?php include('../includes/version.php'); ?>
<footer>
<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
</footer>
</body>
</html>