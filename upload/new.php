<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../../logs/upload_errors.log');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: /error_redirect?message=" . urlencode("You must be logged in to upload. Redirecting to login..."));
    exit();
}

$userId = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $tags = $_POST['tags'] ?? '';
        $displayName = $_POST['display_name'] ?? '';
        $source = $_POST['source'] ?? '';
        $description = $_POST['description'] ?? '';
        $file = $_FILES['file'];

        if (!$file['tmp_name']) {
            throw new Exception("No file uploaded.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedTypes = [
            'image/jpeg','image/png','image/gif','image/webp','image/bmp','image/tiff','image/x-icon','image/svg+xml',
            'audio/mpeg','audio/ogg','audio/wav','audio/webm',
            'video/mp4','video/webm','video/ogg'
        ];

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Invalid file type.");
        }

        $category = $_POST['category'] ?? 'uncategorized';
        // Update your upload directory path as needed
        $uploadDir = "S:/FluffFox-Data/data/";
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception("Failed to create upload directory.");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!$extension) {
            throw new Exception("File must have a valid extension.");
        }

        // --- NEW: MD5 hash filename and nested folder creation ---
        $md5Hash = md5_file($file['tmp_name']);
        $folder1 = substr($md5Hash, 0, 2);
        $folder2 = substr($md5Hash, 2, 2);

        $targetDir = $uploadDir . $folder1 . '/' . $folder2 . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Include folders in filename for database storage
        $relativeFilePath = '/' . $folder1 . '/' . $folder2 . '/' . $md5Hash . '.' . $extension;
        $uploadFilePath = $targetDir . $md5Hash . '.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            throw new Exception("File upload failed.");
        }

        $fileSize = filesize($uploadFilePath);

        $imageWidth = 0;
        $imageHeight = 0;
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($uploadFilePath);
            if ($imageInfo) {
                $imageWidth = $imageInfo[0];
                $imageHeight = $imageInfo[1];
            }
        }

        $userIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // --- Tags processing (unchanged) ---
        $tagArray = array_filter(array_map('trim', explode(',', $tags)));
        $tagArray = array_unique(array_map('strtolower', array_map(function($t) {
            return str_replace(' ', '_', $t);
        }, $tagArray)));

        $tagString = implode(' ', $tagArray);
        $tagCount = count($tagArray);

        $rating = 's';
        $uploadDate = date('Y-m-d H:i:s');

        // --- Insert into uploads table ---
        $stmt = $db->prepare("INSERT INTO uploads (
            file_name, display_name, category, uploaded_by, upload_date, created_at, updated_at,
            up_score, down_score, score,
            source, rating,
            is_note_locked, is_rating_locked, is_status_locked, is_pending, is_flagged, is_deleted,
            uploader_ip_addr,
            fav_string, pool_string,
            tag_string, tag_count, tag_count_general, tag_count_artist, tag_count_character, tag_count_copyright,
            file_ext, file_size, image_width, image_height,
            parent_id, has_children, last_commented_at, has_active_children, bit_flags,
            tag_count_meta, locked_tags, tag_count_species, tag_count_invalid, description,
            comment_count, change_seq, tag_count_lore, bg_color, duration,
            is_comment_disabled, is_comment_locked
        ) VALUES (
            ?, ?, ?, ?, ?, NOW(), NOW(),
            0, 0, 0,
            ?, ?,
            0, 0, 0, 0, 0, 0,
            ?,
            '', '',
            ?, ?, 0, 0, 0, 0,
            ?, ?, ?, ?,
            NULL, 0, NULL, 0, 0,
            0, NULL, 0, 0, ?,
            0, 0, 0, NULL, NULL,
            0, 0
        )");

        $stmt->bind_param(
            "sssisssssisiiis",
            $relativeFilePath, // Now includes /e5/77/
            $displayName,
            $category,
            $userId,
            $uploadDate,
            $source,
            $rating,
            $userIP,
            $tagString,
            $tagCount,
            $extension,
            $fileSize,
            $imageWidth,
            $imageHeight,
            $description
        );

        if (!$stmt->execute()) {
            throw new Exception("Database error (uploads): " . $stmt->error);
        }

        $uploadId = $stmt->insert_id;
        $stmt->close();

        // Update post count: +1
        $updateCount = $db->prepare("UPDATE post_count SET total_posts = total_posts + 1 WHERE id = 1");
        $updateCount->execute();
        $updateCount->close();

        // --- Tags table processing (unchanged) ---
        foreach ($tagArray as $tag) {
            if ($tag === '') continue;

            if (strpos($tag, ',') !== false) {
                throw new Exception("Tag '$tag' contains a comma, which is not allowed.");
            }

            $checkTag = $db->prepare("SELECT tag_id FROM tags WHERE LOWER(tag_name) = ?");
            $lowerTag = strtolower($tag);
            $checkTag->bind_param("s", $lowerTag);
            $checkTag->execute();
            $checkTag->store_result();

            if ($checkTag->num_rows > 0) {
                $checkTag->bind_result($tag_id);
                $checkTag->fetch();

                $updateTag = $db->prepare("UPDATE tags SET post_count = post_count + 1, updated_at = NOW() WHERE tag_id = ?");
                $updateTag->bind_param("i", $tag_id);
                $updateTag->execute();
                $updateTag->close();
            } else {
                $insertTag = $db->prepare("INSERT INTO tags (tag_name, post_count, created_at, updated_at, is_locked) VALUES (?, 1, NOW(), NOW(), 0)");
                $insertTag->bind_param("s", $tag);
                $insertTag->execute();
                $tag_id = $insertTag->insert_id;
                $insertTag->close();
            }

            $checkTag->close();

            $insertUploadTag = $db->prepare("INSERT INTO upload_tags (upload_id, tag_id) VALUES (?, ?)");
            $insertUploadTag->bind_param("ii", $uploadId, $tag_id);
            $insertUploadTag->execute();
            $insertUploadTag->close();
        }

        $message = "<p style='color:green;'>Upload successful! Filename: " . htmlspecialchars($relativeFilePath) . "</p>";

    } catch (Exception $e) {
        error_log("Upload error: " . $e->getMessage());
        $message = "<p style='color:red;'>Upload error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = "<p style='color:red;'>Invalid request or no file uploaded.</p>";
}

