<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include('../includes/version.php'); 
include_once('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Map - FluffFox</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        ul {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 1.5em;
        }
        h3 {
            font-size: 85%;
        }
        section {
            width: 20em;
        }
        .site-map {
            display: flex;
            flex-flow: row wrap;
            max-width: 80em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav>
        <?php include_once '../includes/nav.php'; ?>
    </nav>
    <?php include_once '../includes/site-notice.php'; ?>

    <main>
        <h2>Site Map</h2>
        <p>Welcome to the FluffFox site map. Here you can quickly navigate through the different sections of the site:</p>
        <div class="site-map">
            <section>
                <h3>Posts</h3>
                <ul>
                    <li><a href="/posts/">Listing</a></li>
                    <li><a href="/upload/new">Upload</a></li>
                    <li><a href="/popular_uploads">Popular Uploads</a></li>
                    <li><a href="">Changes (Comming Soon!)</a></li>
                    <li><a href="">Similar Images Search (Comming Soon!)</a></li>
                    <li><a href="">Deleted Index (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Post Events</h3>
                <ul>
                    <li><a href="">Listing (Comming Soon!)</a></li>
                    <li><a href="">Tag Changes (Comming Soon!)</a></li>
                    <li><a href="">Flags (Comming Soon!)</a></li>
                    <li><a href="">Replacements (Comming Soon!)</a></li>
                </ul>
                <h3>Tools</h3>
                <ul>
                    <li><a href="/static/docs/news/">News Updates</a></li>
                    <li><a href="/static/docs/mascots/">Mascots</a></li>
                    <li><a href="">Source Code (Comming Soon!)</a></li>
                    <li><a href="/static/keyboard_shortcuts">Keyboard Shortcuts (Comming Soon!)</a></li>
                    <li><a href="">API documentatioin (Comming Soon!)</a></li>
                    <li><a href="">Stats (Comming Soon!)</a></li>
                    <li><a href="/assets/docs/Terms%20of%20Use">Terms of Use</a></li>
                    <li><a href="/assets/docs/Privacy Policy">Privacy Policy</a></li>
                    <li><a href="/assets/docs/Code Of Conduct">Code of Conduct</a></li>
                    <li><a href="/db_export/">Database Export</a></li>
                    <li><a href="/static/discord">Discord</a></li>
                    <li><a href="">Help Index (Comming Soon!)</a></li>
                </ul>
            </section>
            <section>
                <h3>Artists</h3>
                <ul>
                    <li><a href="">Listing (Comming Soon!)</a></li>
                    <li><a href="">URLs (Comming Soon!)</a></li>
                    <li><a href="">Avoid Posting Entries (Comming Soon!)</a></li>
                    <li><a href="">Avoid Posting List (Comming Soon!)</a></li>
                    <li><a href="">Changes (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Tags</h3>
                <ul>
                    <li><a href="/tags">Listing</a></li>
                </ul>
                <h3>Notes</h3>
                <ul>
                    <li><a href="">Listing (Comming Soon!)</a></li>
                    <li><a href="">Changes (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Pools</h3>
                <ul>
                    <li><a href="">Listing (Comming Soon!)</a></li>
                    <li><a href="">Changes (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Sets</h3>
                <ul>
                    <li><a href="">Listing (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
            </section>
            <section>
                <h3>Comments</h3>
                <ul>
                    <li><a href="">Listing (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Forum</h3>
                <ul>
                    <li><a href="/forums/forum">Listing</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Wiki</h3>
                <ul>
                    <li><a href="">Listing (Comming Soon!)</a></li>
                    <li><a href="">Changes (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Blips</h3>
                <ul>
                    <li><a href="/forums/forum">Listing</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Other</h3>
                <ul>

                </ul>
            </section>
            <section>
                <h3>Users</h3>
                <ul>
                    <li><a href="/user/">Listing</a></li>
                    <li><a href="">Bans (Comming Soon!)</a></li>
                    <li><a href="">Feedback (Comming Soon!)</a></li>
                    <li><a href="/you/">User Home</a></li>
                    <li><a href="/user/<?php echo htmlspecialchars($_SESSION['username']); ?>">User Profile</a></li>
                    <li><a href="/you/settings">Settings</a></li>
                    <li><a href="">Refresh Counts (Comming Soon!)</a></li>
                    <li><a href="">Help (Comming Soon!)</a></li>
                </ul>
                <h3>Staff</h3>
                <ul>
                    <li><a href="">Upload Whitelist (Comming Soon!)</a></li>
                    <li><a href="">Mod Actions (Comming Soon!)</a></li>
                    <li><a href="">Takedowns (Comming Soon!)</a></li>
                    <li><a href="">Tickets (Comming Soon!)</a></li>
                </ul>
            </section>
        </div>
    </main>

    <?php include('../includes/version.php'); ?>
    <footer>
	<p>&copy; 2026 FluffFox. (nixten.ddns.net) All Rights Reserved. <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>
</body>
</html>