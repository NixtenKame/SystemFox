<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../includes/header.php'); // Include common header
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $receiver_id !== 0 ? "Chat with " . htmlspecialchars($receiver['username'], ENT_QUOTES, 'UTF-8') : "Chat - FluffFox"; ?></title>
    <link rel="stylesheet" href="../public/css/styles.css"> <!-- Link to your CSS file -->
    <style>
        #chat-box {
            width: 100%;
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            background: #f9f9f9;
        }
        .chat-message {
            margin-bottom: 8px;
            padding: 5px;
            border-radius: 5px;
        }
        .chat-message strong {
            color: #007bff;
        }

        .search-box {
            width: 79%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .timestamp {
            font-size: 0.8em;
            color: gray;
        }

        .alert {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .archived-notice {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        #chat-form, form[action="/moderation/report"] {
            display: none;
        }
    </style>
</head>
<body>
    
    <nav>
<?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <main style="margin-bottom: 5%;">
        <div class="archived-notice"><i class="fa-solid fa-triangle-exclamation"></i> This chat has been archived. You can read messages but cannot send new ones.</div>
        <div id="chat-box"></div>

        <?php if (!$chat_allowed): ?>
        <p class="alert">Messaging between minors and adults is not allowed.</p>
        <?php elseif ($receiver_id !== 0): ?>
        <form id="chat-form">
            <?php echo csrf_input(); ?>
            <input type="hidden" id="receiver_id" value="<?php echo $receiver_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input class="search-box" type="text" id="message-input" name="message" placeholder="Type a message..." required>
            <button class="button" type="submit">Send</button>
            <button class="button" onclick="window.location.href='/<?php echo urlencode($receiver['username']); ?>'">
                View User's Profile
            </button>
        </form>
        <?php else: ?>
            <div id="chat-form"></div>
        <?php endif; ?>
    </main>

<script>
    function escapeHTML(str) {
        return str.replace(/[&<>"'`]/g, (char) => {
            const escapeMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
                '`': '&#096;'
            };
            return escapeMap[char];
        });
    }

    function isScrolledToBottom(chatBox) {
        // Allow a small margin for rounding errors
        return chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 5;
    }

    function loadMessages(forceScroll = false) {
        let receiverId = document.getElementById("receiver_id") ? document.getElementById("receiver_id").value : 0;

        fetch(receiverId ? "/c/fetch_private_messages?receiver_id=" + receiverId : "/public_chat/fetch_messages")
        .then(response => response.json())
        .then(messages => {
            let chatBox = document.getElementById("chat-box");
            let wasAtBottom = isScrolledToBottom(chatBox);

            chatBox.innerHTML = "";

            // Show messages oldest at top, newest at bottom
            messages.forEach(msg => {
                let messageElement = document.createElement("div");
                messageElement.classList.add("chat-message");
                messageElement.innerHTML = `<strong>${escapeHTML(msg.username)}</strong>: ${escapeHTML(msg.message)} <span class="timestamp">(${msg.timestamp})</span>`;
                chatBox.appendChild(messageElement);
            });

            // Only scroll to bottom if user was already at bottom or forceScroll is true
            if (wasAtBottom || forceScroll) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
            // Otherwise, preserve scroll position (user is viewing past messages)
        })
        .catch(error => console.error('Error loading messages:', error));
    }

    // Chat is archived - disable message sending
    document.getElementById("chat-form").addEventListener("submit", function(event) {
        event.preventDefault();
        alert("This chat is archived. Sending messages is disabled.");
        return false;
    });

    // Initial load, scroll to bottom
    loadMessages(true);
</script>
    
    <br><br><br><br>
    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>