echo $message;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    
<style>
.upload-container {
  display: flex;
  gap: 20px;
  margin-top: 20px;
  align-items: flex-start;
}

.upload-form {
  display: flex;
  flex-direction: column;
  width: 50%;
}

.upload-field {
  background: #fff;
  color: #333;
  padding: 20px;
  border-radius: 10px;
  border: 1px solid #ccc;
  margin-bottom: 20px;
}

body.dark .upload-field {
  background: #333;
  color: #ddd;
  border: 1px solid #555;
}

.preview-image {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  border: 2px solid;
  width: 50%;
  height: 50%;
}

.preview-image img {
  max-width: 100%;
  border: 1px solid #ccc;
  border-radius: 10px;
}

.tag {
  display: inline-block;
  background-color: #007bff;
  color: white;
  padding: 5px 10px;
  margin: 5px;
  border-radius: 15px;
  cursor: pointer;
}

.tag:hover {
  background-color: red;
}

#previewImage {
  max-width: 300px;
  display: none;
  margin-top: 10px;
  border: 1px solid #ccc;
  border-radius: 10px;
}

.modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background: rgba(0,0,0,0.6);
  color: black;
}

.modal-content {
  background: #fff;
  margin: 10% auto;
  padding: 30px;
  border-radius: 10px;
  width: 90%;
  max-width: 500px;
  text-align: center;
  position: relative;
}

body.dark .modal-content {
  background: #333;
  color: #ddd;
}

.close {
  position: absolute;
  right: 15px;
  top: 10px;
  font-size: 28px;
  font-weight: bold;
  color: #888;
  cursor: pointer;
}

