<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/../logs/php_errors/php_errors.log");

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access settings.");
}

$userId = $_SESSION['user_id'];

// Fetch current settings
$query = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$query->bind_param("i", $userId);
$query->execute();
$result = $query->get_result();
$settings = $result->fetch_assoc();

// Fetch current blacklist
$blacklistQuery = $db->prepare("SELECT tag FROM user_blacklist WHERE user_id = ?");
$blacklistQuery->bind_param("i", $userId);
$blacklistQuery->execute();
$blacklistResult = $blacklistQuery->get_result();
$blacklistData = $blacklistResult->fetch_assoc();
$blacklistTags = $blacklistData ? $blacklistData['tag'] : '';

// Fetch current user info (without username/email updates)
$userQuery = $db->prepare("SELECT username, email FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userInfo = $userResult->fetch_assoc();

// If no settings exist, create default ones
if (!$settings) {
    $insert = $db->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
    $insert->bind_param("i", $userId);
    $insert->execute();
    $settings = [
        "theme" => "light",
        "language" => "en",
        "font_size" => "medium",
        "notifications_enabled" => 1,
        "custom_css" => "",
        "custom_css_md5" => "",
        "wallpaper" => "light-wallpaper"
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'];
    $language = $_POST['language'];
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $blacklist = trim($_POST['blacklist_tags']);
    $wallpaper = $_POST['wallpaper'];
    $timezone = $_POST['timezone'];
    $perPage = $_POST['per_page'];
    $icon = $_POST['icon'];

    // NEW: Custom CSS handling - convert empty string to null
    $custom_css = trim($_POST['custom_css'] ?? '');
    if ($custom_css === '') {
        $custom_css_db = null;
        $custom_css_md5_db = null;
    } else {
        $custom_css_db = $custom_css;
        $custom_css_md5_db = md5($custom_css);
    }

    // Update user settings
    $update = $db->prepare("
        UPDATE user_settings
        SET theme = ?, language = ?, notifications_enabled = ?, custom_css = ?, custom_css_md5 = ?, wallpaper = ?, icon = ?, timezone = ?, per_page = ?
        WHERE user_id = ?
    ");
    $update->bind_param(
        "ssisssssii",
        $theme,
        $language,
        $notifications_enabled,
        $custom_css_db,
        $custom_css_md5_db,
        $wallpaper,
        $icon,
        $timezone,
        $perPage,
        $userId
    );
    $update->execute();

    // Update user blacklist only if the input is not empty
    if (!empty($blacklist)) {
        if ($blacklistData) {
            $updateBlacklist = $db->prepare("UPDATE user_blacklist SET tag = ? WHERE user_id = ?");
            $updateBlacklist->bind_param("si", $blacklist, $userId);
            $updateBlacklist->execute();
        } else {
            $insertBlacklist = $db->prepare("INSERT INTO user_blacklist (user_id, tag) VALUES (?, ?)");
            $insertBlacklist->bind_param("is", $userId, $blacklist);
            $insertBlacklist->execute();
        }
    }

    // Handle security question updates
    if (isset($_POST['update_security_questions'])) {
        $security_question1 = trim($_POST['security_question1']);
        $security_answer1 = trim($_POST['security_answer1']);
        $security_question2 = trim($_POST['security_question2']);
        $security_answer2 = trim($_POST['security_answer2']);

        if (empty($security_question1) || empty($security_answer1) || empty($security_question2) || empty($security_answer2)) {
            $_SESSION['message'] = ["type" => "error", "text" => "All security questions and answers are required."];
            header("Location: settings");
            exit();
        }

        if ($security_question1 === $security_question2) {
            $_SESSION['message'] = ["type" => "error", "text" => "Security questions must be different."];
            header("Location: settings");
            exit();
        }

        $security_answer1_hashed = password_hash($security_answer1, PASSWORD_BCRYPT);
        $security_answer2_hashed = password_hash($security_answer2, PASSWORD_BCRYPT);

        $updateSecurity = $db->prepare("UPDATE users SET security_question1 = ?, security_answer1 = ?, security_question2 = ?, security_answer2 = ? WHERE id = ?");
        $updateSecurity->bind_param("ssssi", $security_question1, $security_answer1_hashed, $security_question2, $security_answer2_hashed, $userId);

        if ($updateSecurity->execute()) {
            $_SESSION['message'] = ["type" => "success", "text" => "Security questions updated successfully."];
        } else {
            $_SESSION['message'] = ["type" => "error", "text" => "Failed to update security questions. Please try again."];
        }

        header("Location: settings");
        exit();
    }

    header("Location: settings?success=1");
    exit();
}

// Determine if a warning message should be displayed
$showWarning = false;
if (
    ($settings['theme'] == 'dark' && ($settings['wallpaper'] == 'light-wallpaper' || $settings['wallpaper'] == 'old-wallpaper')) ||
    ($settings['theme'] == 'light' && $settings['wallpaper'] == 'dark-wallpaper')
) {
    $showWarning = true;
}
include_once('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    <link rel="stylesheet" href="../public/css/styles-e621.css">
    <script>
        function updateTheme() {
            const theme = document.getElementById('theme').value;
            const wallpaperSelect = document.getElementById('wallpaper');
            document.body.classList.remove('light', 'dark', 'ios26-dark', 'ios26-light', 'e621');
            document.body.classList.add(theme);
            // Set default wallpaper for e621 theme
            if (theme === 'e621') {
                wallpaperSelect.value = 'e621-wallpaper';
            } else if (theme === 'dark') {
                wallpaperSelect.value = 'dark-wallpaper';
            } else {
                wallpaperSelect.value = 'light-wallpaper';
            }
            updateWallpaper();
        }

        function updateWallpaper() {
            const wallpaper = document.getElementById('wallpaper').value;
            document.body.classList.remove('light-wallpaper', 'dark-wallpaper', 'old-wallpaper', 'e621-wallpaper');
            document.body.classList.add(wallpaper);

            const theme = document.getElementById('theme').value;
            const warning = document.getElementById('warning');
            if ((theme === 'dark' && (wallpaper === 'light-wallpaper' || wallpaper === 'old-wallpaper')) ||
                (theme === 'light' && wallpaper === 'dark-wallpaper')) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateWallpaper();
            // Set theme class on load
            const theme = document.getElementById('theme').value;
            document.body.classList.add(theme);
        });
    </script>
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

        .settings-container {
            margin-bottom: 100px !important;
        }

        .username-form {
            font-size: 20px;
            font-weight: bold;
            color: grey;
        }

        .email-form {
            font-size: 20px;
            font-weight: bold;
            color: grey;
        }
    </style>
</head>
<body class="<?php echo htmlspecialchars($settings['theme'] ?? '') . ' ' . htmlspecialchars($settings['wallpaper'] ?? ''); ?>">

<nav>
<?php include_once '../includes/nav.php'; ?>
</nav>
    <?php include_once '../includes/site-notice.php'; ?>

<div class="settings-container card">
    <h2>Settings</h2>
    <p>Do not use these settings as they are still not yet available to use.</p>

    <form method="POST" onsubmit="return validateForm()">
        <?php echo csrf_input(); ?>
        <label for="username">Username:</label>
        <p class="username-form"><?php echo htmlspecialchars($userInfo['username'], ENT_QUOTES, 'UTF-8'); ?></p>

        <label for="email">Email:</label>
        <p class="email-form"><?php echo htmlspecialchars($userInfo['email'], ENT_QUOTES, 'UTF-8'); ?>

        <label for="theme">Theme:</label>
        <select name="theme" id="theme" onchange="updateTheme()">
            <option value="light" <?php if (($settings['theme'] ?? '') == 'light') echo 'selected'; ?>>Light</option>
            <option value="dark" <?php if (($settings['theme'] ?? '') == 'dark') echo 'selected'; ?>>Dark</option>
            <option value="ios26-dark" <?php if (($settings['theme'] ?? '') == 'ios26-dark') echo 'selected'; ?>>iOS 26 Dark (WWDC 25)</option>
            <option value="ios26-light" <?php if (($settings['theme'] ?? '') == 'ios26-light') echo 'selected'; ?>>iOS 26 Light (WWDC 25)</option>
            <option value="e621" <?php if (($settings['theme'] ?? '') == 'e621') echo 'selected'; ?>>e621 Theme Pack (Under Development)</option>
        </select>

        <label for="per_page">Items per page:</label>
        <input class="per_page" type="number" name="per_page" id="per_page" min="5" max="75" value="<?php echo htmlspecialchars($settings['per_page'] ?? '75'); ?>">

        <label for="icon">Icon:</label>
        <p>To learn more about the old icon <a href="/static/docs/old_icon/">click here</a>.</p>
        <select name="icon" id="icon">
            <option value="default" <?php if (($settings['icon'] ?? '') == 'default') echo 'selected'; ?>>Default</option>
            <option value="january" <?php if (($settings['icon'] ?? '') == 'january') echo 'selected'; ?>>January</option>
            <option value="february" <?php if (($settings['icon'] ?? '') == 'february') echo 'selected'; ?>>February</option>
            <option value="march" <?php if (($settings['icon'] ?? '') == 'march') echo 'selected'; ?>>March</option>
            <option value="april" <?php if (($settings['icon'] ?? '') == 'april') echo 'selected'; ?>>April</option>
            <option value="may" <?php if (($settings['icon'] ?? '') == 'may') echo 'selected'; ?>>May</option>
            <option value="june" <?php if (($settings['icon'] ?? '') == 'june') echo 'selected'; ?>>June (Pride)</option>
            <option value="july" <?php if (($settings['icon'] ?? '') == 'july') echo 'selected'; ?>>July</option>
            <option value="august" <?php if (($settings['icon'] ?? '') == 'august') echo 'selected'; ?>>August</option>
            <option value="september" <?php if (($settings['icon'] ?? '') == 'september') echo 'selected'; ?>>September</option>
            <option value="october" <?php if (($settings['icon'] ?? '') == 'october') echo 'selected'; ?>>October</option>
            <option value="november" <?php if (($settings['icon'] ?? '') == 'november') echo 'selected'; ?>>November</option>
            <option value="december" <?php if (($settings['icon'] ?? '') == 'december') echo 'selected'; ?>>December</option>
            <option value="ace" <?php if (($settings['icon'] ?? '') == 'ace') echo 'selected'; ?>>Ace</option>
            <option value="aro" <?php if (($settings['icon'] ?? '') == 'aro') echo 'selected'; ?>>Aro</option>
            <option value="bi" <?php if (($settings['icon'] ?? '') == 'bi') echo 'selected'; ?>>Bisexual</option>
            <option value="french" <?php if (($settings['icon'] ?? '') == 'french') echo 'selected'; ?>>French</option>
            <option value="gay" <?php if (($settings['icon'] ?? '') == 'gay') echo 'selected'; ?>>Gay</option>
            <option value="genderfluid" <?php if (($settings['icon'] ?? '') == 'genderfluid') echo 'selected'; ?>>Genderfluid</option>
            <option value="lesbian" <?php if (($settings['icon'] ?? '') == 'lesbian') echo 'selected'; ?>>Lesbian</option>
            <option value="nonbinary" <?php if (($settings['icon'] ?? '') == 'nonbinary') echo 'selected'; ?>>Nonbinary</option>
            <option value="old" <?php if (($settings['icon'] ?? '') == 'old') echo 'selected'; ?>>Old Icon</option>
            <option value="omni" <?php if (($settings['icon'] ?? '') == 'omni') echo 'selected'; ?>>Omnisexual</option>
            <option value="pan" <?php if (($settings['icon'] ?? '') == 'pan') echo 'selected'; ?>>Pansexual</option>
            <option value="progress" <?php if (($settings['icon'] ?? '') == 'progress') echo 'selected'; ?>>Progress Pride Flag</option>
            <option value="pride" <?php if (($settings['icon'] ?? '') == 'pride') echo 'selected'; ?>>Pride</option>
            <option value="trans" <?php if (($settings['icon'] ?? '') == 'trans') echo 'selected'; ?>>Transgender</option>
            <option value="none" <?php if (($settings['icon'] ?? '') == 'none') echo 'selected'; ?>>None</option>
        </select>

        <label for="language">Language:</label>
        <select name="language" id="language">
            <option value="en" <?php if (($settings['language'] ?? '') == 'en') echo 'selected'; ?>>English</option>
            <option value="es" <?php if (($settings['language'] ?? '') == 'es') echo 'selected'; ?>>Spanish</option>
            <option value="fr" <?php if (($settings['language'] ?? '') == 'fr') echo 'selected'; ?>>French</option>
        </select>

        <label for="timezone">Timezone:</label>
        <select name="timezone" id="timezone">
        <?php
        $current_tz = $settings['timezone'] ?? 'America/Chicago';
        $timezones = DateTimeZone::listIdentifiers();

        foreach ($timezones as $tz) {
            $date = new DateTime('now', new DateTimeZone($tz));
            $offset = $date->format('P');
            $label = str_replace('_', ' ', $tz);
            $selected = ($tz === $current_tz) ? 'selected' : '';
            echo "<option value='$tz' $selected>UTC$offset — $label</option>";
        }
        ?>
        </select>

        
        <h2>Blacklist</h2>
        <label for="blacklist_tags">Blacklist Tags (comma-separated):</label>
        <input type="text" name="blacklist_tags" id="blacklist_tags" value="<?php echo htmlspecialchars($blacklistTags ?? ''); ?>" placeholder="e.g. tag1, tag2, tag3">
        
        <h2>Wallpaper</h2>
        <label for="wallpaper">Select Wallpaper:</label>
        <select name="wallpaper" id="wallpaper" onchange="updateWallpaper()">
            <option value="light-wallpaper" <?php if (($settings['wallpaper'] ?? '') == 'light-wallpaper') echo 'selected'; ?>>Light Wallpaper</option>
            <option value="dark-wallpaper" <?php if (($settings['wallpaper'] ?? '') == 'dark-wallpaper') echo 'selected'; ?>>Dark Wallpaper</option>
            <option value="old-wallpaper" <?php if (($settings['wallpaper'] ?? '') == 'old-wallpaper') echo 'selected'; ?>>Old Wallpaper</option>
            <option value="e621-wallpaper" <?php if (($settings['wallpaper'] ?? '') == 'e621-wallpaper') echo 'selected'; ?>>e621 Theme Wallpaper</option>
        </select>

        <h2>Custom CSS (Advanced Users)</h2>
        <p>Paste your custom CSS here. It will be loaded via <code>/you/custom_style.css?md5=...</code>
        <br>
        <?php if (!empty($md5)): ?><p><a href="/you/custom_style.css?md5=<?= htmlspecialchars($md5) ?>">View Your Custom CSS</a></p><?php endif; ?>

        <textarea name="custom_css" rows="10" style="width:100%;"><?= htmlspecialchars($settings['custom_css'] ?? '') ?></textarea>

        <p id="warning" class="warning" style="display:<?php echo $showWarning ? 'block' : 'none'; ?>">Warning: Some elements might be hard to see with the current theme and wallpaper combination.</p>

        <h2>Done?</h2>
        <button class="button" type="submit">Save Settings</button>
    </form>

    <h2>Change Security Questions</h2>
    <form method="POST">
        <?php echo csrf_input(); ?>
        <label for="security_question1">Security Question 1:</label>
        <select name="security_question1" id="security_question1" required>
            <option value="">-- Select a question --</option>
            <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
            <option value="What was the name of your first pet?">What was the name of your first pet?</option>
            <option value="What was the name of your elementary school?">What was the name of your elementary school?</option>
            <option value="What is your favorite food?">What is your favorite food?</option>
        </select>

        <label for="security_answer1">Answer to Security Question 1:</label>
        <input type="text" name="security_answer1" id="security_answer1" required>

        <label for="security_question2">Security Question 2:</label>
        <select name="security_question2" id="security_question2" required>
            <option value="">-- Select a question --</option>
            <option value="What is your father's middle name?">What is your father's middle name?</option>
            <option value="What was the make of your first car?">What was the make of your first car?</option>
            <option value="What city were you born in?">What city were you born in?</option>
            <option value="What is your favorite color?">What is your favorite color?</option>
        </select>

        <label for="security_answer2">Answer to Security Question 2:</label>
        <input type="text" name="security_answer2" id="security_answer2" required>

        <button class="button" type="submit" name="update_security_questions">Update Security Questions</button>
    </form>

    <div class="advanced-settings">
        <h2>Advanced</h2>
        <p>These settings are for advanced users</p>
        <a class="button" href="/auth/reset_password">Change Password</a>
        <a class="button" href="/auth/delete_account">Delete Account</a>
        <a class="button" href="/settings/blacklist">View Blacklisted Tags</a>
        <a class="button" href="/settings/export_data">Export My Data</a>
    </div>
</div>
<br><br><br><br>

<?php include('../includes/version.php'); ?>
<footer>
<p>&copy; 2026 FluffFox. (Property of NIXTENSSERVER (nixten.ddns.net)) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
</footer>
</body>
</html>