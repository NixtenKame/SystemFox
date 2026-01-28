<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
include_once '../includes/header.php';
$fanart = [
    [
        'title' => 'Well Hello There',
        'image' => 'https://nixten.ddns.net:9001/data/assets/index/fan-art/114ae387db40bf9f1cb615a7b1ec1baa339f0bd0ec4cd1d702a4da467e807259.png',
        'artist' => 'DI_N_K0',
        'artist_url' => 'https://l3v14th4n.straw.page/',
        'featured' => true
    ],
    [
        'title' => 'Flying High',
        'image' => 'https://nixten.ddns.net:9001/data/assets/index/fan-art/facf5b3f3d413b7414429cca2ef3b2ce97c34f45.png',
        'artist' => 'Barley',
        'artist_url' => 'https://nixten.ddns.net/user/DoodlePaw',
        'featured' => false
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fan Art - FluffFox</title>
    <link rel="stylesheet" href="/public/css/styles.css">

    <style>
        .fanart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .fanart-card {
            background: #121212;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.35);
            transition: transform 0.2s ease;
        }

        .fanart-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .fanart-info {
            padding: 1rem;
        }

        .fanart-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .fanart-artist {
            font-size: 0.9rem;
            opacity: 0.85;
            margin-top: 0.25rem;
        }

        .fanart-tags {
            margin-top: 0.5rem;
        }
        .featured-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #1e90ff;
            color: #fff;
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
            border-radius: 6px;
            font-weight: bold;
            z-index: 10;
        }

        .fanart-wrapper {
            position: relative;
        }
    </style>
</head>

<body>
<nav>
    <?php include_once '../includes/nav.php'; ?>
</nav>

<main>
    <h1>Fan Art</h1>
    <p>Thank you all to the artists who have worked really hard on these fan art pieces as a honor I decided to create a dedicated web page for these wonderful art pieces of my fursona.</p>
    <p>If you have fan art you would like to share, please contact me through discord or email.</p>
    <a href="mailto:nixtenkame@gmail.com">Email Me</a>
    <p><a href="https://discordapp.com/users/881220034431713282">Discord</a> | Note if you are contacting me through discord please include "FluffFox Fan Art" in the message with the completed artwork (I WILL NOT ACCEPT ANY FAN ART THAT I HAVE TO PAY FOR AS THAT IS NOT FAIR).</p>

    <section class="fanart-grid">
        <?php foreach ($fanart as $art): ?>
            <article class="fanart-card">
                <div class="fanart-wrapper">
                    <?php if ($art['featured']): ?>
                        <div class="featured-badge">FEATURED</div>
                    <?php endif; ?>

                    <img src="<?= htmlspecialchars($art['image']) ?>" alt="<?= htmlspecialchars($art['title']) ?>">
                </div>

                <div class="fanart-info">
                    <div class="fanart-title">
                        <?= htmlspecialchars($art['title']) ?>
                    </div>

                    <div class="fanart-artist">
                        Art by
                        <?php if ($art['artist_url']): ?>
                            <a href="<?= htmlspecialchars($art['artist_url']) ?>" target="_blank" class="link">
                                <?= htmlspecialchars($art['artist']) ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($art['artist']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php include '../includes/version.php'; ?>

<?php include('../includes/version.php'); ?>
<footer>
    <p>&copy; 2026 FluffFox. All Rights Reserved.
    <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a></p>
</footer>
</body>
</html>