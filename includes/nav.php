<ul class="menu" id="menu">
    <a href="/">
        <img class="logo" src="/public/images/icons/<?= $iconFile ?>.png" alt="Home" width="50" height="50">
    </a>
    <a title="View the large catalog of posts" href="/posts"><i class="fa-solid fa-images"></i> Posts</a>
    <a title="Well what are you waiting for UPLOAD SOMETHING :3" href="/upload/new"><i class="fa-solid fa-upload"></i> Upload</a>
    <a title="View a large list of all tags!" href="/tags"><i class="fa-solid fa-tags"></i> Tags</a>
    <li>
        <p><i class="fa-solid fa-user"></i> Account</p>
        <ul class="dropdown">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/you/"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                <a href="/actions/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                <a href="/you/favorites"><i class="fa-solid fa-star"></i> Favorites</a>
                <a href="/auth/reset_password"><i class="fa-solid fa-key"></i> Reset Password</a>
                <a href="/you/settings"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="/settings/blacklist"><i class="fa-solid fa-ban"></i> Blacklist</a>
                <a href="/help/"><i class="fa-solid fa-circle-question"></i> Help</a>
                <a href="/user/"><i class="fa-solid fa-magnifying-glass"></i> Search Profile</a>
                <a href="/you/requests"><i class="fa-solid fa-user-group"></i> Friend Requests</a>
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'moderator')): ?>
                    <a href="/moderation/moderation"><i class="fa-solid fa-shield-halved"></i> Moderation Panel</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="/login"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                <a href="/register"><i class="fa-solid fa-user-plus"></i> Signup</a>
            <?php endif; ?>
        </ul>
    </li>
    <li>
        <p><i class="fa-solid fa-comments"></i> Chat</p>
        <ul class="dropdown">
            <a href="/c/"><i class="fa-solid fa-message"></i> Private Chat</a>
            <a href="/public_chat/"><i class="fa-solid fa-users"></i> Public Group Chat</a>
        </ul>
    </li>
    <li>
        <p><i class="fa-solid fa-info-circle"></i> Information</p>
        <ul class="dropdown">
            <a href="/assets/docs/Terms of Use"><i class="fa-solid fa-file-contract"></i> Terms of Use</a>
            <a href="/assets/docs/Privacy Policy"><i class="fa-solid fa-user-shield"></i> Privacy Policy</a>
            <a href="/assets/docs/Code Of Conduct"><i class="fa-solid fa-scale-balanced"></i> Code of Conduct</a>
            <a href="/assets/docs/content_moderation"><i class="fa-solid fa-gavel"></i> Content Moderation</a>
            <a href="/assets/docs/dmca_policy"><i class="fa-solid fa-copyright"></i> DMCA Policy</a>
            <a href="/assets/docs/version"><i class="fa-solid fa-code-branch"></i> Site Version</a>
            <a href="/static/docs/gore/"><i class="fa-solid fa-heart-pulse"></i> Gore Content Guidelines</a>
            <a href="/static/docs/underwear-content/"><i class="fa-solid fa-briefcase"></i> Underwear Content Guidelines</a>
            <a href="/static/docs/news/"><i class="fa-solid fa-newspaper"></i> Site News</a>
            <a href="/forums/forum"><i class="fa-solid fa-comments"></i> Forums</a>
            <a href="/assets/docs/FluffFox"><i class="fa-solid fa-paw"></i>About FluffFox</a>
            <a href="/static/site_map"><i class="fa-solid fa-sitemap"></i> Site Map</a>
            <a href="/static/discord"><i class="fa-brands fa-discord"></i> Join Our Discord Server</a>
        </ul>
    </li>
    <li>
        <p><i class="fa-solid fa-users"></i> About Us</p>
        <ul class="dropdown">
            <a href="/Nixten/"><i class="fa-solid fa-crown"></i> Owner's Profile</a>
            <a href="/public/users/lucky_the_wolf/"><i class="fa-solid fa-code"></i> First Developer/Beta Tester</a>
        </ul>
    </li>
    <li id="notification-container">
        <p><span id="notification-icon"><i class="fa-solid fa-bell"></i> Notifications</span></p>
        <span class="notification-count" id="notification-count"><?= intval($unreadCount ?? 0) ?></span>
        <ul id="notification-list" class="dropdown">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <li>
                        <a href="/you/notifications"><?= htmlspecialchars($notification['message']) ?></a>
                        <small><?= htmlspecialchars($notification['created_at']) ?></small>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li><p>No new notifications</p></li>
            <?php endif; ?>
        </ul>
    </li>
    <form class="nav-search" action="/posts/" method="GET">
        <label for="tags"><i class="fa-solid fa-magnifying-glass"></i> Search:</label>
        <input id="searchInput" class="nav-search" type="text" name="q" placeholder="" title="Press Ctrl+K to search">
    </form>
    <button id="requestNotifications">Enable Notifications</button>
</ul>

<script>
  document.querySelectorAll(".mobile-nav a").forEach(button => {
    button.addEventListener("click", () => {
      if (navigator.vibrate) {
        navigator.vibrate(50);
      }
    });
  });
