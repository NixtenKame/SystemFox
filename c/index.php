<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view users.");
}

$user_id = $_SESSION['user_id'];

/**
 * Get logged-in user's age from birthdate
 */
$user_stmt = $db->prepare("
    SELECT TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age
    FROM users
    WHERE id = ?
");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user_data) {
    die("Error: User not found.");
}

$viewer_age = (int)$user_data['age'];

/**
 * Determine visibility rules
 */
if ($viewer_age >= 18) {
    // 18+ users can ONLY see 18+ users
    $ageCondition = "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 18";
} else {
    // 13–17 users can ONLY see 13–17 users
    $ageCondition = "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 13 AND 17";
}

// Pagination + search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

/**
 * Main query
 */
if ($search) {
    $stmt = $db->prepare("
        SELECT id, username, profile_picture, online_status
        FROM users
        WHERE username LIKE ?
          AND $ageCondition
        LIMIT ?, ?
    ");
    $searchTerm = "%$search%";
    $stmt->bind_param("sii", $searchTerm, $offset, $limit);
} else {
    $stmt = $db->prepare("
        SELECT id, username, profile_picture, online_status
        FROM users
        WHERE $ageCondition
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$result = $stmt->get_result();

/**
 * Total count for pagination
 */
$total_stmt = $db->prepare("
    SELECT COUNT(*) AS count
    FROM users
    WHERE $ageCondition
");
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_users = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_users / $limit);

$total_stmt->close();
$stmt->close();

/**
 * Online status helper
 */
function getUserStatus($user_id, $conn) {
    $stmt = $conn->prepare("SELECT online_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return ($row && $row['online_status'] === 'online') ? '🟢' : '🔴';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    
    <style>
        .container {
            max-width: 100%;
            margin: 20px;
            background: white;
            padding: 70px;
            border-radius: 15px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 120px;
        }
        body.dark .container {
            background: #1e1e1e;
            color: #ffffff;
        }
        .search-box {
            width: 79%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .user-list {
            list-style: none;
            padding: 0;
        }
        .user-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }
        .user-item a {
            display: flex;
            align-items: center;
            width: 100%;
            height: 100%;
            padding: 10px;
            background-color: #eee;
            border-radius: 15px;
        }
        .user-item a:hover {
            background-color: #ddd;
            text-decoration: none;
        }
        body.dark .user-item a {
            background-color: #2e2e2e;
        }
        body.dark .user-item a:hover {
            background-color: #3e3e3e;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: auto;
        }
        .online { background: green; }
        .offline { background: red; }
        .pagination {
            display: flex;
            justify-content: center;
            margin: 0 0;
            align-items: center;
            margin-bottom: 0;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            padding: 5px 10px;
            background: #007bff;
            color: white;
            border-radius: 5px;
        }
        .pagination a.disabled {
            background: gray;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <br>
    <div class="container">
        <h2>User List</h2>
        <form method="GET">
            <input type="text" name="search" class="search-box" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="button" type="submit">Search</button>
        </form>

        <ul class="user-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <li class="user-item">
                <a href="<?php echo htmlspecialchars($row['id']); ?>"><img src="<?php echo $row['profile_picture'] ? '../public/uploads/' . htmlspecialchars($row['profile_picture']) : '../public/images/default-profile.png'; ?>" alt="profile_picture" class="user-avatar"><?php echo htmlspecialchars($row['username']); ?></a>
                <span class="status-indicator <?php echo $row['online_status'] == 'online' ? 'online' : 'offline'; ?>"></span>
                </li>
            <?php endwhile; ?>
        </ul>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
            <?php else: ?>
                <a class="disabled">Previous</a>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
            <?php else: ?>
                <a class="disabled">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <br>
    <br>
    <br>
    <?php include('../includes/version.php'); ?>
    <footer>
    <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>