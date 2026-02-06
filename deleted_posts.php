<?php
define('ROOT_PATH', realpath(__DIR__ . '/..'));

include_once ROOT_PATH . '/connections/config.php';
include_once 'includes/header.php';

/* -----------------------------
   Load all users into a lookup
------------------------------*/
$usernames = [];

$stmtUsers = $db->prepare("SELECT id, username FROM users");
$stmtUsers->execute();
$resultUsers = $stmtUsers->get_result();

while ($row = $resultUsers->fetch_assoc()) {
    $usernames[(int)$row['id']] = $row['username'];
}
$stmtUsers->close();

/* -----------------------------
   Fetch deleted posts
------------------------------*/
$deleted_posts = [];

$sqlDeleted = "
    SELECT
        dp.post_id,
        dp.created_at AS deleted_at,
        dp.reason,
        u.uploaded_by,
        u.upload_date,
        u.tag_string
    FROM destroyed_posts dp
    LEFT JOIN uploads u 
        ON u.id = dp.post_id
    ORDER BY dp.created_at DESC
";

$stmtDeleted = $db->prepare($sqlDeleted);
$stmtDeleted->execute();
$resultDeleted = $stmtDeleted->get_result();

while ($row = $resultDeleted->fetch_assoc()) {
    $deleted_posts[] = $row;
}

$stmtDeleted->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deleted Posts - FluffFox</title>
<link rel="stylesheet" href="public/css/styles.css">

<style>
table {
    border-collapse: collapse;
    width: 100%;
}
th, td {
    border: 1px solid #ddd;
    padding: 8px;
    vertical-align: top;
}
th {
    background-color: #f2f2f2;
}
body.dark th {
    background-color: #333;
}
.muted {
    color: #777;
    font-size: 0.85em;
}
</style>
</head>

<body>
<nav><?php include 'includes/nav.php'; ?></nav>
<?php include 'includes/site-notice.php'; ?>

<main>
<h2>Deleted Posts</h2>

<table>
<thead>
<tr>
    <th>Post</th>
    <th>Poster</th>
    <th>Tags</th>
    <th>Deleted At</th>
    <th>Reason</th>
</tr>
</thead>

<tbody>
<?php if (empty($deleted_posts)): ?>
<tr>
    <td colspan="5" class="muted">No deleted posts found.</td>
</tr>
<?php endif; ?>

<?php foreach ($deleted_posts as $post): ?>
<tr>
    <td>
        <strong><?= $post['post_id'] ?></strong><br>
    </td>

    <td>
        <?= htmlspecialchars($usernames[$post['uploaded_by']] ?? 'Unknown User') ?>
    </td>

    <td>
        <?= htmlspecialchars($post['tag_string'] ?? '') ?>
    </td>

    <td>
        <?= htmlspecialchars($post['deleted_at']) ?>
    </td>

    <td>
        <?= htmlspecialchars($post['reason'] ?? 'No reason provided') ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</main>

<?php include 'includes/version.php'; ?>
</body>
</html>