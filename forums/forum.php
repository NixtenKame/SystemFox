<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Fetch threads
$query = $db->query("SELECT forums.*, users.username FROM forums JOIN users ON forums.user_id = users.id ORDER BY created_at DESC");
$threads = $query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    
    <style>
        .forum-container {
            margin: 20px auto;
            padding: 20px;
            max-width: 800px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .forum-container h1 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .forum-container ul {
            list-style-type: none;
            padding: 0;
        }

        .forum-container li {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .forum-container li:last-child {
            border-bottom: none;
        }

        .forum-container a {
            text-decoration: none;
            color: #007bff;
        }

        .forum-container a:hover {
            text-decoration: underline;
        }

        .forum-container .thread-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .forum-container .thread-info span {
            color: #555;
        }

        body.dark .forum-container .thread-info span {
            color: #bbb;
        }
    </style>
</head>
<body>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <div class="forum-container">
        <?php if (isset($_SESSION['user_id'])): ?>
            <form action="/forums/create_thread" method="POST">
                <?php echo csrf_input(); ?>
                <input type="text" name="title" placeholder="Thread Title" required>
                <select name="category">
                    <option value="General">General</option>
                    <option value="Questions">Questions</option>
                    <option value="Feedback">Feedback</option>
                    <option value="Off-Topic">Off-Topic</option>
                </select>
                <button class="button" type="submit">Create Thread</button>
            </form>
        <?php endif; ?>

        <ul>
            <?php foreach ($threads as $thread): ?>
                <li>
                    <div class="thread-info">
                        <a href="thread?id=<?php echo htmlspecialchars($thread['id']); ?>">
                            <?php echo htmlspecialchars($thread['title']); ?>
                        </a>
                        <span>Posted by <?php echo htmlspecialchars($thread['username']); ?> on <?php echo htmlspecialchars($thread['created_at']); ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>