.close:hover {
  color: #f00;
}
</style>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const noticeModal = document.getElementById("noticeModal");
  const sfwModal = document.getElementById("sfwModal");
  const acknowledgeNotice = document.getElementById("acknowledgeNotice");
  const acknowledgeSfw = document.getElementById("acknowledgeSfw");
  const closeNotice = document.getElementById("closeNotice");
  const closeSfw = document.getElementById("closeSfw");

  if (noticeModal) noticeModal.style.display = "block";

  const closeNoticeHandler = () => {
    if (noticeModal) noticeModal.style.display = "none";
    if (sfwModal) sfwModal.style.display = "block";
  };

  if (acknowledgeNotice) acknowledgeNotice.onclick = closeNoticeHandler;
  if (closeNotice) closeNotice.onclick = closeNoticeHandler;

  if (acknowledgeSfw && closeSfw && sfwModal) {
    const closeSfwHandler = () => {
      sfwModal.style.display = "none";
    };
    acknowledgeSfw.onclick = closeSfw.onclick = closeSfwHandler;
  }

  const tagInput = document.getElementById("tagInput");
  const tagContainer = document.getElementById("tagContainer");
  const hiddenTagField = document.getElementById("tags");
  const fileInput = document.getElementById("file");
  const previewImage = document.getElementById("previewImage");
  const uploadForm = document.getElementById("uploadForm");
  const progressBar = document.getElementById("progressBar");
  const progressText = document.getElementById("progressText");
  const uploadStatus = document.getElementById("uploadStatus");
  const clearButton = document.querySelector("button[type='button']");

  let tags = [];

  if (tagInput) {
    tagInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter" && tagInput.value.trim() !== "") {
        event.preventDefault();
        addTag(tagInput.value.trim());
        tagInput.value = "";
      }
    });
  }

  function addTag(tag) {
    tag = tag.replace(/\s+/g, "_").toLowerCase();
    if (tag === "" || tags.includes(tag)) return;
    if (tag.includes(",")) {
      alert("Tags cannot contain commas.");
      return;
    }
    tags.push(tag);
    updateTags();
  }

  function removeTag(tag) {
    tags = tags.filter((t) => t !== tag);
    updateTags();
  }

  function updateTags() {
    if (!tagContainer) return;
    tagContainer.innerHTML = "";
    tags.forEach((tag) => {
      const tagElement = document.createElement("span");
      tagElement.classList.add("tag");
      tagElement.textContent = tag;
      tagElement.title = "Click to remove tag";
      tagElement.style.cursor = "pointer";
      tagElement.onclick = () => removeTag(tag);
      tagContainer.appendChild(tagElement);
    });
    if (hiddenTagField) hiddenTagField.value = tags.join(", ");
  }

  if (fileInput) {
    fileInput.addEventListener("change", () => {
      const file = fileInput.files[0];
      if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = (e) => {
          if (previewImage) {
            previewImage.src = e.target.result;
            previewImage.style.display = "block";
          }
        };
        reader.readAsDataURL(file);
      } else if (previewImage) {
        previewImage.src = "";
        previewImage.style.display = "none";
      }
    });
  }

  if (uploadForm) {
    uploadForm.addEventListener("submit", (e) => {
      e.preventDefault();

      if (tags.length === 0) {
        alert("Please add at least one tag.");
        return;
      }

      if (!fileInput || !fileInput.files.length) {
        alert("Please select a file to upload.");
        return;
      }

      uploadFile();
    });
  }

  if (clearButton) {
    clearButton.addEventListener("click", () => {
      if (fileInput) {
        fileInput.value = "";
      }
      if (previewImage) {
        previewImage.src = "";
        previewImage.style.display = "none";
      }
      tags = [];
      updateTags();
      if (progressBar) progressBar.value = 0;
      if (progressText) progressText.innerText = "";
      if (uploadStatus) uploadStatus.innerHTML = "";
    });
  }

  function uploadFile() {
    if (!uploadForm) return;

    const formData = new FormData(uploadForm);
    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener("progress", (event) => {
      if (event.lengthComputable) {
        const percentComplete = (event.loaded / event.total) * 100;
        if (progressBar) progressBar.value = percentComplete;
        if (progressText) progressText.innerText = Math.round(percentComplete) + "%";
      }
    });

xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
        console.log("RAW response:", xhr.responseText);
        if (xhr.status === 200) {
            document.getElementById("progressText").innerText = "Upload Complete!";
        } else {
            document.getElementById("uploadStatus").innerHTML = "<span style='color:red;'>Error occurred during upload.</span>";
        }
    }
};
        xhr.open("POST", "/upload/new", true);
        xhr.send(formData);
    }
});
</script>

    <div id="noticeModal" class="modal">
      <div class="modal-content">
        <span class="close" id="closeNotice">&times;</span>
        <h1><strong>NOTE: Only SFW content is allowed. Any NSFW content will be removed. <a href="/assets/docs/content">Click Here</a> to learn more</strong></span></h1>
        <h3>By clicking upload you agree that you are willing to share your IP Address, Geo Location and user ID.<br>
        If you are okay with this then continue. If you are not okay with this <b>LEAVE THE SITE IMMEDIATELY.</b></h3>
        <button id="acknowledgeNotice" class="button">I Understand</button>
      </div>
    </div>

    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

<main>
  <div class="upload-container">
    <form class="upload-form" id="uploadForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

      <div class="upload-field">
        <label for="file">Select File:</label>
        <input type="file" name="file" id="file" class="button" required><br><br>
      </div>

      <div class="upload-field">
        <label for="display_name">Display Name (Optional):</label>
        <input class="tagarea" type="text" name="display_name" id="display_name" placeholder="Enter a display name">
      </div>

      <div class="upload-field">
        <label for="category">Category:</label>
        <select name="category" id="category" required>
          <option value="sfw">SFW</option>
        </select><br><br>
      </div>

      <div class="upload-field">
        <label for="source">Source (Optional):</label>
        <br>
        <input class="tagarea" type="text" name="source" id="source" placeholder="Where is this from? (URL or text)">
        <br>
        <label for="description">Description (Optional):</label>
        <br>
        <textarea name="description" id="description" rows="3" placeholder="Add a description..."></textarea>
        <br>
        <label for="tagInput">Tags:</label>
        <input class="tagarea" type="text" id="tagInput" placeholder="Type a tag and press Enter" autocomplete="off">
        <div id="tagContainer"></div>
        <input type="hidden" name="tags" id="tags">
      </div>

      <br><br>
      <input type="submit" name="upload" value="Upload" class="button">
      <button type="button" class="button" onclick="window.location.reload();">Remove Attachment & Tags</button>

      <progress id="progressBar" value="0" max="100"></progress>
      <span id="progressText">0%</span>
      <p id="uploadStatus"></p>
    </form>

    <div class="preview-image">
      <img id="previewImage" src="" alt="File preview"><br>
    </div>
  </div>
  <br>
<br>
<br>
<br>
</main>

<?php include('../includes/version.php'); ?>
    <footer>
    <p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>