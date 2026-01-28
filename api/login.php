<?php
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw input: " . file_get_contents('php://input'));
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: https://nixten.ddns.net");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

// --------------------
// GET request = return CSRF token
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    echo json_encode([
        "success" => true,
        "csrf_token" => $_SESSION['csrf_token']
    ]);
    exit;
}

// --------------------
// POST request = attempt login
// --------------------
$data = json_decode(file_get_contents("php://input"), true);

// Check JSON payload
if (!$data) {
    echo json_encode(["success" => false, "error" => "No data received"]);
    exit;
}

// CSRF check
$csrfToken = $data['csrf_token'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    echo json_encode(["success" => false, "error" => "Invalid CSRF token"]);
    exit;
}

// Extract input
$usernameOrEmail = trim($data['username_or_email'] ?? '');
$password = $data['password'] ?? '';
$rememberMe = $data['remember_me'] ?? false;

if (empty($usernameOrEmail) || empty($password)) {
    echo json_encode(["success" => false, "error" => "Username/email and password required"]);
    exit;
}

// Check database for user (mysqli)
$stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
if ($stmt) {
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : false;
    $stmt->close();
} else {
    error_log("DB prepare failed: " . $db->error);
    echo json_encode(["success" => false, "error" => "Server error"]);
    exit;
}

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    // Update last login time (mysqli)
    $updateLogin = $db->prepare("UPDATE users SET last_logged_in_at = NOW() WHERE id = ?");
    if ($updateLogin) {
        $updateLogin->bind_param("i", $user['id']);
        $updateLogin->execute();
        $updateLogin->close();
    }

    // Handle remember-me
    if ($rememberMe) {
        $token = bin2hex(random_bytes(16));
        $insertToken = $db->prepare("INSERT INTO remember_me (user_id, token) VALUES (?, ?)");
        if ($insertToken) {
            $insertToken->bind_param("is", $user['id'], $token);
            $insertToken->execute();
            $insertToken->close();
        }
        setcookie('remember_me_token', $token, time() + (86400 * 30), "/", "", true, true);
    }

    // Return login success + API token
    echo json_encode([
        "success" => true,
        "user_id" => (int)$user['id'],
        "username" => $user['username'],
        "token" => bin2hex(random_bytes(32))
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Invalid username/email or password"]);
}
