<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Check if the user is an admin or has the necessary permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "
    <h1>Access denied.</h1>";
    exit;
}

// Function to get the total number of users
function getTotalUsers($db) {
    $query = "SELECT COUNT(*) as total FROM users";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

$totalUploadsQuery = "SELECT COUNT(*) AS total_uploads FROM uploads";
$stmt = $db->prepare($totalUploadsQuery);
$stmt->execute();
$result = $stmt->get_result();
$totalUploads = ($result) ? $result->fetch_assoc()['total_uploads'] : 0;
$stmt->close();

// Function to get the total number of comments
function getTotalComments($db) {
    $query = "SELECT COUNT(*) as total FROM comments";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getTotalPosts($db) {
    $query = "SELECT  total_posts FROM post_count";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    return $row['total_posts'];
}

// Function to get the list of users
function getUsers($db) {
    $query = "SELECT id, username, email, created_at, last_ip_addr, birthdate, base_upload_limit, status FROM users";
    $result = $db->query($query);
    return $result;
}

// Handle news posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_news'])) {
    $newsTitle = trim($_POST['news_title']);
    $newsContent = trim($_POST['news_content']);
    $author = $_SESSION['username']; // Assuming the admin's username is stored in the session

    if (!empty($newsTitle) && !empty($newsContent)) {
        $stmt = $db->prepare("INSERT INTO news (title, content, author) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $newsTitle, $newsContent, $author);
            if ($stmt->execute()) {
                $newsSuccessMessage = "News posted successfully!";
            } else {
                $newsErrorMessage = "Failed to post news: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $newsErrorMessage = "Failed to prepare the statement: " . $db->error;
        }
    } else {
        $newsErrorMessage = "Please fill in all fields.";
    }
}

// Handle notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $userId = $_POST['user_id'];
    $message = trim($_POST['message']);

    if (!empty($userId) && !empty($message)) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message, status, dismissed, created_at) VALUES (?, ?, 'unread', 0, NOW())");
        $stmt->bind_param("is", $userId, $message);
        if ($stmt->execute()) {
            $successMessage = "Notification sent successfully!";
        } else {
            $errorMessage = "Failed to send notification: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errorMessage = "Please select a user and enter a message.";
    }
}

// Handle password hashing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_hash'])) {
    $plainPassword = trim($_POST['plain_password']);
    if (!empty($plainPassword)) {
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    } else {
        $hashErrorMessage = "Please enter a password to hash.";
    }
}

// Handle version update posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_version_update'])) {
    $version = trim($_POST['version']);
    $release_date = $_POST['release_date'];
    $release_time = $_POST['release_time'];
    $notes = trim($_POST['notes']);

    if (!empty($version) && !empty($release_date) && !empty($release_time) && !empty($notes)) {
        $stmt = $db->prepare("INSERT INTO version_updates (version, release_date, release_time, notes) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $version, $release_date, $release_time, $notes);
            if ($stmt->execute()) {
                $versionSuccessMessage = "Version update posted successfully!";
            } else {
                $versionErrorMessage = "Failed to post version update: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $versionErrorMessage = "Failed to prepare the statement: " . $db->error;
        }
    } else {
        $versionErrorMessage = "Please fill in all fields.";
    }
}

