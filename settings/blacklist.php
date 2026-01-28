<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header

// Fetch user blacklist
$user_id = $_SESSION['user_id'] ?? 0;
$blacklist = [];

if ($user_id) {
    $query = $db->prepare("SELECT tag FROM user_blacklist WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        $blacklist[] = $row['tag'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blacklist - FluffFox</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    
</head>
<body>
    
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>
    <script>
        // helper: locate CSRF token value emitted by csrf_input()
        function getCsrfToken() {
            // common hidden input names used by many CSRF helpers
            const selectors = [
                'input[name="csrf_token"]',
                'input[name="csrf"]',
                'input[name="_csrf"]',
                'input[name="_token"]'
            ];
            for (const sel of selectors) {
                const el = document.querySelector(sel);
                if (el && el.value) return el.value;
            }
            // fallback: any hidden input with 'csrf' in the name
            const any = Array.from(document.querySelectorAll('input[type="hidden"]')).find(i => /csrf/i.test(i.name || ''));
            if (any && any.value) return any.value;
            console.warn('CSRF token not found in DOM. AJAX POSTs may fail validation.');
            return null;
        }

        function addTag() {
            const tag = document.getElementById('blacklist-input').value.trim();
            if (tag === "") {
                alert("Error: Tag cannot be empty.");
                return;
            }

            const formData = new URLSearchParams();
            formData.append("action", "add");
            formData.append("tag", tag);

            const token = getCsrfToken();
            if (token) formData.append("csrf_token", token);

            console.log("Sending data:", formData.toString());

            fetch("/settings/blacklist_settings", {
                method: "POST",
                headers: { 
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: formData.toString()
            })
            .then(response => response.text())
            .then(data => {
                console.log("Raw server response:", data);
                try {
                    const json = JSON.parse(data);
                    console.log("Parsed server response:", json);
                    if (json.success) {
                        alert(json.success);
                        loadBlacklist();
                    } else {
                        alert("Error: " + (json.error || "Unknown error."));
                    }
                } catch (error) {
                    console.error("Error parsing JSON:", error, "Response text:", data);
                }
            })
            .catch(error => console.error('Fetch error:', error));
        }

        function loadBlacklist() {
            fetch("/settings/get_blacklist", {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    const list = document.getElementById("blacklisted-tags");
                    list.innerHTML = "";
                    if (!Array.isArray(data) || data.length === 0) {
                        list.innerHTML = "<li>No tags blacklisted yet.</li>";
                    } else {
                        data.forEach(tag => {
                            // escape tag for insertion
                            const safeTag = String(tag).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                            list.innerHTML += `<li>${safeTag} <button class="button" onclick="removeTag('${safeTag}')">Remove</button></li>`;
                        });
                    }
                })
                .catch(error => console.error("Error loading blacklist:", error));
        }

        document.addEventListener("DOMContentLoaded", function () {
            loadBlacklist();  // Load blacklist when page is loaded
        });

        function removeTag(tag) {
            const formData = new FormData();
            formData.append("action", "remove");
            formData.append("tag", tag);

            const token = getCsrfToken();
            if (token) formData.append("csrf_token", token);

            fetch("/settings/blacklist_settings", {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: formData
            })
            .then(response => {
                if (!response.ok) return response.text().then(t => { throw new Error('Server error: ' + response.status + ' ' + t); });
                return response.json();
            })
            .then(data => {
                console.log("Remove response:", data);
                if (data.success) {
                    alert(data.success);
                    loadBlacklist(); // Refresh the list after removing the tag
                } else {
                    alert("Error: " + (data.error || "Unknown error."));
                }
            })
            .catch(error => {
                console.error("Error removing tag:", error);
                alert("Error removing tag: " + error.message);
            });
        }
    </script>
</head>
<body>
    <h2>Blacklist Tags</h2>
    <p>Enter tags separated by commas to filter out content.</p>
    <input type="text" id="blacklist-input" placeholder="e.g., gore, NSFW, violence">
    <?php echo csrf_input(); ?>
    <button class="button" onclick="addTag()">Add Tag</button>

    <h3>Your Blacklisted Tags:</h3>
    <ul id="blacklisted-tags"></ul>

    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>
