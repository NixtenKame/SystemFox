<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Initialize search username variable
$searchUsername = trim($_GET['q'] ?? '');

// Prepare results variable
$users = [];
$error = '';

if (!empty($searchUsername)) {
    // Search for users matching the username input
    $stmt = $db->prepare("SELECT id, username, profile_picture, online_status FROM users WHERE username LIKE CONCAT('%', ?, '%') LIMIT 20");
    $stmt->bind_param("s", $searchUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "No users found.";
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
    
    <title>User Search</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            backdrop-filter: blur(5px);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .search-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .search-container input[type="text"] {
            width: 70%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }
        .search-container button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .search-container button:hover {
            background-color: #0056b3;
        }
        .user-list {
            list-style: none;
            padding: 0;
        }
        .user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            backdrop-filter: blur(8px);
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            background-color: hsla(0,0%,0%,.5);
        }
        .profile-picture {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 10px;
        }
        .online { background: green; }
        .offline { background: red; }
        .error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
<nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

<main>
    <div class="container">
        <div class="search-container">
            <form action="/user/" method="GET">
                <?php echo csrf_input(); ?>
                <input type="text" name="q" placeholder="Search for a user..." required value="<?php echo htmlspecialchars($searchUsername); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($users)): ?>
            <h2>Search Results:</h2>
            <ul class="user-list">
                <?php foreach ($users as $user): ?>
                    <li class="user-item">
                        <a href="<?php echo urlencode($user['username']); ?>">
                            <img class="profile-picture" 
                                src="<?php echo htmlspecialchars($user['profile_picture'] ? '/public/uploads/' . htmlspecialchars($user['profile_picture']) : '/public/images/default-profile.png'); ?>" 
                                alt="Profile Picture" width="50">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <span class="status-indicator <?php echo $user['online_status'] == 'online' ? 'online' : 'offline'; ?>"></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

<?php include('../includes/version.php'); ?>
<footer>
    <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
</footer>
</body>
</html>