</script>

<script>
  const CURRENT_USER_ID = <?= isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;
  console.log("CURRENT_USER_ID set to:", CURRENT_USER_ID);
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    function renderNotifications(data) {
        const notificationCount = document.getElementById('notification-count');
        const notificationList = document.getElementById('notification-list');
        notificationList.innerHTML = '';

        if (Array.isArray(data) && data.length > 0) {
            notificationCount.textContent = data.length;
            data.forEach(notification => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.textContent = notification.message || '';
                a.href = "/you/notifications";
                li.appendChild(a);
                if (notification.created_at) {
                    const small = document.createElement('small');
                    small.textContent = notification.created_at;
                    li.appendChild(small);
                }
                notificationList.appendChild(li);
            });
        } else {
            notificationCount.textContent = '0';
            const li = document.createElement('li');
            const p = document.createElement('p');
            p.textContent = 'No new notifications';
            li.appendChild(p);
            notificationList.appendChild(li);
        }
    }

    function fetchNotifications() {
        console.log("Fetching notifications via HTTP fallback...");
        fetch('/api/fetch_notifications')
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data && data.error) {
                    console.warn('Notifications fetch returned error:', data.error);
                    renderNotifications([]);
                } else {
                    renderNotifications(data);
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    function setupWebSocket() {
        // Prevent duplicate WebSockets
        if (window.notificationWS && window.notificationWS.readyState === WebSocket.OPEN) {
            console.log("Reusing existing WebSocket connection");
            return window.notificationWS;
        }

        console.log("Connecting WebSocket...");
        const ws = new WebSocket("wss://nixten.ddns.net:5502");
        window.notificationWS = ws;

        let wsActive = false; // becomes true when we receive first notification via WS

        ws.onopen = () => {
            console.log("WebSocket connection opened");

            // Fetch once to populate initial state (in case server doesn't push immediately)
            try { fetchNotifications(); } catch (e) { console.warn('fetchNotifications failed on WS open', e); }

            if (typeof CURRENT_USER_ID !== 'undefined' && CURRENT_USER_ID !== null) {
                try {
                    ws.send(JSON.stringify({ type: "auth", user_id: CURRENT_USER_ID }));
                    console.log("Sent auth message:", CURRENT_USER_ID);
                } catch (e) {
                    console.warn('Failed to send auth message over WS', e);
                }
            } else {
                console.warn("CURRENT_USER_ID is undefined or null. Auth message not sent.");
            }
        };

        ws.onmessage = (event) => {
            console.log("WebSocket message received:", event.data);
            const msg = JSON.parse(event.data);

            if (msg.type === "notification_update") {
                console.log("Rendering notifications via WebSocket");
                renderNotifications(msg.data);
                // On first real WS notification, stop HTTP polling
                if (!wsActive) {
                    wsActive = true;
                    if (typeof notificationIntervalId !== 'undefined' && notificationIntervalId) {
                        clearInterval(notificationIntervalId);
                        notificationIntervalId = null;
                        console.log('Cleared HTTP polling interval because WebSocket delivered notifications');
                    }
                }
            }
        };

        ws.onerror = (err) => {
            console.error("WebSocket error:", err);
        };

        ws.onclose = (ev) => {
            console.warn("WebSocket closed:", ev);
            window.notificationWS = null; // allow reconnect
            // Restart HTTP polling as a fallback
            if (typeof notificationIntervalId === 'undefined' || !notificationIntervalId) {
                notificationIntervalId = setInterval(fetchNotifications, 10000);
                console.log('Restarted HTTP polling after WebSocket close');
            }
            setTimeout(setupWebSocket, 3000);
        };
        return ws;
    }

    // Initial fetch (fallback). We'll poll until WebSocket takes over.
    fetchNotifications();
    let notificationIntervalId = setInterval(fetchNotifications, 10000);

    // WebSocket live updates
    setupWebSocket();

    // Toggle notification UI
    document.getElementById('notification-icon').addEventListener('click', function () {
        let notificationList = document.getElementById('notification-list');
        notificationList.style.display = 
            notificationList.style.display === 'none' ? 'block' : 'none';
    });
});
</script>

<script>
  document.getElementById("news-dismiss").addEventListener("click", function (e) {
    e.preventDefault();
    document.getElementById("news").style.display = "none";
  });

  document.getElementById("news-show").addEventListener("click", function (e) {
    e.preventDefault();
    const newsBody = document.getElementById("news-body");
    if (newsBody.style.display === "none") {
      newsBody.style.display = "block";
      this.textContent = "Hide";
    } else {
      newsBody.style.display = "none";
      this.textContent = "Show";
    }
  });

  document.getElementById("news-body").style.display = "none";
</script>
<script>

// Listen for Ctrl + K
document.addEventListener('keydown', (e) => {
  if (e.ctrlKey && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    searchInput.focus();
  }
});

// Click outside to close
overlay.addEventListener('click', closeSearch);
</script>