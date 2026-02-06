<?php
define('ROOT_PATH', realpath(__DIR__ . '/..'));

include_once ROOT_PATH . '/connections/config.php';
include('includes/header.php');

$deleted_posts = [];

$sql = "
    SELECT
        id,
        display_name,
        category,
        uploaded_by,
        upload_date,
        tag_string,
        tag_count
    FROM uploads
    WHERE is_deleted = 1
    ORDER BY upload_date DESC
";

$stmt = $db->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $deleted_posts[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deleted Posts – FluffFox</title>
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
</tr>
</thead>

<tbody>
<?php if (empty($deleted_posts)): ?>
<tr>
    <td colspan="3" class="muted">No deleted posts found.</td>
</tr>
<?php endif; ?>

<?php foreach ($deleted_posts as $post): ?>
<tr>
    <td>
        <strong>#<?= $post['id'] ?></strong><br>
        <?= htmlspecialchars($post['display_name']) ?><br>
        <span class="muted"><?= strtoupper($post['category']) ?></span>
    </td>

    <td>
        User #<?= $post['uploaded_by'] ?><br>
        <span class="muted"><?= $post['upload_date'] ?></span>
    </td>

    <td>
        <?= htmlspecialchars($post['tag_string']) ?><br>
        <span class="muted"><?= $post['tag_count'] ?> tags</span>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</main>

<?php include 'includes/version.php'; ?>
</body>
</html>
