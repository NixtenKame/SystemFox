<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', realpath(__DIR__ . '/..'));

include_once ROOT_PATH . '/connections/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /posts");
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$redirect = null;

// Handle remember_me token (auto-login)
if (isset($_COOKIE['remember_me_token']) && !isset($_SESSION['user_id'])) {
    $token = $_COOKIE['remember_me_token'];
    $stmt = $db->prepare("SELECT user_id FROM remember_me WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];

        $user_stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();

        if ($user_result && $user_result->num_rows == 1) {
            $user_data = $user_result->fetch_assoc();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['username'] = $user_data['username'];
            $redirect = $_SESSION['redirect_url'] ?? '/posts';
            unset($_SESSION['redirect_url']);
            header("Location: $redirect");
            exit;
        }
    }
}

// Handle POST login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = "Invalid request. Please try again.";
    } else {
        $usernameOrEmail = trim($_POST['username_or_email']);
        $password = trim($_POST['password']);
        $rememberMe = isset($_POST['remember_me']);

        if (empty($usernameOrEmail) || empty($password)) {
            $error = "Please fill in both fields.";
        } else {
            $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows == 1) {
                    $user = $result->fetch_assoc();

                    if (password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];

                        $updateLoginTime = $db->prepare("UPDATE users SET last_logged_in_at = NOW() WHERE id = ?");
                        $updateLoginTime->bind_param("i", $user['id']);
                        $updateLoginTime->execute();
                        $updateLoginTime->close();

                        if ($rememberMe) {
                            $rememberToken = bin2hex(random_bytes(16));
                            $insertStmt = $db->prepare("INSERT INTO remember_me (user_id, token) VALUES (?, ?)");
                            $insertStmt->bind_param("is", $user['id'], $rememberToken);
                            $insertStmt->execute();
                            $insertStmt->close();
                            setcookie('remember_me_token', $rememberToken, time() + (86400 * 30), "/");
                        }

                        $redirect = $_SESSION['redirect_url'] ?? '/posts';
                        unset($_SESSION['redirect_url']);
                        header("Location: $redirect");
                        exit;
                    } else {
                        $error = "Invalid credentials.";
                    }
                } else {
                    $error = "User not found.";
                }
                $stmt->close();
            } else {
                $error = "Error preparing SQL statement.";
            }
        }
    }
}

// ===== OUTPUT PHASE: Now we can include header.php and output HTML =====
include_once('includes/header.php');

// Update online status (only if logged in, which we are NOT at this point in the login page)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("UPDATE users SET online_status = 'online' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
    } else {
        error_log("Error updating online status: " . $stmt->error);
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/css/styles.css">
    
    <title>Login</title>
</head>
<body>
<nav>
<?php include_once 'includes/nav.php'; ?>
    </nav>

    <?php include_once 'includes/site-notice.php'; ?>

    <main>
    <div class="login">
    <form method="POST" action="/login">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <label for="username_or_email">Username or Email:</label>
    <input type="text" id="username_or_email" name="username_or_email" required>
    <br>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    
    <label class="custom-checkbox">
        <input type="checkbox" id="showPassword">
        <span class="checkmark"></span>
        Show Password
    </label>
    
    <br>

    <label class="custom-checkbox">
        <input type="checkbox" id="remember_me" name="remember_me" checked>
        <span class="checkmark"></span>
        Remember Me
    </label>
    
    <br>

    <button class="login button" type="submit">Login</button>
    <button class="login button" type="button" onclick="window.location.href='/register'">Don't have an account? Sign up</button>
    <button class="login button" type="button" onclick="window.location.href='/auth/forgot_password'">Forgot password?</button>
</form>
</div>
</main>
    <br>
    <br>
    <br>
    <br>
    <?php include('includes/version.php'); ?>
    <footer>
    <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
    
<script>
    document.getElementById("showPassword").addEventListener("change", function () {
        var passwordInput = document.getElementById("password");
        passwordInput.type = this.checked ? "text" : "password";
    });
</script>
</body>
</html>