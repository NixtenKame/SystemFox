<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

if (!isset($_GET['id'])) {
    die("Thread ID is missing.");
}

$threadId = intval($_GET['id']);

// Fetch thread details
$query = $db->prepare("SELECT forums.*, users.username FROM forums JOIN users ON forums.user_id = users.id WHERE forums.id = ?");
$query->bind_param("i", $threadId);
$query->execute();
$result = $query->get_result();
$thread = $result->fetch_assoc();

if (!$thread) {
    die("Thread not found.");
}

// Fetch posts (replies)
$postQuery = $db->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.forum_id = ? ORDER BY created_at ASC");
$postQuery->bind_param("i", $threadId);
$postQuery->execute();
$posts = $postQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Add Content Security Policy (CSP) header
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thread['title']); ?> - Forum</title>
    
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        /* Lightbox Background */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            justify-content: center;
            align-items: center;
        }

        /* Lightbox Image */
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 5px;
        }

        /* Close Button */
        .close {
            position: absolute;
            top: 15px;
            right: 30px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }

        /* Style for clickable images */
        .clickable-image {
            max-width: 150px;
            cursor: pointer;
            transition: transform 0.2s;
            border-radius: 5px;
        }

        .clickable-image:hover {
            transform: scale(1.05);
        }

        .thread-container {
            margin: 20px auto;
            padding: 20px;
            max-width: 800px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        body.dark .thread-container {
            background-color: #333;
            color: #ddd;
        }

        .thread-container h2 {
            color: #007bff;
            margin-bottom: 10px;
        }

        .thread-container p {
            line-height: 1.6;
            color: #333;
        }

        body.dark .thread-container p {
            color: #ddd;
        }

        .thread-container .reply {
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #ddd;
        }

        .thread-container .reply img {
            max-width: 100px;
            margin-top: 10px;
        }

        .thread-container .reply strong {
            display: block;
            margin-top: 10px;
        }

        .thread-container .reply a {
            color: #007bff;
            text-decoration: none;
        }

        .thread-container .reply a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <div class="thread-container">
        <h2><?php echo htmlspecialchars($thread['title']); ?></h2>
        <p>Category: <?php echo htmlspecialchars($thread['category']); ?></p>
        <p>Posted by: <?php echo htmlspecialchars($thread['username']); ?></p>

        <!-- Display Thread Image -->
        <?php if (!empty($thread['image_path'])): ?>
            <img src="/public<?php echo htmlspecialchars($thread['image_path']); ?>" 
                 class="clickable-image" 
                 id="openLightboxThread" 
                 alt="Thread Image">
        <?php endif; ?>

        <hr>

        <h3>Replies</h3>

        <ul>
            <?php foreach ($posts as $post): ?>
                <li class="reply">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?> 
                    - <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                    <br>
                    <!-- Display Reply Image -->
                    <?php if (!empty($post['image_path'])): ?>
                        <img src="/public<?php echo htmlspecialchars($post['image_path']); ?>" 
                             class="clickable-image" 
                             data-lightbox-src="/public<?php echo htmlspecialchars($post['image_path']); ?>" 
                             alt="Reply Image">
                    <?php endif; ?>
                    <br>
                    <a href="vote?id=<?php echo $post['id']; ?>&type=up">Upvote</a> |
                    <a href="vote?id=<?php echo $post['id']; ?>&type=down">Downvote</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Reply Form -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <h3>Reply</h3>
            <form action="/forums/post_reply" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="forum_id" value="<?php echo $threadId; ?>">
                <textarea name="content" required></textarea>
                <input type="file" name="image">
                <button class="button" type="submit">Post Reply</button>
            </form>
        <?php endif; ?>
        <a class="button" href="/forums/forum">Back to Forums</a>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox">
        <span class="close">&times;</span>
        <img id="lightboxImg">
    </div>

    <script>
        var lightbox = document.getElementById("lightbox");
        var lightboxImg = document.getElementById("lightboxImg");
        var threadImage = document.getElementById("openLightboxThread");

        // Function to open the lightbox
        function openLightbox(src) {
            lightbox.style.display = "flex";
            lightboxImg.src = src;
        }

        // Open lightbox for thread image
        if (threadImage) {
            threadImage.onclick = function() {
                openLightbox(this.src);
            };
        }

        // Open lightbox for reply images
        document.querySelectorAll(".clickable-image[data-lightbox-src]").forEach(function(img) {
            img.onclick = function() {
                openLightbox(this.getAttribute("data-lightbox-src"));
            };
        });

        // Close the lightbox when clicking the 'X'
        document.querySelector(".close").onclick = function() {
            lightbox.style.display = "none";
        };

        // Close when clicking outside the image
        lightbox.onclick = function(event) {
            if (event.target === lightbox) {
                lightbox.style.display = "none";
            }
        };
    </script>
    <br><br><br><br><br><br><br><br>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>