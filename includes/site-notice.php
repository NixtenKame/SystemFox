<?php
$latestNews = $db->query("SELECT id, title, content, author, created_at FROM news ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

if ($latestNews):
    $news_id = (int)$latestNews['id'];
    $news_title = htmlspecialchars($latestNews['title']);
    $news_content = nl2br(htmlspecialchars($latestNews['content']));
    $news_author = htmlspecialchars($latestNews['author']);
    $news_date = date("F j, Y", strtotime($latestNews['created_at']));
?>

<script src="https://nixten.ddns.net:3001/js/v<?php echo $version; ?>/site-notice.js"></script>
<div class="ui-state-highlight site-notice" id="news" data-id="<?= $news_id ?>" style="display: none;">
  <a href="#" role="button" id="news-dismiss" title="Dismiss">
    <i class="fas fa-times"></i>
  </a>
  <h6 id="news-header">
    News: <?= $news_date ?>
    <a role="button" id="news-show">Show</a>
  </h6>
  <div id="news-body" class="dtext-container" style="display: none; animation: fadeIn 0.3s ease-in-out;">
    <div class="styled-dtext">
      <h3><?= $news_title ?></h3>
      <p><?= $news_content ?></p>
      <small>Posted by <?= $news_author ?> on <?= $news_date ?></small>
    </div>
  </div>
</div>

<button id="news-restore" style="position: fixed; bottom: 10px; right: 10px; font-size: 10px; opacity: 0.3; z-index: 9999; display: none;">News</button>

<div class="news-tutorial" id="news-tutorial">
  <strong>Tip:</strong> You can dismiss the news using the <i class="fas fa-times"></i> icon and bring it back with the small “News” button below.<br><br>
  <button id="close-news-tutorial" style="margin-top: 5px; font-size: 11px;">Got it</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const newsElement = document.getElementById('news');
    const newsId = newsElement.dataset.id;
    const dismissedId = localStorage.getItem('dismissedNewsId');
    const restoreBtn = document.getElementById('news-restore');
    const tutorialSeen = localStorage.getItem('newsTutorialSeen');
    const tutorialBox = document.getElementById('news-tutorial');

    if (dismissedId !== newsId) {
        newsElement.style.display = 'block';
    } else {
        restoreBtn.style.display = 'block';
    }

    if (!tutorialSeen) {
        tutorialBox.style.display = 'block';
    }

    document.getElementById('news-dismiss').addEventListener('click', function (e) {
        e.preventDefault();
        localStorage.setItem('dismissedNewsId', newsId);
        newsElement.style.display = 'none';
        restoreBtn.style.display = 'block';
    });

    document.getElementById('news-show').addEventListener('click', function (e) {
        e.preventDefault();
        const body = document.getElementById('news-body');
        body.style.display = body.style.display === 'none' ? 'block' : 'none';
    });

    restoreBtn.addEventListener('click', function () {
        localStorage.removeItem('dismissedNewsId');
        newsElement.style.display = 'block';
        restoreBtn.style.display = 'none';
    });

    document.getElementById('close-news-tutorial').addEventListener('click', function () {
        tutorialBox.style.display = 'none';
        localStorage.setItem('newsTutorialSeen', '1');
    });
});
</script>

<?php endif; ?>