$totalUsers = getTotalUsers($db);
$postCount = getTotalPosts($db);
$totalComments = getTotalComments($db);
$users = getUsers($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../public/css/styles.css" />
    <title>Admin Panel</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .notification-form, .hash-form, .news-form {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .notification-form label, .hash-form label, .news-form label {
            display: block;
            margin-bottom: 10px;
        }
        .notification-form select, .notification-form textarea, .notification-form button,
        .hash-form input, .hash-form button,
        .news-form input, .news-form textarea, .news-form button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
            /* General container styling for dark mode */
    body.dark .container, body.dark .news-form, body.dark .notification-form, body.dark .hash-form {
        background-color: #333; /* Dark background */
        color: #fff; /* Light text */
        border: 1px solid #555; /* Subtle border */
    }

    /* Table styling for dark mode */
    body.dark table {
        background-color: #444; /* Dark background for the table */
        color: #fff; /* Light text for table content */
        border: 1px solid #555; /* Subtle border for the table */
    }

    body.dark th {
        background-color: #555; /* Slightly lighter background for table headers */
        color: #fff; /* Light text for headers */
    }

    body.dark td {
        background-color: #444; /* Dark background for table cells */
        color: #fff; /* Light text for cells */
    }

    /* Add hover effect for table rows in dark mode */
    body.dark tr:hover {
        background-color: #555; /* Highlighted row background */
    }

    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function () {
        $('#user_id').select2({
            placeholder: "Search for a user",
            allowClear: true,
            ajax: {
                url: $('#user_id').data('ajax-url'),
                dataType: 'json',
                processResults: function (data) {
                    return { results: data };
                }
            }
        });
    });
    </script>
</head>
<body>
    <nav>
        <?php include_once '../includes/nav.php'; ?>
    </nav>

    <?php include_once '../includes/site-notice.php'; ?>

    <main>
        <h2>Website Statistics</h2>
        <p>Total Users: <?php echo $totalUsers; ?></p>
        <p>Total Posts: <?php echo $postCount; ?></p>
        <p>Total Comments: <?php echo $totalComments; ?></p>

        <h2>Post News</h2>
        <div class="news-form">
            <?php if (isset($newsSuccessMessage)): ?>
                <p style="color: green;"><?php echo htmlspecialchars($newsSuccessMessage); ?></p>
            <?php endif; ?>
            <?php if (isset($newsErrorMessage)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($newsErrorMessage); ?></p>
            <?php endif; ?>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <label for="news_title">News Title:</label>
                <input type="text" name="news_title" id="news_title" placeholder="Enter news title" required />

                <label for="news_content">News Content:</label>
                <textarea name="news_content" id="news_content" rows="4" placeholder="Enter news content" required></textarea>

                <button type="submit" name="post_news">Post News</button>
            </form>
        </div>

        <h2>Post Version Update</h2>
        <div class="news-form">
            <?php if (isset($versionSuccessMessage)): ?>
                <p style="color: green;"><?php echo htmlspecialchars($versionSuccessMessage); ?></p>
            <?php endif; ?>
            <?php if (isset($versionErrorMessage)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($versionErrorMessage); ?></p>
            <?php endif; ?>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <label for="version">Version:</label>
                <input type="text" name="version" id="version" placeholder="e.g. 1.0.0" required />

                <label for="release_date">Release Date:</label>
                <input type="date" name="release_date" id="release_date" value="<?php echo date('Y-m-d'); ?>" required />

                <label for="release_time">Release Time:</label>
                <input type="time" name="release_time" id="release_time" value="<?php echo date('H:i'); ?>" required />

                <label for="notes">Release Notes:</label>
                <textarea name="notes" id="notes" rows="4" placeholder="Describe what's new..." required></textarea>

                <button type="submit" name="post_version_update">Post Version Update</button>
            </form>
        </div>

        <h2>Send Notification</h2>
        <div class="notification-form">
            <?php if (isset($successMessage)): ?>
                <p style="color: green;"><?php echo htmlspecialchars($successMessage); ?></p>
            <?php endif; ?>
            <?php if (isset($errorMessage)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <label for="user_id">Select User:</label>
                <select name="user_id" id="user_id" required data-ajax-url="/api/user_search">
                    <option value="">-- Select a User --</option>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['id']); ?>">
                            <?php echo htmlspecialchars($row['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="message">Notification Message:</label>
                <textarea name="message" id="message" rows="4" placeholder="Enter your notification message here..." required></textarea>

                <button type="submit" name="send_notification">Send Notification</button>
            </form>
        </div>

        <h2>Generate Hashed Password</h2>
        <div class="hash-form">
            <?php if (isset($hashedPassword)): ?>
                <p style="color: green;">Hashed Password: <code><?php echo htmlspecialchars($hashedPassword); ?></code></p>
            <?php endif; ?>
            <?php if (isset($hashErrorMessage)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($hashErrorMessage); ?></p>
            <?php endif; ?>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <label for="plain_password">Enter Plain Text Password:</label>
                <input type="text" name="plain_password" id="plain_password" placeholder="Enter password" required />

                <button type="submit" name="generate_hash">Generate Hash</button>
            </form>
        </div>

        <h2>Users</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Created At</th>
                    <th>Last IP Address</th>
                    <th>Age</th>
                    <th>Birthdate</th>
                    <th>Base Upload Limit</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = getUsers($db); // Re-fetch users for the table
                while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['username'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['last_ip_addr'] ?? ''); ?></td>
                        <td><?php
                        if (!empty($row['birthdate'])) {
                            $birthdate = new DateTime($row['birthdate']);
                            $today = new DateTime();
                            $age = $today->diff($birthdate)->y;
                            echo htmlspecialchars($age, ENT_QUOTES, 'UTF-8');
                        } else {
                            echo "N/A";
                        }
                        ?></td>
                        <td><?php echo htmlspecialchars($row['birthdate'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['base_upload_limit'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <br><br><br><br>
    </main>

    <?php include('../includes/version.php'); ?>

    <footer>
        <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved.
            <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a>
        </p>
    </footer>
</body>
</html>