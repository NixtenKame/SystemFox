<?php define('ROOT_PATH',realpath(__DIR__.'/..'));include_once ROOT_PATH.'/connections/config.php';include_once 'includes/utils.php';ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(E_ALL);$postCountQuery="SELECT total_posts FROM post_count WHERE id = 1 LIMIT 1";$stmt=$db->prepare($postCountQuery);$stmt->execute();$result=$stmt->get_result();$postCount=($result&&$row=$result->fetch_assoc())?(int) $row['total_posts']:0;$stmt->close();$searchTerm=htmlspecialchars($_GET['tags']?? '',ENT_QUOTES,'UTF-8');$categoryFilter='';$results=[];$itemsPerPage=50;$currentPage=isset($_GET['page'])?(int)$_GET['page']:1;$offset=($currentPage-1)*$itemsPerPage;$totalResults=0;$unreadCount=0;$notifications=[];if(isset($_SESSION['user_id'])){$user_id=$_SESSION['user_id'];$unreadQuery="SELECT COUNT(*) AS unread_count \n                    FROM notifications \n                    WHERE user_id = ? AND status = 0 AND dismissed = 0";$stmt=$db->prepare($unreadQuery);$stmt->bind_param("i",$user_id);$stmt->execute();$result=$stmt->get_result();$unreadCount=($result)?$result->fetch_assoc()['unread_count']:0;$stmt->close();$notifQuery="SELECT id, message, created_at \n                   FROM notifications \n                   WHERE user_id = ? AND dismissed = 0 \n                   ORDER BY created_at DESC \n                   LIMIT 5";$stmt=$db->prepare($notifQuery);$stmt->bind_param("i",$user_id);$stmt->execute();$result=$stmt->get_result();while($row=$result->fetch_assoc()){$notifications[]=$row;}$stmt->close();}$latestNewsQuery="SELECT title, content, author, created_at FROM news ORDER BY created_at DESC LIMIT 1";$newsResult=$db->query($latestNewsQuery);if($newsResult&&$newsResult->num_rows>0){$latestNews=$newsResult->fetch_assoc();$newsTitle=$latestNews['title'];$newsContent=$latestNews['content'];$newsAuthor=$latestNews['author'];$newsDate=date('F j, Y',strtotime($latestNews['created_at']));}else{$newsTitle="No News Available";$newsContent="There is currently no news to display.";$newsAuthor="";$newsDate="";}date_default_timezone_set('UTC');$currentTime=date('Y-m-d H:i:s');include('includes/header.php'); ?><!doctypehtml><html lang="en"><head><meta charset="UTF-8"><meta content="summary_large_image"name="twitter:card"><meta content="FluffFox"name="twitter:title"><meta content="FluffFox"property="og:title"><meta content="FluffFox is a safe for work furry art gallery and artboard website for sharing any form of artwork. Browse, upload, and favorite SFW art in a community focused space."name="twitter:description"><meta content="FluffFox is a safe for work furry art gallery and artboard website for sharing any form of artwork. Browse, upload, and favorite SFW art in a community focused space."property="og:description"><meta content="FluffFox is a safe for work furry art gallery and artboard website for sharing any form of artwork. Browse, upload, and favorite SFW art in a community focused space."name="description"><meta content="https://nixten.ddns.net/public/images/icons/default.png"name="twitter:image"><meta content="https://nixten.ddns.net/public/images/icons/default.png"property="og:image"><meta content="https://nixten.ddns.net/"property="og:url"><meta content="website"property="og:type"><meta content="#005999"name="theme-color"><meta content="width=device-width,initial-scale=1"name="viewport"><meta content="qKrPJrXQZEqADBbIZJni6zvfvVk2X5HKE3f7ZDRbuwE"name="google-site-verification"><title>FluffFox, The SFW artboard site</title><link href="/public/css/styles.css"rel="stylesheet"><style>body.dark .input_bar::placeholder{color:#ddd}.image-container{width:50%;aspect-ratio:auto;max-width:50%;margin:0 auto;background-image:url(https://nixten.ddns.net:9001/data/assets/index/fan-art/114ae387db40bf9f1cb615a7b1ec1baa339f0bd0ec4cd1d702a4da467e807259.png);background-size:contain;background-repeat:no-repeat;background-position:center;height:50vh;pointer-events:none}</style><script src="https://nixten.ddns.net:3001/js/site-notice.js"></script><script>function updateClock() {
            // Parse the server time as Chicago time (UTC-5 or UTC-6 depending on DST)
            // Create a date string that JavaScript can parse, then adjust for timezone
            let phpTimeStr = "<?php echo $currentTime; ?>";
            
            // Parse as if it's already in Chicago time
            // Create a UTC date first, then we'll manually apply the offset
            let parts = phpTimeStr.split(/[- :]/);
            let serverTime = new Date(Date.UTC(
                parseInt(parts[0]),      // year
                parseInt(parts[1]) - 1,  // month (0-indexed)
                parseInt(parts[2]),      // day
                parseInt(parts[3]),      // hour
                parseInt(parts[4]),      // minute
                parseInt(parts[5])       // second
            ));
            
            // Adjust for Chicago timezone offset
            // Get the current offset for Chicago (accounts for DST automatically)
            let chicagoFormatter = new Intl.DateTimeFormat('en-US', {
                timeZone: 'America/Chicago',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });

            function tick() {
                let options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric', 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit', 
                    hour12: true, 
                    timeZone: 'America/Chicago' 
                };

                document.getElementById("serverTime").innerHTML = serverTime.toLocaleString("en-US", options);
                serverTime.setSeconds(serverTime.getSeconds() + 1);
            }

            tick(); // Update immediately on page load
            setInterval(tick, 1000);
        }</script></head><body onload="updateClock()"><body><?php include_once 'includes/header.php'; ?><nav><?php include_once 'includes/nav.php'; ?></nav><?php include_once 'includes/site-notice.php'; ?><main><a style="text-align:center;color:#fff;font-size:10px;font-style:italic"title="Copyright DI_N_K0 all rights reserved (fanart of Nixten you MAY download the image but you MAY not claim the image as you own thank you)"><div class="image-container"></div><p>Fanart of Nixten Leo Kame by DI_N_K0 perms were granted for display on FF</p><p>© Copyright 2025 DI_N_K0 All go to https://nixten.ddns.net/user/r4nd0m_pers0n to learn more about this user rights reserved. Do not claim as your own.</p><p style="text-align:center"><a href="/posts/fan-art">Fan art</a></p></a><div class="search-container"><form action="/posts/"><label for="tags">Search:</label> <input id="q"name="q"class="input_bar"value="<?php echo htmlspecialchars($searchTerm); ?>"placeholder="Search posts by tag(s)"required> <button type="submit">Search</button><br><button onclick='window.location.href="posts"'type="button">Posts</button> <button onclick='window.location.href="/random_image"'type="button">Random</button> <button onclick='window.location.href="/popular_uploads"'type="button">Popular</button> <button onclick='window.location.href="/tags"'type="button">Tags</button></div><div class="news-container"><h1><a href="/static/docs/news/">Site News</a></h1><?php if(!empty($newsTitle)&&!empty($newsContent)): ?><p><strong><?php echo htmlspecialchars($newsDate); ?></strong>:<?php echo nl2br(htmlspecialchars($newsContent)); ?></p><?php else: ?><p><?php echo htmlspecialchars($newsContent); ?></p><?php endif; ?><a href="/static/docs/news/">Previous News</a></div><div class="search-container"><div class="current-uploads"><p>Now serving: <strong><?php echo $postCount; ?></strong>uploads</p></div></div><br><br><br><br></main><div id="cookie-consent"><div class="cookie-content"><h2>We use cookies!</h2><p>We use cookies to improve your experience on our site. By browsing, you agree to our use of cookies.</p><p>Also by uploading and accessing this Web Site you agree to the <a href="/assets/docs/Terms of Use">Terms of Use</a> and <a href="/assets/docs/Privacy Policy">Privacy Policy</a>. If you agree to these terms and conditions click the I Agree button</p><p>This site is SFW, but we recommend users be 13+ as some content may be considered questionable. However, we do not allow NSFW content at this time.</p><div><label><input id="essentialCookies"type="checkbox"checked disabled> Essential cookies</label><br><label><input id="analyticsCookies"type="checkbox"checked> Analytics cookies</label><br><label><input id="marketingCookies"type="checkbox"> Marketing cookies</label> <label><br><input id="terms"type="checkbox"checked disabled> I Agree to the Terms of Use and Privacy Policy and I am 13+</label></div><button onclick="acceptCookies()">I Agree</button></div></div><?php include('includes/version.php'); ?><footer><p>© 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a href="/assets/docs/version"class="link"><?php echo htmlspecialchars($version); ?></a></p><p>Current Server Time:<p>Current Server Time: <strong id="serverTime"><?php echo date('l, F j, Y - h:i:s A'); ?>(CST/CDT)</strong></p><a href="/assets/docs/Privacy Policy"class="link">Privacy Policy</a> | <a href="/assets/docs/Terms of Use"class="link">Terms of Use</a> | <a href="/assets/docs/Code Of Conduct"class="link">Code of Conduct</a> | <a href="/assets/docs/content_moderation"class="link">Content Moderation</a> | <a href="/assets/docs/dmca_policy"class="link">DMCA</a></footer><script>document.addEventListener("DOMContentLoaded", function () {
    function fetchNotifications() {
        fetch('../api/fetch_notifications')
            .then(response => response.json())
            .then(data => {
                let notificationCount = document.getElementById('notification-count');
                let notificationList = document.getElementById('notification-list');
                notificationList.innerHTML = '';

                if (data.length > 0) {
                    notificationCount.textContent = data.length;
                    data.forEach(notification => {
                        let div = document.createElement('div');
                        div.classList.add('notification-item');
                        div.innerHTML = `<a href="/you/notifications">${notification.message}</a>`;
                        notificationList.appendChild(div);
                    });
                } else {
                    notificationCount.textContent = "0";
                    notificationList.innerHTML = "<p>No new notifications</p>";
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    fetchNotifications();

    setInterval(fetchNotifications, 10000);

    document.getElementById('notification-icon').addEventListener('click', function () {
        let notificationList = document.getElementById('notification-list');
        notificationList.style.display = notificationList.style.display === 'none' ? 'block' : 'none';
    });
});</script><script>function refreshNotifications() {
    fetch('/api/fetch_notifications')
        .then(response => response.text())
        .then(data => {
            document.getElementById('notification-list').innerHTML = data;
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

setInterval(refreshNotifications, 10000);</script><script>if (!localStorage.getItem('cookieConsent')) {
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('cookie-consent').style.display = 'flex';
    });
}

function acceptCookies() {
    const consentData = {
        essential: true,
        analytics: true,
        marketing: false
    };

    localStorage.setItem('cookieConsent', JSON.stringify(consentData));
    document.getElementById('cookie-consent').style.display = 'none';
}</script></body></html>