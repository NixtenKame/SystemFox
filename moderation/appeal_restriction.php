<?php

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

define('ROOT_PATH', realpath(__DIR__ . '/../../'));

include_once ROOT_PATH . '/connections/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to appeal a ban.");
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appeal_reason = trim($_POST['appeal_reason']);

    if (empty($appeal_reason)) {
        $error = "Appeal reason cannot be empty.";
    } else {
        // Insert appeal into database
        $stmt = $db->prepare("INSERT INTO ban_appeals (user_id, appeal_reason) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $appeal_reason);

        if ($stmt->execute()) {
            $success = "Your appeal has been submitted.";
        } else {
            $error = "Failed to submit appeal.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appeal Ban</title>
</head>
<body>
    <h1>Appeal Your Ban or Suspension</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif (isset($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <label for="appeal_reason">Reason for Appeal:</label><br>
    <textarea name="appeal_reason" id="appeal_reason" rows="6" cols="50" required></textarea><br><br>
    <button type="submit">Submit Appeal</button>
</form>

</body>
</html>
