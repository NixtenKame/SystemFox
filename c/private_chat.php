<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php');
include_once('../actions/friends.php');

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access private chat.");
}

// ----------------------
// URL parsing
// ----------------------
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$query = $_SERVER['QUERY_STRING'] ?? '';

parse_str($query, $queryParams);
$userFromQuery = isset($queryParams['user']) ? intval($queryParams['user']) : null;

$matches = [];
$userFromPath = null;
if (preg_match('#^/c/(\d+)$#', $path, $matches)) {
    $userFromPath = intval($matches[1]);
}

if (preg_match('#^/c/private_chat(?:\.php)?$#', $path) && $userFromQuery !== null) {
    header("Location: /c/$userFromQuery", true, 301);
    exit;
}

if ($userFromPath !== null && !empty($query)) {
    if (!(count($queryParams) === 1 && isset($queryParams['user']) && $queryParams['user'] == $userFromPath)) {
        header("Location: /c/$userFromPath", true, 301);
        exit;
    }
}

if ($userFromPath === null) {
    http_response_code(404);
    exit("Invalid or missing user ID in URL.");
}

$receiver_id = $userFromPath;

// ----------------------
// Age helper
// ----------------------
function calculate_age($birthdate) {
    if (!$birthdate) return null;

    try {
        $dob = new DateTime($birthdate);
        $today = new DateTime('today');
        return $dob->diff($today)->y;
    } catch (Exception $e) {
        return null;
    }
}

