<?php
$latest = $db->query("SELECT id, version, release_date, release_time, notes FROM version_updates ORDER BY id DESC LIMIT 1")->fetch_assoc();
$latest_id = $latest ? $latest['id'] : 0;
$version = $latest ? $latest['version'] : '';
$latest_date = $latest ? $latest['release_date'] : '';
$latest_time = $latest ? $latest['release_time'] : '';
$latest_notes = $latest ? $latest['notes'] : '';
?>