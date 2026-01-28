<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

// Redirect to clean URL if needed
if (isset($_GET['id']) && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
    $id = intval($_GET['id']);
    $cleanUrl = "/posts/$id/edit";
    header("Location: $cleanUrl", true, 301);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid post.";
    exit;
}

$userId = $_SESSION['user_id'];
$imageId = intval($_GET['id']);

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch post info
$fetchQuery = $db->prepare("SELECT display_name, uploaded_by, file_name FROM uploads WHERE id = ?");
$fetchQuery->bind_param("i", $imageId);
$fetchQuery->execute();
$result = $fetchQuery->get_result();

if ($result->num_rows === 0) {
    echo "Unauthorized or post not found.";
    exit;
}

$post = $result->fetch_assoc();
$imagePath = $post['file_name'];
$isOwner = ($post['uploaded_by'] == $userId);

// Fetch current tags for this upload
$tagQuery = $db->prepare("SELECT t.tag_name FROM tags t JOIN upload_tags ut ON t.tag_id = ut.tag_id WHERE ut.upload_id = ?");
$tagQuery->bind_param("i", $imageId);
$tagQuery->execute();
$tagResult = $tagQuery->get_result();
$currentTags = [];
while ($row = $tagResult->fetch_assoc()) {
    $currentTags[] = strtolower($row['tag_name']);
}
$tagQuery->close();

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMessage = "Invalid CSRF token.";
    } else {
        $newTags = trim($_POST['tags']);
        $newDisplayName = $isOwner ? trim($_POST['display_name']) : $post['display_name'];

        // Validate display name if owner
        if ($isOwner && $newDisplayName === '') {
            $errorMessage = "Display name cannot be empty.";
        } else {
            // Normalize and deduplicate tags
            $oldTagArray = array_unique(array_map('strtolower', $currentTags));
            $newTagArray = array_unique(array_filter(array_map('strtolower', array_map('trim', explode(',', $newTags)))));

            // Tags to add and remove
            $tagsToAdd = array_diff($newTagArray, $oldTagArray);
            $tagsToRemove = array_diff($oldTagArray, $newTagArray);

            // Remove all old tag associations for this upload
            $deleteUploadTags = $db->prepare("DELETE FROM upload_tags WHERE upload_id = ?");
            $deleteUploadTags->bind_param("i", $imageId);
            $deleteUploadTags->execute();
            $deleteUploadTags->close();

            // Decrement post_count for tags that were removed
            foreach ($tagsToRemove as $oldTag) {
                $decrementTag = $db->prepare("UPDATE tags SET post_count = GREATEST(post_count - 1, 0) WHERE LOWER(tag_name) = ?");
                $decrementTag->bind_param("s", $oldTag);
                $decrementTag->execute();
                $decrementTag->close();
            }

            // Add new tags and associations
            foreach ($newTagArray as $tag) {
                if ($tag === '') continue;

                // Check if tag exists
                $checkTag = $db->prepare("SELECT tag_id FROM tags WHERE LOWER(tag_name) = ?");
                $checkTag->bind_param("s", $tag);
                $checkTag->execute();
                $checkTag->store_result();

                if ($checkTag->num_rows > 0) {
                    $checkTag->bind_result($tag_id);
                    $checkTag->fetch();

                    // Only increment post_count if this tag is newly added
                    if (in_array($tag, $tagsToAdd)) {
                        $updateTag = $db->prepare("UPDATE tags SET post_count = post_count + 1, updated_at = NOW() WHERE tag_id = ?");
                        $updateTag->bind_param("i", $tag_id);
                        $updateTag->execute();
                        $updateTag->close();
                    }
                } else {
                    // Insert new tag
                    $insertTag = $db->prepare("INSERT INTO tags (tag_name, post_count, created_at, updated_at, is_locked) VALUES (?, 1, NOW(), NOW(), 0)");
                    $insertTag->bind_param("s", $tag);
                    $insertTag->execute();
                    $tag_id = $insertTag->insert_id;
                    $insertTag->close();
                }
                $checkTag->close();

                // Insert into upload_tags
                $insertUploadTag = $db->prepare("INSERT INTO upload_tags (upload_id, tag_id) VALUES (?, ?)");
                $insertUploadTag->bind_param("ii", $imageId, $tag_id);
                $insertUploadTag->execute();
                $insertUploadTag->close();
            }

            // Update display name if owner
            if ($isOwner) {
                $updateQuery = $db->prepare("UPDATE uploads SET display_name = ? WHERE id = ?");
                $updateQuery->bind_param("si", $newDisplayName, $imageId);
                $updateQuery->execute();
                $updateQuery->close();
            }

            // Update tag string in uploads table
            $tagStringForUpload = implode(' ', $newTagArray);
            $updateTagString = $db->prepare("UPDATE uploads SET tag_string = ? WHERE id = ?");
            $updateTagString->bind_param("si", $tagStringForUpload, $imageId);
            $updateTagString->execute();
            $updateTagString->close();

            header("Location: /posts/$imageId/");
            exit;
        }
    }
}
include_once('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/public/css/styles.css">

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tagInput = document.getElementById("tagInput");
    const tagContainer = document.getElementById("tagContainer");
    const hiddenTagField = document.getElementById("tags");

    let tags = hiddenTagField.value ? hiddenTagField.value.split(",").map(tag => tag.trim().toLowerCase()) : [];

    function addTag(tag) {
        tag = tag.replace(/\s+/g, '_').toLowerCase();
        if (tag && !tags.includes(tag)) {
            tags.push(tag);
            updateTags();
        }
    }

    function removeTag(tag) {
        tags = tags.filter(t => t !== tag);
        updateTags();
    }

    function updateTags() {
        tagContainer.innerHTML = "";
        tags.forEach(tag => {
            let tagElement = document.createElement("span");
            tagElement.classList.add("tag");
            tagElement.textContent = tag;
            tagElement.onclick = () => removeTag(tag);
            tagContainer.appendChild(tagElement);
        });

        hiddenTagField.value = tags.join(", ");
    }

    // Populate tags from database on page load
    updateTags();

    tagInput.addEventListener("keydown", function(event) {
        if (event.key === "Enter" && tagInput.value.trim() !== "") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });
});
</script>
<style>
.edit-form {
  background: #fff;
  padding: 20px;
  border: 1px solid #d1d7e0;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(13, 110, 253, 0.1);
  margin: 20px 20px 80px;
}

