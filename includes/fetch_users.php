<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

$query = "SELECT id, username FROM users";
$result = $db->query($query);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = ['id' => $row['id'], 'text' => $row['username']];
}

echo json_encode($users);
?>