<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));
include_once ROOT_PATH . '/connections/config.php';
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . 'logs/search_images_error.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$rawTag = trim((string)($_GET['tag'] ?? ''));
if ($rawTag === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tag parameter.']);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1;
if ($limit <= 0) $limit = 1;
$random = isset($_GET['random']) ? (int)$_GET['random'] : 1;
$mode = isset($_GET['mode']) ? strtolower($_GET['mode']) : 'all'; // 'all' (AND) or 'any' (OR)

try {
    // Normalize and split incoming tags (comma-separated)
    $parts = array_filter(array_map('trim', explode(',', $rawTag)), function($v){ return $v !== ''; });
    if (empty($parts)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid tag parameter.']);
        exit;
    }

    // Prepare patterns
    $patterns = [];
    foreach ($parts as $p) {
        $patterns[] = '%' . mb_strtolower($p, 'UTF-8') . '%';
    }

    $items = [];

    if ($mode === 'any') {
        // OR matching (any tag) - simpler, same as previous behavior
        $whereClauses = [];
        $params = [];
        $types = '';
        foreach ($patterns as $pat) {
            $whereClauses[] = "LOWER(t.tag_name) LIKE ?";
            $params[] = $pat;
            $types .= 's';
        }

        $orderClause = $random ? 'ORDER BY RAND()' : 'ORDER BY up.upload_date DESC';
        $sql = "
            SELECT DISTINCT up.id, up.file_name, up.category, up.display_name, up.uploaded_by, up.upload_date, up.description
            FROM uploads up
            JOIN upload_tags ut ON ut.upload_id = up.id
            JOIN tags t ON t.tag_id = ut.tag_id
            WHERE (" . implode(' OR ', $whereClauses) . ")
            $orderClause
            LIMIT ?
        ";

        $stmt = $db->prepare($sql);
        if ($stmt === false) throw new Exception('Failed to prepare query: ' . $db->error);

        // bind params + limit
        $types_with_limit = $types . 'i';
        $bindValues = array_merge($params, [$limit]);
        $refs = [];
        $refs[] = & $types_with_limit;
        foreach ($bindValues as $k => $v) $refs[] = & $bindValues[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // AND matching (all tags) - require each tag to exist for the upload
        // Use EXISTS subqueries (one per tag) for robust partial-match AND logic
        $existsClauses = [];
        $params = [];
        $types = '';
        foreach ($patterns as $pat) {
            $existsClauses[] = "EXISTS (
                SELECT 1 FROM upload_tags ut2
                JOIN tags t2 ON t2.tag_id = ut2.tag_id
                WHERE ut2.upload_id = up.id AND LOWER(t2.tag_name) LIKE ?
            )";
            $params[] = $pat;
            $types .= 's';
        }

        $orderClause = $random ? 'ORDER BY RAND()' : 'ORDER BY up.upload_date DESC';
        $sql = "
            SELECT up.id, up.file_name, up.category, up.display_name, up.uploaded_by, up.upload_date, up.description
            FROM uploads up
            WHERE " . implode(' AND ', $existsClauses) . "
            $orderClause
            LIMIT ?
        ";

        $stmt = $db->prepare($sql);
        if ($stmt === false) throw new Exception('Failed to prepare query: ' . $db->error);

        // bind params + limit
        $types_with_limit = $types . 'i';
        $bindValues = array_merge($params, [$limit]);
        $refs = [];
        $refs[] = & $types_with_limit;
        foreach ($bindValues as $k => $v) $refs[] = & $bindValues[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $result = $stmt->get_result();
    }

    while ($row = $result->fetch_assoc()) {
        $fileName = $row['file_name'];
        $relativeUrl = '/public/uploads/' . rawurlencode($row['category']) . '/' . rawurlencode(basename($fileName));
        $imageUrl = rtrim(SITE_URL, '/') . $relativeUrl;
        $pageUrl = rtrim(SITE_URL, '/') . '/posts/' . urlencode($row['id']);

        $items[] = [
            'id' => (int)$row['id'],
            'display_name' => $row['display_name'],
            'description' => $row['description'] ?? $row['display_name'],
            'category' => $row['category'],
            'image_url' => $imageUrl,
            'relative_url' => $relativeUrl,
            'url' => $pageUrl,
            'uploaded_by' => (int)$row['uploaded_by'],
            'upload_date' => $row['upload_date'],
        ];
    }

    $stmt->close();

    if (empty($items)) {
        echo json_encode(['error' => 'No images found for tag(s): ' . $rawTag]);
        exit;
    }

    if ($limit === 1) {
        $item = $items[0];
        echo json_encode([
            'url' => $item['url'],
            'image_url' => $item['image_url'],
            'description' => $item['description'],
            'id' => $item['id'],
            'category' => $item['category'],
            'uploaded_by' => $item['uploaded_by'],
            'upload_date' => $item['upload_date'],
        ]);
        exit;
    }

    echo json_encode([
        'results' => $items,
        'count' => count($items),
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
    exit;
}
?>