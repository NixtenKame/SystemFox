<?php

define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../includes/header.php'); // Include common header

// Set your admin user ID here
$admin_id = 3;

// Fetch latest version update
$latest = $db->query("SELECT id, version, release_date, release_time, notes FROM version_updates ORDER BY id DESC LIMIT 1")->fetch_assoc();
$latest_id = $latest ? $latest['id'] : 0;
$latest_version = $latest ? $latest['version'] : '';
$latest_date = $latest ? $latest['release_date'] : '';
$latest_time = $latest ? $latest['release_time'] : '';
$latest_notes = $latest ? $latest['notes'] : '';

// Handle delete
if (
    isset($_POST['delete_version'], $_POST['delete_id'], $_SESSION['user_id']) &&
    $_SESSION['user_id'] == $admin_id
) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM version_updates WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle update
if (
    isset($_POST['update_version'], $_POST['update_id'], $_SESSION['user_id']) &&
    $_SESSION['user_id'] == $admin_id
) {
    $update_id = intval($_POST['update_id']);
    $version = trim($_POST['version']);
    $release_date = $_POST['release_date'];
    $release_time = $_POST['release_time'];
    $notes = trim($_POST['notes']);
    $stmt = $db->prepare("UPDATE version_updates SET version=?, release_date=?, release_time=?, notes=? WHERE id=?");
    $stmt->bind_param("ssssi", $version, $release_date, $release_time, $notes, $update_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle edit (show form)
$edit_row = null;
if (
    isset($_POST['edit_version'], $_POST['edit_id'], $_SESSION['user_id']) &&
    $_SESSION['user_id'] == $admin_id
) {
    $edit_id = intval($_POST['edit_id']);
    $edit_result = $db->query("SELECT * FROM version_updates WHERE id = $edit_id");
    $edit_row = $edit_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta property="og:title" content="FluffFox-Version">
    <meta property="og:description" content="<?php
        if ($latest_version) {
            echo 'Latest Version: ' . htmlspecialchars($latest_version);
        }
    ?>">
    <meta property="og:image" content="https://nixten.ddns.net/public/images/favicon.ico">
    <meta property="og:url" content="https://nixten.ddns.net/assets/docs/version">
    <meta property="og:type" content="website">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Version - FluffFox</title>
    <link rel="stylesheet" href="../../public/css/styles.css">
    <style>
        .version-block {
            background: #005999;
            padding: 20px;
            margin: 30px 0;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0002;
        }
        body.dark .version-block {
            background: #333;
        }
        .version-block h2 {
            color: #ff6f61;
            margin-bottom: 10px;
        }
        .version-block div {
            color: #f0f0f0;
            font-size: 1.1rem;
            margin-bottom: 10px;
            white-space: pre-line;
        }
        .version-block .author {
            text-align: right;
            color: #aaa;
            font-size: 0.95rem;
        }
        .version-actions {
            margin-top: 10px;
        }
        .version-actions form, .version-actions button {
            display: inline;
        }
        .version-actions button {
            margin-right: 8px;
            padding: 4px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .version-actions .delete-btn {
            color: #fff;
            background: #d9534f;
        }
        .version-actions .edit-btn {
            color: #fff;
            background: #0275d8;
        }
        .edit-version-form input, .edit-version-form textarea {
            width: 100%;
            margin-bottom: 8px;
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #555;
            background: #111;
            color: #fff;
        }
        .edit-version-form label {
            color: #ff6f61;
            font-weight: bold;
        }
        .edit-version-form button {
            background: #5cb85c;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        /* Popup styles */
        #deletePopup {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        #deletePopup.show {
            display: flex;
        }
        #deletePopup .version-block {
            max-width: 400px;
            margin: auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav>
        <?php include_once '../../includes/nav.php'; ?>
    </nav>
        <?php include_once '../../includes/site-notice.php'; ?>
    <main>
        <p>These release notes can be viewed back by the general public to look how the site changed and developed during its stages of early development :3</p>
        <h1><span class="highlight">Version</span></h1>
        <p><strong><span class="highlight">Version:</span></strong> <span class="highlight"><?php echo htmlspecialchars($latest_version); ?></span></p>
        <p><strong><span class="highlight">Release Date:</span></strong> <span class="highlight"><?php echo htmlspecialchars($latest_date); ?></span></p>
        <p><strong><span class="highlight">Time:</span></strong> <span class="highlight"><?php echo htmlspecialchars($latest_time); ?></span></p>
        <p><strong><span class="highlight">GitHub repo:</span></strong> <span class="highlight"><a href="https://github.com/NixtenKame/SystemFox">SystemFox<?php echo htmlspecialchars($latest_version); ?></a></span></p>
        <p><strong><span class="highlight">Running:</span></strong> <span class="highlight"><a href="https://github.com/NixtenKame/SystemFox/archive/refs/heads/main.zip">SystemFox<?php echo htmlspecialchars($latest_version); ?></a></span></p>
        <h1><span class="highlight">Version History</span></h1>
        <?php
        // Show edit form if needed
        if ($edit_row) {
            ?>
            <div class="version-block" style="background:#333;">
                <form method="POST" class="edit-version-form">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="update_id" value="<?php echo $edit_row['id']; ?>">
                    <label>Version:
                        <input type="text" name="version" value="<?php echo htmlspecialchars($edit_row['version']); ?>" required>
                    </label>
                    <label>Date:
                        <input type="date" name="release_date" value="<?php echo htmlspecialchars($edit_row['release_date']); ?>" required>
                    </label>
                    <label>Time:
                        <input type="time" name="release_time" value="<?php echo htmlspecialchars(substr($edit_row['release_time'],0,5)); ?>" required>
                    </label>
                    <label>Notes:<br>
                        <textarea name="notes" rows="6" required><?php echo htmlspecialchars($edit_row['notes']); ?></textarea>
                    </label>
                    <button type="submit" name="update_version">Save</button>
                </form>
            </div>
            <?php
        }

        // Show all version updates except the latest (to avoid duplicate)
        $result = $db->query("SELECT id, version, release_date, release_time, notes FROM version_updates WHERE id != $latest_id ORDER BY id DESC");
        // Show latest version at the top
        if ($latest) {
            echo '<div class="version-block">';
            echo '<h2>Version ' . htmlspecialchars($latest['version']) .
                ' <span style="font-size:1rem;color:#fff;">(' . date("n/j/y", strtotime($latest['release_date'])) .
                ' @ ' . date("g:i A", strtotime($latest['release_time'])) . ' (GMT-6))</span></h2>';
            echo '<div>' . nl2br(htmlspecialchars($latest['notes'])) . '</div>';
            echo '<div class="author">- Nixten Leo Kame</div>';
            // Show edit/delete only if logged in as admin
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $admin_id) {
                echo '<div class="version-actions">';
                echo '<form method="POST" style="display:inline;">
                    <?php echo csrf_input(); ?>
                        <input type="hidden" name="edit_id" value="' . $latest['id'] . '">
                        <button type="submit" name="edit_version" class="edit-btn">Edit</button>
                      </form>';
                // Custom popup trigger for delete
                echo '<button type="button" class="delete-btn" onclick="openDeletePopup(' . $latest['id'] . ')">Delete</button>';
                echo '</div>';
            }
            echo '</div>';
        }
        // Show the rest of the history
        while ($row = $result->fetch_assoc()) {
            echo '<div class="version-block">';
            echo '<h2>Version ' . htmlspecialchars($row['version']) .
                ' <span style="font-size:1rem;color:#fff;">(' . date("n/j/y", strtotime($row['release_date'])) .
                ' @ ' . date("g:i A", strtotime($row['release_time'])) . ' (GMT-6))</span></h2>';
            echo '<div>' . nl2br(htmlspecialchars($row['notes'])) . '</div>';
            echo '<div class="author">- Nixten Leo Kame</div>';
            // Show edit/delete only if logged in as admin
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $admin_id) {
                echo '<div class="version-actions">';
                echo '<form method="POST" style="display:inline;">
                    <?php echo csrf_input(); ?>
                        <input type="hidden" name="edit_id" value="' . $row['id'] . '">
                        <button type="submit" name="edit_version" class="edit-btn">Edit</button>
                      </form>';
                // Custom popup trigger for delete
                echo '<button type="button" class="delete-btn" onclick="openDeletePopup(' . $row['id'] . ')">Delete</button>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>

        <!-- Delete Confirmation Popup -->
        <div id="deletePopup">
            <div class="version-block">
                <h2 style="color:#ff6f61;">Confirm Delete</h2>
                <p style="color:#f0f0f0;">Are you sure you want to delete this version update?</p>
                <form id="deleteForm" method="POST" style="margin-top:20px;">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="delete_id" id="popupDeleteId" value="">
                    <button type="submit" name="delete_version" class="delete-btn" style="background:#d9534f; color:#fff; margin-right:10px;">Delete</button>
                    <button type="button" onclick="closeDeletePopup()" style="background:#444; color:#fff; border:none; padding:4px 10px; border-radius:4px;">Cancel</button>
                </form>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($latest_version); ?></a></p>
    </footer>
    <script>
    function openDeletePopup(id) {
        document.getElementById('popupDeleteId').value = id;
        document.getElementById('deletePopup').classList.add('show');
    }
    function closeDeletePopup() {
        document.getElementById('deletePopup').classList.remove('show');
    }
    // Optional: close popup on background click
    document.getElementById('deletePopup').addEventListener('click', function(e) {
        if (e.target === this) closeDeletePopup();
    });
    </script>
</body>
</html>