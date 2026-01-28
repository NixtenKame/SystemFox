<?php
define('ROOT_PATH', realpath(__DIR__ . '/..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('includes/header.php');

$itemsPerPage = 50;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$allowedSorts = ['name', 'popular', 'date'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSorts) ? $_GET['sort'] : 'name';

switch ($sort) {
    case 'popular':
        $orderBy = 't.post_count DESC';
        break;
    case 'date':
        $orderBy = 'earliest_upload.upload_date ASC';
        break;
    case 'name':
    default:
        $orderBy = 't.tag_name ASC';
        break;
}

$totalQuery = "SELECT COUNT(*) AS total FROM tags";
$totalResult = $db->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalItems = intval($totalRow['total']);
$totalPages = ceil($totalItems / $itemsPerPage);

$tagsQuery = "
SELECT
    t.tag_id,
    t.tag_name,
    t.post_count,
    earliest_upload.upload_id,
    earliest_upload.upload_date,
    u.username AS first_user
FROM tags t
LEFT JOIN (
    SELECT ut.tag_id, up.id AS upload_id, up.upload_date, up.uploaded_by
    FROM upload_tags ut
    JOIN uploads up ON up.id = ut.upload_id
    WHERE (up.upload_date, up.id) = (
        SELECT up2.upload_date, up2.id
        FROM upload_tags ut2
        JOIN uploads up2 ON up2.id = ut2.upload_id
        WHERE ut2.tag_id = ut.tag_id
        ORDER BY up2.upload_date ASC, up2.id ASC
        LIMIT 1
    )
) AS earliest_upload ON earliest_upload.tag_id = t.tag_id
LEFT JOIN users u ON u.id = earliest_upload.uploaded_by
ORDER BY $orderBy
LIMIT $itemsPerPage OFFSET $offset
";

$tagsResult = $db->query($tagsQuery);
if (!$tagsResult) {
    die("Query error: " . $db->error);
}

date_default_timezone_set('America/Chicago');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tags - FluffFox</title>
    <link rel="stylesheet" href="../public/css/styles.css" />
    
</head>
<body>

    <nav>
        <?php include_once 'includes/nav.php'; ?>
    </nav>
    <?php include_once 'includes/site-notice.php'; ?>

    <main>
        <div class="content">
            <h2>All Tags</h2>

            <!-- Sorting options -->
            <div class="sort-options" style="margin-bottom:1em;">
                Sort by: 
                <a href="?sort=name<?php echo $currentPage > 1 ? '&page=' . $currentPage : ''; ?>" <?php if ($sort === 'name') echo 'style="font-weight:bold;color:#005999;"'; ?>>Name</a> | 
                <a href="?sort=popular<?php echo $currentPage > 1 ? '&page=' . $currentPage : ''; ?>" <?php if ($sort === 'popular') echo 'style="font-weight:bold;color:#005999;"'; ?>>Popular</a> | 
                <a href="?sort=date<?php echo $currentPage > 1 ? '&page=' . $currentPage : ''; ?>" <?php if ($sort === 'date') echo 'style="font-weight:bold;color:#005999;"'; ?>>Date Created</a>
            </div>

            <table class="tags-table">
                <thead>
                    <tr>
                        <th>Count</th>
                        <th>Tag</th>
                        <th>First User</th>
                        <th>First Upload</th>
                        <th>First Upload Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($tagRow = $tagsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tagRow['post_count']); ?></td>
                            <td>
                                <a href="/posts/?q=<?php echo urlencode($tagRow['tag_name']); ?>">
                                    <?php echo htmlspecialchars($tagRow['tag_name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($tagRow['first_user']): ?>
                                    <a href="/<?php echo urlencode($tagRow['first_user']); ?>">
                                        <?php echo htmlspecialchars($tagRow['first_user']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tagRow['upload_id']): ?>
                                    <a href="/posts/<?php echo urlencode($tagRow['upload_id']); ?>">View</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($tagRow['upload_date'])) {
                                    $date = new DateTime($tagRow['upload_date']);
                                    echo htmlspecialchars($date->format('Y-m-d'));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>&sort=<?php echo $sort; ?>">&laquo; Previous</a>
            <?php endif; ?>

            <?php
            $pageRange = 2;
            $startPage = max(1, $currentPage - $pageRange);
            $endPage = min($totalPages, $currentPage + $pageRange);

            if ($startPage > 1):
                echo '<a href="?page=1&sort=' . $sort . '">1</a>';
                if ($startPage > 2) echo '<span class="dots">...</span>';
            endif;

            for ($i = $startPage; $i <= $endPage; $i++):
                echo '<a href="?page=' . $i . '&sort=' . $sort . '" class="' . ($i === $currentPage ? 'active' : '') . '">' . $i . '</a>';
            endfor;

            if ($endPage < $totalPages):
                if ($endPage < $totalPages - 1) echo '<span class="dots">...</span>';
                echo '<a href="?page=' . $totalPages . '&sort=' . $sort . '">' . $totalPages . '</a>';
            endif;
            ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>&sort=<?php echo $sort; ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </main>

    <?php include('includes/version.php'); ?>
    <footer>
        <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <?php echo htmlspecialchars($version); ?></p>
    </footer>
</body>
</html>