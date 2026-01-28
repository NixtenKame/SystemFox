<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once '../../incLudes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <style>
        .comming-soon {
            text-align: center;
            margin-top: 50px;
            font-size: 1.5em;
            color: #333;
            background-color: #f0f0f0;
        }
        body.dark .comming-soon {
            text-align: center;
            margin-top: 50px;
            font-size: 1.5em;
            color: white;
            background-color: #555;
        }
    </style>
</head>
<body>
    <nav>
<?php include_once '../../includes/nav.php'; ?>
    </nav>
    <?php include_once '../../includes/site-notice.php'; ?>

    <h1>Search Results:</h1>
    <div class="search-results">
        <div class="comming-soon">
            <h1>Comming Soon!</h1>
        </div>
    </div>
</body>
</html>