// ----------------------
// Get sender info
// ----------------------
$user_stmt = $db->prepare("SELECT birthdate FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$sender = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// ----------------------
// Get receiver info
// ----------------------
$stmt = $db->prepare("SELECT username, birthdate, online_status FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$receiver = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$receiver) {
    http_response_code(404);
    exit("User not found.");
}

// ----------------------
// Enforce 13+ rule
// ----------------------
$sender_age   = calculate_age($sender['birthdate'] ?? null);
$receiver_age = calculate_age($receiver['birthdate'] ?? null);

$chat_allowed = (
    $sender_age !== null &&
    $receiver_age !== null &&
    $sender_age >= 13 &&
    $receiver_age >= 13
);

if (!$chat_allowed) {
    http_response_code(403);
    exit("Private chat is restricted to users aged 13 or older.");
}

// ----------------------
// Full receiver profile data
// ----------------------
$query = "
    SELECT id, username, bio, profile_picture, email, level, created_at,
           last_active, birthdate, online_status,
           email_visibility, birthday_visibility
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ----------------------
// Profile picture handling
// ----------------------
$profilePicture = (!empty($userData['profile_picture']))
    ? (strpos($userData['profile_picture'], '/public/') === 0
        ? $userData['profile_picture']
        : '/public/uploads/' . htmlspecialchars($userData['profile_picture'], ENT_QUOTES, 'UTF-8'))
    : '/public/images/default-profile.png';

$user_id = $_SESSION['user_id'] ?? 0;

// ----------------------
// Relationship status
// ----------------------
$relStmt = $db->prepare("
    SELECT relationship_status
    FROM user_relationships
    WHERE user_id = ? AND target_id = ?
    LIMIT 1
");
$relStmt->bind_param("ii", $user_id, $userData['id']);
$relStmt->execute();
$relRow = $relStmt->get_result()->fetch_assoc();
$relationshipStatus = $relRow['relationship_status'] ?? 'none';
$relStmt->close();

// ----------------------
// Friends sidebar helper
// ----------------------
function fetch_next_friend($db, $user_id) {
    static $friend_stmt = null;
    static $friend_result = null;

    if ($friend_stmt === null) {
        $friend_query = "
            SELECT u.id, u.username, u.profile_picture, u.online_status
            FROM users u
            JOIN user_relationships ur ON u.id = ur.target_id
            WHERE ur.user_id = ? AND ur.relationship_status = 'friends'
            ORDER BY u.username ASC
        ";
        $friend_stmt = $db->prepare($friend_query);
        $friend_stmt->bind_param("i", $user_id);
        $friend_stmt->execute();
        $friend_result = $friend_stmt->get_result();
    }

    return $friend_result->fetch_assoc();
}

$content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat with <?php echo htmlspecialchars($receiver['username'], ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="stylesheet" href="../public/css/styles.css">
<link rel="stylesheet" href="../public/css/private_chat.css">
<style>
.chat-message.sent {
    background: purple; /* or your theme’s color */
    align-self: flex-end;
    text-align: right;
    margin-left: auto;
    border-bottom-right-radius: 0px;
}

.chat-message.received {
    background: #404249;
    align-self: flex-start;
    text-align: left;
    border-top-left-radius: 0px;
}

.send-button {
    background: purple; /* or your theme’s color */
    color: white;
    font-size: 100%;
}

@media (max-width: 768px) {
    .chat-message {
        max-width: 80%;
    }

    .text-chat-box {
        width: calc(100% - 120px);
    }

    .send-button {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        touch-action: manipulation;
    }
}
/* ===== Discord-like DM Sidebar ===== */
.dm-list {
    width: 260px;
    height: 100vh;
    background-color: #1e1f22;
    border-right: 1px solid #2b2d31;
    display: flex;
    flex-direction: column;
    color: #dbdee1;
}

/* Header / Search */
.dm-header {
    padding: 10px;
    border-bottom: 1px solid #2b2d31;
}

.dm-search {
    width: 100%;
    padding: 8px 10px;
    background: #2b2d31;
    border: none;
    border-radius: 4px;
    color: #dbdee1;
    font-size: 14px;
}
.dm-search::placeholder {
    color: #949ba4;
}

/* Scrollable list */
.dm-users {
    flex: 1;
    overflow-y: auto;
}

/* DM Item */
.dm-user-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    margin: 2px 6px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s ease;
}

.dm-user-item:hover {
    background-color: #35373c;
}

.dm-user-item.active {
    background-color: #404249;
}

/* Avatar + status ring */
.dm-user-avatar-wrapper {
    position: relative;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.dm-user-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

/* Status dot (Discord-style) */
.dm-status-indicator {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 3px solid #1e1f22;
}

.dm-status-indicator.online {
    background-color: #23a55a;
}

.dm-status-indicator.offline {
    background-color: #80848e;
}

/* Username + meta */
.dm-user-content {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex: 1;
}

.dm-username {
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dm-last-message {
    font-size: 12px;
    color: #949ba4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Unread badge */
.dm-unread {
    background: #f23f42;
    color: #fff;
    font-size: 12px;
    min-width: 18px;
    height: 18px;
    padding: 0 6px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Scrollbar (WebKit) */
.dm-users::-webkit-scrollbar {
    width: 6px;
}
.dm-users::-webkit-scrollbar-thumb {
    background: #2b2d31;
    border-radius: 3px;
}
.dm-toggle {
    display: none;
    background: #2b2d31;
    border: none;
    color: #fff;
    font-size: 22px;
    cursor: pointer;
    position: fixed;
    padding: 8px 12px;
    border-radius: 15px;
    z-index: 10;
    top: 48px
}

/* ===== Mobile DM Sidebar ===== */
@media (max-width: 900px) {

    .chat-layout {
        position: relative;
    }

    .dm-list {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 260px;
        z-index: 1000;
        transform: translateX(-100%);
        transition: transform 0.25s ease;
        box-shadow: 4px 0 12px rgba(0,0,0,0.4);
    }

    .dm-list.open {
        transform: translateX(0);
    }

    /* Dark overlay behind DM list */
    .dm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
        display: none;
    }

    .dm-overlay.show {
        display: block;
    }
    .dm-toggle {
        display: inline-block;
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
    <!-- Mobile DM overlay -->
    <div class="dm-overlay" id="dmOverlay" onclick="toggleDMList()"></div>

    <div class="chat-layout">

        <!-- DM LIST -->
        <div class="dm-list" id="dmList">
            <div class="dm-header">
                <input type="text" class="dm-search" placeholder="Find or start a conversation">
            </div>

            <div class="dm-users">
                <?php while ($friend = fetch_next_friend($db, $user_id)) : ?>
                    <div
                        class="dm-user-item <?php echo ($receiver_id == $friend['id']) ? 'active' : ''; ?>"
                        onclick="window.location.href='/c/<?php echo $friend['id']; ?>'">

                        <div class="dm-user-avatar-wrapper">
                            <img
                                src="<?php echo !empty($friend['profile_picture'])
                                    ? '/public/uploads/' . htmlspecialchars($friend['profile_picture'], ENT_QUOTES)
                                    : '/public/images/default-profile.png'; ?>"
                                class="dm-user-avatar"
                                alt="Avatar">

                            <span class="dm-status-indicator <?php echo $friend['online_status'] === 'online' ? 'online' : 'offline'; ?>"></span>
                        </div>

                        <div class="dm-user-content">
                            <span class="dm-username">
                                <?php echo htmlspecialchars($friend['username'], ENT_QUOTES); ?>
                            </span>
                            <span class="dm-last-message">
                                <?php echo htmlspecialchars($friend['last_message'] ?? ''); ?>
                            </span>
                        </div>

                        <?php if (!empty($friend['unread_count'])): ?>
                            <span class="dm-unread">
                                <?php echo (int)$friend['unread_count']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- CHAT COLUMN -->
        <div class="chat-column">

            <!-- Mobile back button -->
            <button class="dm-toggle" onclick="toggleDMList()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>

            <!-- Chat actions dropdown -->
            <?php if ($user_id && $user_id != $userData['id']): ?>
            <div class="chat-actions">
                <button class="chat-actions-btn" onclick="toggleActionsMenu()">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>

                <div class="chat-actions-menu" id="chatActionsMenu">

                    <?php if ($relationshipStatus === 'friends'): ?>
                        <div class="menu-item success">Friends</div>

                        <form method="POST" action="/api/friends/block">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="target_username" value="<?php echo htmlspecialchars($userData['username']); ?>">
                            <button class="menu-item danger">Block User</button>
                        </form>

                    <?php elseif ($relationshipStatus === 'pending_out'): ?>
                        <div class="menu-item disabled">Friend Request Sent</div>

                    <?php elseif ($relationshipStatus === 'pending_in'): ?>
                        <form method="POST" action="/api/friends/accept">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="from_username" value="<?php echo $userData['username']; ?>">
                            <button class="menu-item">Accept Friend Request</button>
                        </form>

                    <?php elseif ($relationshipStatus === 'blocked' || $relationshipStatus === 'blocked_by'): ?>
                        <div class="menu-item danger">User Blocked</div>

                    <?php else: ?>
                        <form method="POST" action="/api/friends/add">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="target_username" value="<?php echo $receiver['username']; ?>">
                            <button class="menu-item">Add Friend</button>
                        </form>
                    <?php endif; ?>

                    <hr>

                    <button class="menu-item danger" onclick="openReport()">Report User</button>
                </div>
            </div>
            <?php endif; ?>

            <!-- CHAT BOX -->
            <div id="chat-box"></div>

            <!-- Typing indicator -->
            <div style="background-color:black;display:flex;white-space:nowrap;margin-bottom:10px;">
                <p style="font-size:14px;font-style:italic;margin:5px 10px;">
                    typing status:
                </p>
                <div id="typing-indicator"
                     class="typing-indicator"
                     style="font-size:14px;color:#bbb;font-style:italic;margin:5px 10px;margin-left:0;display:none;">
                </div>
            </div>

            <!-- Chat form -->
            <?php if (!$chat_allowed): ?>
                <p class="alert">Messaging between minors and adults is not allowed.</p>
            <?php else: ?>
                <form id="chat-form" enctype="multipart/form-data">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" id="receiver_id" value="<?php echo $receiver_id; ?>">
                    <input type="hidden" id="current_user_id" value="<?php echo $_SESSION['user_id']; ?>">
                    <input class="text-chat-box" type="text" id="message-input" name="message" placeholder="Type a message..." required>
                    <input type="file" id="image-input" name="image" accept="image/*">
                    <button type="submit" class="send-button" style="width:3.5rem;height:3.5rem;border-radius:100px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;touch-action:manipulation;" aria-label="Send"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <!-- Image popup -->
    <div id="image-popup" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;justify-content:center;align-items:center;">
        <img id="popup-image" src="" alt="Enlarged Image" style="max-width:90%;max-height:90%;border-radius:10px;">
        <button id="popup-close" style="position:absolute;top:20px;right:20px;background:#fff;border:none;padding:10px 15px;font-size:16px;cursor:pointer;border-radius:5px;">
            Close
        </button>
    </div>

    <!-- Report popup -->
    <div id="report-overlay"></div>
    <div id="report-popup">
        <h3>Report User</h3>
        <form action="/moderation/report" method="POST">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="content_id" value="<?= $receiver_id; ?>">
            <input type="hidden" name="content_type" value="user">
            <label>Reason:</label>
            <select name="reason" required>
                <option value="harassment">Harassment</option>
                <option value="spam">Spam</option>
                <option value="inappropriate">Inappropriate Messages</option>
                <option value="other">Other</option>
            </select>
            <textarea name="details" placeholder="Provide more details (optional)"></textarea>
            <button type="submit">Submit Report</button>
            <button type="button" onclick="closeReport()">Cancel</button>
        </form>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const currentUserId = document.getElementById("current_user_id").value;
    const receiverId = document.getElementById("receiver_id").value;
    const chatBox = document.getElementById("chat-box");
    const typingIndicator = document.getElementById("typing-indicator");

    function escapeHTML(str) {
        return str.replace(/[&<>"'`]/g, (char) => {
            const escapeMap = { 
                '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;',
                "'":'&#039;', '`':'&#096;'
            };
            return escapeMap[char];
        });
    }

    function appendMessage(msg) {
        const messageElement = document.createElement("div");

        if (parseInt(msg.sender_id) === parseInt(currentUserId)) {
            messageElement.classList.add("chat-message", "sent");
        } else {
            messageElement.classList.add("chat-message", "received");
        }

        let messageContent = `
            <strong>${escapeHTML(msg.username)}</strong>
            ${escapeHTML(msg.message)}
            <span class="timestamp">(${msg.timestamp})</span>
        `;

        if (msg.image_path) {
            messageContent += `
                <br>
                <img src="${escapeHTML(msg.image_path)}" class="chat-image"
                     style="max-width:200px;max-height:200px;border-radius:5px;margin-top:5px;cursor:pointer;">
            `;
        }

        messageElement.innerHTML = messageContent;
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;

        messageElement.querySelectorAll(".chat-image").forEach(img => {
            img.addEventListener("click", () => openImagePopup(img.src));
        });
    }

    // Load old messages
    function loadMessages() {
        fetch(`/c/fetch_private_messages?receiver_id=${receiverId}`)
            .then(res => res.json())
            .then(messages => {
                chatBox.innerHTML = "";
                messages.forEach(msg => appendMessage(msg));
            })
            .catch(err => console.error("Error fetching messages:", err));
    }
    loadMessages();

    // Image popup
    function openImagePopup(src) {
        const popup = document.getElementById("image-popup");
        const img = document.getElementById("popup-image");
        img.src = src;
        popup.style.display = "flex";
    }
    document.getElementById("popup-close").addEventListener("click", () => {
        document.getElementById("image-popup").style.display = "none";
    });
    document.getElementById("image-popup").addEventListener("click", e => {
        if (e.target === document.getElementById("image-popup"))
            e.target.style.display = "none";
    });

    // WebSocket
    const ws = new WebSocket("wss://nixten.ddns.net:5502");

    ws.onopen = () => console.log("Connected to WebSocket!");

    // TYPING VARIABLES
    let typingTimeout = null;
    let isTyping = false;

    function sendTypingStatus(type) {
        ws.send(JSON.stringify({
            type: type,
            from: currentUserId,
            to: receiverId
        }));
    }

    const messageInput = document.getElementById("message-input");

    // Detect typing
    messageInput.addEventListener("input", () => {
        if (!isTyping) {
            isTyping = true;
            sendTypingStatus("typing");
        }

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            isTyping = false;
            sendTypingStatus("stop_typing");
        }, 3000);
    });

    // Handle incoming WebSocket messages
    ws.onmessage = (event) => {
        const msg = JSON.parse(event.data);

        // typing handler
        if (msg.type === "typing" && parseInt(msg.from) === parseInt(receiverId)) {
            typingIndicator.textContent = "<?php echo addslashes($receiver['username']); ?> is typing...";
            typingIndicator.style.display = "block";

            clearTimeout(typingIndicator.hideTimer);
            typingIndicator.hideTimer = setTimeout(() => {
                typingIndicator.style.display = "none";
            }, 3000);

            return;
        }

        if (msg.type === "stop_typing" && parseInt(msg.from) === parseInt(receiverId)) {
            typingIndicator.style.display = "none";
            return;
        }

        // chat messages
        const relevant =
            (parseInt(msg.receiver_id) === parseInt(currentUserId) &&
             parseInt(msg.sender_id) === parseInt(receiverId)) ||
            (parseInt(msg.sender_id) === parseInt(currentUserId) &&
             parseInt(msg.receiver_id) === parseInt(receiverId));

        if (!relevant) return;

        // avoid duplication: ignore echo of own message
        if (parseInt(msg.sender_id) === parseInt(currentUserId)) return;

        appendMessage(msg);
    };

    ws.onclose = () => console.log("WebSocket disconnected");

    // Send message
    const chatForm = document.getElementById("chat-form");
    if (chatForm) {
        chatForm.addEventListener("submit", function(e) {
            e.preventDefault();
            const imageInput = document.getElementById("image-input");

            const formData = new FormData();
            formData.append("receiver_id", receiverId);
            formData.append("message", messageInput.value.trim());
            formData.append("csrf_token", "<?php echo $_SESSION['csrf_token']; ?>");

            if (imageInput.files.length > 0)
                formData.append("image", imageInput.files[0]);

            fetch("/c/send_private_message", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.message) {

                    // append locally immediately
                    appendMessage(data.message);

                    // broadcast via WebSocket
                    ws.send(JSON.stringify(data.message));

                    // stop typing state
                    isTyping = false;
                    sendTypingStatus("stop_typing");

                    messageInput.value = "";
                    imageInput.value = "";
                } else if (data.error) {
                    alert(data.error);
                }
            })
            .catch(err => console.error("Error sending message:", err));
        });
    }
});
</script>
<script>
function toggleDMList() {
    const dmList = document.getElementById('dmList');
    const overlay = document.getElementById('dmOverlay');

    dmList.classList.toggle('open');
    overlay.classList.toggle('show');
}
</script>
<script>
(function () {
    let startX = 0;
    let startY = 0;
    let isTracking = false;

    const dmList = document.getElementById('dmList');
    const overlay = document.getElementById('dmOverlay');

    function openDMs() {
        dmList.classList.add('open');
        overlay.classList.add('show');
    }

    function closeDMs() {
        dmList.classList.remove('open');
        overlay.classList.remove('show');
    }

    document.addEventListener('touchstart', function (e) {
        if (e.touches.length !== 1) return;

        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        isTracking = true;
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
        if (!isTracking) return;

        const dx = e.touches[0].clientX - startX;
        const dy = e.touches[0].clientY - startY;

        // User is scrolling vertically → ignore
        if (Math.abs(dy) > Math.abs(dx)) {
            isTracking = false;
            return;
        }

        // Strong horizontal intent required
        if (dx > 80 && !dmList.classList.contains('open')) {
            openDMs();
            isTracking = false;
        }

        if (dx < -80 && dmList.classList.contains('open')) {
            closeDMs();
            isTracking = false;
        }
    }, { passive: true });

    document.addEventListener('touchend', function () {
        isTracking = false;
    });
})();
</script>
<script>
function toggleActionsMenu() {
    document.getElementById('chatActionsMenu').classList.toggle('show');
}

document.addEventListener('click', function (e) {
    const menu = document.getElementById('chatActionsMenu');
    if (!menu || e.target.closest('.chat-actions')) return;
    menu.classList.remove('show');
});
</script>
<script>
function openReport() {
    document.getElementById('report-overlay').style.display = 'block';
    document.getElementById('report-popup').style.display = 'block';
}
function closeReport() {
    document.getElementById('report-overlay').style.display = 'none';
    document.getElementById('report-popup').style.display = 'none';
}
</script>

</body>
</html>