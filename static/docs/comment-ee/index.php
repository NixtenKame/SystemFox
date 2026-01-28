<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once('../../../includes/header.php');


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Design Examples</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <link rel="stylesheet" href="/posts/assets/comment-designs.css">
    <link rel="stylesheet" href="assets/style.css">
    
    <style>
        .code-block {
            position: relative;
            border-radius: 6px;
            font-family: Consolas, monospace;
            overflow-x: auto;
            padding: 10px;
            margin: 10px 0;
        }

        .code-block pre {
            margin: 0;
        }

        .copy-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            background: blue;
            border: none;
            color: #ccc;
            font-size: 12px;
            padding: 3px 6px;
            border-radius: 4px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .copy-btn:hover {
            opacity: 1;
        }
    </style>
</head>
<body>

    <nav><?php include_once '../../../includes/nav.php'; ?></nav>
    <?php include_once '../../../includes/site-notice.php'; ?>

    <div class="content">
        <h1>Comment Design Examples</h1>
        <p>Here are several text effects you can use in your comments. Follow the instructions under each example to copy and paste the correct code into the comment editor.</p>

        <h2>1. Rainbow Text</h2>
        <p class="rainbow">This is rainbow gradient text.</p>
        <ol>
            <li>Open the comment editor and click the <strong>code button</strong>.</li>
            <li>Paste this code:</li>
        </ol>
        <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>
            <pre><code>&lt;span class="rainbow"&gt;Your rainbow text here&lt;/span&gt;</code></pre>
        </div>
        <ol start="3">
            <li>Save, then post your comment. Refresh if needed to see the effect.</li>
        </ol>

        <!-- 🔥 Fire -->
        <h2>2. Fire Glow</h2>
        <p class="fire">This text flickers like fire.</p>
        <ol>
            <li>Click the code button in the editor.</li>
            <li>Paste this code:</li>
        </ol>
        <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>
            <pre><code>&lt;span class="fire"&gt;Your fire text here&lt;/span&gt;</code></pre>
        </div>
        <ol start="3">
            <li>Save and post your comment. The glow will animate automatically.</li>
        </ol>

        <!-- 💧 Water -->
        <h2>3. Water Shimmer</h2>
        <p class="water">This text flows like water.</p>
        <ol>
            <li>Click the code button in the editor.</li>
            <li>Paste this code:</li>
        </ol>
        <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>
            <pre><code>&lt;span class="water"&gt;Your water text here&lt;/span&gt;</code></pre>
        </div>
        <ol start="3">
            <li>Save and post. The shimmer will move across your text.</li>
        </ol>

        <!-- 🎆 Neon -->
        <h2>4. Neon Glow</h2>
        <p class="neon">This is glowing neon text.</p>
        <ol>
            <li>Click the code button in the editor.</li>
            <li>Paste this code:</li>
        </ol>
        <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>
            <pre><code>&lt;span class="neon"&gt;Your neon text here&lt;/span&gt;</code></pre>
        </div>
        <ol start="3">
            <li>Save, post, and your text will pulse with a neon glow.</li>
        </ol>

        <!-- 🌌 Galaxy -->
        <h2>5. Galaxy Effect</h2>
        <p class="galaxy">This is galaxy shifting text.</p>
        <ol>
            <li>Click the code button in the editor.</li>
            <li>Paste this code:</li>
        </ol>
        <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>
            <pre><code>&lt;span class="galaxy"&gt;Your galaxy text here&lt;/span&gt;</code></pre>
        </div>
        <ol start="3">
            <li>Save, post, and your text will cycle through a cosmic gradient.</li>
        </ol>

        <!-- 🖤 Glitch -->
        <h2>6. Glitchy Cyberpunk</h2>
        <p class="glitch" data-text="Glitch Example">Glitch Example</p>
        <ol>
            <li>Click the code button in the editor.</li>
            <li>Paste this code (be sure to match the <code>data-text</code> with the text inside the span):</li>
        </ol>
        <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>
            <pre><code>&lt;span class="glitch" data-text="Glitch Example"&gt;Glitch Example&lt;/span&gt;</code></pre>
        </div>
        <ol start="3">
            <li>Save and post. The text will “glitch” with shifting colors and distortions.</li>
        </ol>

        <!-- ✨ Sparkle -->
        <h2>7. Sparkle</h2>
        <p class="sparkle">This text has a sparkle effect ✦</p>
        <ol>
            <li>Click the code button in the editor.</li>
            <li>Paste this code:</li>
        </ol>
        <div class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>
            <pre><code>&lt;span class="sparkle"&gt;Your sparkling text here&lt;/span&gt;</code></pre>
        </div>
        <ol start="3">
            <li>Save, post, and a sparkle ✦ will animate around your text.</li>
        </ol>

    </div>
    <?php include('../../../includes/version.php'); ?>
    <footer>
        <p>&copy; 2026 FluffFox. All Rights Reserved. 
        <a href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
    </footer>

    <script>
        function copyCode(button) {
            const code = button.parentElement.querySelector("code").innerText;
            navigator.clipboard.writeText(code).then(() => {
                button.textContent = "Copied!";
                setTimeout(() => (button.textContent = "Copy"), 2000);
            });
        }
    </script>
</body>
</html>