body.dark .edit-form {
  background: #1e1e1e;
  border: 1px solid #444;
}

label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #333;
}

body.dark label {
  color: #ccc;
}

input[type="text"], 
input[type="email"], 
textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid #ccc;
  border-radius: 6px;
  font-size: 1rem;
  margin-bottom: 20px;
  transition: border-color 0.3s ease;
}

input[type="text"]:focus, 
textarea:focus {
  outline: none;
  color: black;
}

.tagarea {
  margin-bottom: 10px;
}

#tagContainer {
  margin-bottom: 20px;
}

.tag {
  display: inline-block;
  background-color: #0d6efd;
  color: white;
  padding: 5px 12px;
  margin: 4px 6px 4px 0;
  border-radius: 20px;
  font-weight: 600;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.3s ease;
}

.tag:hover {
  background-color: #0843c5;
}

.edit-image-container img {
  max-width: 10%;
  max-height: 10%;
  height: auto;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border: 1px solid #ccc;
}
</style>
</head>
<body>
<nav>
<?php include_once '../includes/nav.php'; ?>
</nav>
<?php include_once '../includes/site-notice.php'; ?>

<h1>Edit Image</h1>
<?php if ($errorMessage): ?>
    <p style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></p>
<?php endif; ?>
<p>Here you can edit the tags and other information here. If you are the owner, you can also edit the display name. If you'd like to request an option to edit the image, email me at <a href="mailto:nixtenkame@gmail.com">nixtenkame@gmail.com</a>.</p>
<form class="edit-form" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <?php if ($isOwner): ?>
        <label for="display_name">Edit Display Name:</label>
        <input type="text" name="display_name" id="display_name" value="<?php echo htmlspecialchars($post['display_name']); ?>">
        <br>
    <?php endif; ?>

    <label for="tags">Edit Tags:</label>
    <input class="tagarea" type="text" id="tagInput" placeholder="Type a tag and press Enter">
    <div id="tagContainer"></div>
    <input type="hidden" name="tags" id="tags" value="<?php echo htmlspecialchars(implode(', ', $currentTags)); ?>">
    <div class="edit-image-container">
        <img src="https://nixten.ddns.net:9001/data<?php echo htmlspecialchars($imagePath); ?>" alt="Image for post <?php echo $imageId; ?>" />
    </div>
    <br>
    <button class="button" type="submit">Update</button>
    <a class="button" onclick="window.history.back()">Changed your mind?</a>
</form>

<?php include('../includes/version.php'); ?>
<footer>
<p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
</footer>
</body>
</html>