<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT id, username, email, bio, profile_picture, level, created_at, last_active, birthdate, custom_status, online_status, email_visibility, birthday_visibility, custom_profile_css FROM users WHERE id = ?");
if (!$stmt) {
    die('Query preparation failed.');
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('User not found.');
}

$user = $result->fetch_assoc();
$stmt->close();

$profilePicture = (!empty($user['profile_picture'])) 
    ? "../public/uploads/" . htmlspecialchars($user['profile_picture']) 
    : "../public/images/default-profile.png";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bio'])) {
    $newBio = trim($_POST['bio']);
    $updateBio = $db->prepare("UPDATE users SET bio = ? WHERE id = ?");
    if ($updateBio) {
        $updateBio->bind_param("si", $newBio, $user_id);
        if ($updateBio->execute()) {
            $user['bio'] = $newBio;
            $successMessage = "Bio updated successfully!";
        } else {
            $errorMessage = "Failed to update bio.";
        }
        $updateBio->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email_visibility'])) {
    $emailVisibility = isset($_POST['email_visibility']) ? 1 : 0;
    $updateVis = $db->prepare("UPDATE users SET email_visibility = ? WHERE id = ?");
    if ($updateVis) {
        $updateVis->bind_param("ii", $emailVisibility, $user_id);
        if ($updateVis->execute()) {
            $user['email_visibility'] = $emailVisibility;
            $successMessage = "Email visibility updated successfully!";
        } else {
            $errorMessage = "Failed to update email visibility.";
        }
        $updateVis->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_birthday_visibility'])) {
    $birthdayVisibility = isset($_POST['birthday_visibility']) ? 1 : 0;
    $updateVis = $db->prepare("UPDATE users SET birthday_visibility = ? WHERE id = ?");
    if ($updateVis) {
        $updateVis->bind_param("ii", $birthdayVisibility, $user_id);
        if ($updateVis->execute()) {
            $user['birthday_visibility'] = $birthdayVisibility;
            $successMessage = "Birthday visibility updated successfully!";
        } else {
            $errorMessage = "Failed to update birthday visibility.";
        }
        $updateVis->close();
    }
}

$totalItems = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    
    <style>
        .disabled-form {
            position: relative;
            opacity: 0.5;
        }
        .disabled-form::before {
            content: "Coming Soon";
            position: absolute;
            height: 50px;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.8);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 2.0em;
            color: red;
            text-align: center;
        }
        .disabled-form select,
        .disabled-form input {
            pointer-events: none;
        }

        #preview {
            display: block;
            width: 180px;
            height: 180px;
            margin: 16px auto 0;
            border-radius: 50%;
            object-fit: cover;
            background: #fafafa;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        @media (max-width: 768px) {
            #preview {
                width: 140px;
                height: 140px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <?php include_once '../includes/nav.php'; ?>
    </nav>

    <?php include_once '../includes/site-notice.php'; ?>

    <main>
        <div class="wrapper">
        <div class="left-items">
        <?php if (isset($successMessage)): ?>
            <p style="color: green;"><?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>
        <?php if (isset($errorMessage)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <div class="pfp-form">
            <h2>Upload Profile Picture</h2>
            <form id="profileForm" method="POST" action="upload_profile_picture" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <label for="profile_picture">Upload Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                <img id="preview" width="180" height="180" style="display:none; border-radius:50%; object-fit:cover; margin-top:16px;"/>
                <button class="button" type="submit" id="uploadBtn" style="display:none;">Upload</button>
            </form>
        </div>

        <div class="pfp-form">
            <h2>Warning: Profile Picture Caching</h2>
            <p>After uploading multiple profile pictures in one sitting, your browser may cache the old images causing the preview of your profile picture to not update immediately. To fix this, you can try hard refreshing the page (Ctrl + Shift + R or Cmd + Shift + R) or clearing your browser cache.</p>
        </div>

        <div class="profile-container">
            <h1>User profile:</h1>
            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            <div class="profile-picture">
                <img src="/<?php echo $profilePicture; ?>" alt="Profile Picture" style="width: 100px; height: 100px; border-radius: 50%;">
            </div>

            <div class="profile-details">
                <p>Email:
                    <?php if ($user['email_visibility']): ?>
                        <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></a>
                    <?php else: ?>
                        Hidden
                    <?php endif; ?>
                </p>

                <h3>Email Visibility</h3>
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="update_email_visibility" value="1">
                    <label>
                        <input type="checkbox" name="email_visibility" <?php echo $user['email_visibility'] ? 'checked' : ''; ?>>
                        Show email on profile
                    </label>
                    <br><br>
                    <input class="button" type="submit" value="Update Visibility">
                </form>

                <h3>Birthday Visibility</h3>
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="update_birthday_visibility" value="1">
                    <label>
                        <input type="checkbox" name="birthday_visibility" <?php echo $user['birthday_visibility'] ? 'checked' : ''; ?>>
                        Show birthday on profile
                    </label>
                    <br><br>
                    <input class="button" type="submit" value="Update Visibility">
                </form>

                <h3>Edit Bio</h3>
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <textarea class="bio" name="bio" rows="4" cols="50"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea><br><br>
                    <input class="button" type="submit" value="Update Bio">
                </form>
            </div>

            <h3>Profile Information:</h3>
            <div class="profile-info">
                <p>ID: <?php echo htmlspecialchars($user['id']); ?></p>
                <p>Join Date: <?php echo htmlspecialchars($user['created_at']); ?></p>
                <p>Account Level: <?php echo htmlspecialchars($user['level'] ?: "No account type available"); ?></p>
                <p>Last Active: <?php echo htmlspecialchars($user['last_active']); ?></p>
                <p>Birthday: <?php echo htmlspecialchars($user['birthdate']); ?></p>
                <p>Age: <?php 
                if (!empty($user['birthdate'])) {
                    $birthdate = new DateTime($user['birthdate']);
                    $today = new DateTime();
                    $age = $today->diff($birthdate)->y;
                    echo htmlspecialchars($age, ENT_QUOTES, 'UTF-8');
                } else {
                    echo "Age not available";
                }
                ?></p>
            </div>
            <br>
            <a class="button" href="/you/favorites">View Favorites</a>
            <br><br>
            <a class="button" href="/you/">View profile as if</a>
            <br><br>
            <a class="button" href="/user/<?php echo htmlspecialchars($user['username']); ?>">View actual profile</a>
        </div>
        </div>
        <div class="right-items">
            <div class="edit-css-container">
                <h2>Edit Profile CSS</h2>
                <p>Notice: You DO have full control over what is displayed on your profile from the footer to the navigation bar using rules like display: none; WILL hide them if you are okay with this then go for it but realize that users viewing your profile might have a struggle with navigating or viewing the page.</p>
                <h3>I AM NOT RESPONSIBLE FOR ANY ERRORS OR ISSUES CAUSED BY BAD CSS CODE!</h3>
                <form method="POST" action="update_profile_css">
                    <?php echo csrf_input(); ?>
                    <textarea name="custom_profile_css" rows="10" cols="80" placeholder="Enter your custom CSS here..."><?php echo htmlspecialchars($user['custom_profile_css'] ?? ''); ?></textarea><br><br>
                    <input class="button" type="submit" value="Update Profile CSS">
                </form>
            </div>
        </div>
        </div>
    </main>

    <?php include('../includes/version.php'); ?>

    <footer>
        <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved.
            <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a>
        </p>
    </footer>

<script>
const input = document.getElementById('profile_picture');
const preview = document.getElementById('preview');
const uploadBtn = document.getElementById('uploadBtn');
const form = document.getElementById('profileForm');

console.log('Profile upload script loaded');

input.addEventListener('change', function(e) {
    const file = e.target.files[0];
    console.log('File selected:', file);
    
    if (!file) {
        console.log('No file selected');
        preview.style.display = 'none';
        uploadBtn.style.display = 'none';
        return;
    }
    
    if (!file.type.startsWith('image/')) {
        console.warn('File is not an image:', file.type);
        alert("Please select an image file.");
        preview.style.display = 'none';
        uploadBtn.style.display = 'none';
        return;
    }
    
    console.log('Reading file as data URL...');
    const reader = new FileReader();
    reader.onload = function(event) {
        console.log('FileReader loaded, displaying preview');
        preview.src = event.target.result;
        preview.style.display = 'block';
        uploadBtn.style.display = 'inline-block';
    };
    reader.onerror = function(err) {
        console.error('FileReader error:', err);
    };
    reader.readAsDataURL(file);
});

form.addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Form submit event triggered');
    console.log('Input element has file?', input.files.length > 0);
    
    if (input.files.length === 0) {
        alert('No file selected! Please select an image first.');
        return;
    }
    
    const formData = new FormData();
    // Manually append CSRF token and file to ensure they're added
    const csrfInput = form.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfInput ? csrfInput.value : '';
    console.log('CSRF token value:', csrfToken);
    
    // Manually append the file instead of using FormData(form)
    const file = input.files[0];
    console.log('File to upload:', file.name, 'Size:', file.size, 'Type:', file.type);
    formData.append('profile_picture', file);
    formData.append('csrf_token', csrfToken);
    console.log('File and CSRF appended to FormData');
    
    fetch('upload_profile_picture', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    }).then(r => {
        console.log('Response received, status:', r.status);
        const debugHeader = r.headers.get('X-Debug-Info');
        if (debugHeader) {
            try {
                console.log('X-Debug-Info:', JSON.parse(decodeURIComponent(debugHeader)));
            } catch (e) {
                console.log('X-Debug-Info (raw):', decodeURIComponent(debugHeader));
            }
        }
        return r.text();
    }).then(data => {
        console.log('Response text:', data);
        if (data.includes('success')) {
            console.log('Upload successful, reloading in 2 seconds...');
            alert("Upload successful! If you have uploaded multiple times, on this same page without refreshing manually, you may need to refresh manually by clicking the refresh button on any browser of you choice or hold ctrl + shift + r to refresh.");
            setTimeout(() => window.location.reload(), 2000);
        } else {
            console.error('Upload failed with response:', data);
            alert("Upload failed: " + data);
        }
    }).catch(err => {
        console.error('Fetch error:', err);
        alert("Error: " + err);
    });
});
</script>
<script>
    // cropping tool by cloudinary

</script>
</body>
</html>