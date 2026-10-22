<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$categories = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();
$category = trim((string) ($_GET['category'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$conditions = ["e.status = 'published'"];
$parameters = [];

if ($category !== '') {
    $conditions[] = 'c.slug = :category';
    $parameters['category'] = $category;
}

if ($search !== '') {
    $conditions[] = '(e.title LIKE :title_query OR e.description LIKE :description_query)';
    $parameters['title_query'] = '%' . $search . '%';
    $parameters['description_query'] = '%' . $search . '%';
}

$sql = 'SELECT e.id, e.title, e.slug, e.description, e.audio_path, e.cover_path,
               e.duration_seconds, e.published_at, c.name AS category_name
        FROM episodes e
        LEFT JOIN categories c ON c.id = e.category_id
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY COALESCE(e.published_at, e.created_at) DESC
        LIMIT 50';
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$episodes = $statement->fetchAll();

function categoryNavigation(array $categories, string $selected): void
{
    ?>
    <a href="/"<?= $selected === '' ? ' aria-current="page"' : '' ?>>Tất cả tập</a>
    <?php foreach ($categories as $item): ?>
        <a href="/?category=<?= rawurlencode($item['slug']) ?>"<?= $selected === $item['slug'] ? ' aria-current="page"' : '' ?>><?= h($item['name']) ?></a>
    <?php endforeach; ?>
    <?php
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Podcast / Audio · Podcast Library</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/" aria-label="Podcast Library - trang chủ">
        <span class="brand-mark" aria-hidden="true">▶</span>
        <span>Podcast Library</span>
    </a>
    <form class="top-search" method="get" action="/">
        <?php if ($category !== ''): ?>
            <input type="hidden" name="category" value="<?= h($category) ?>">
        <?php endif; ?>
        <label class="visually-hidden" for="q">Tìm tập podcast</label>
        <input id="q" name="q" value="<?= h($search) ?>" placeholder="Tìm podcast hoặc tập...">
        <button type="submit" aria-label="Tìm kiếm">⌕</button>
    </form>
    <details class="mobile-menu">
        <summary aria-label="Mở danh mục">☰ <span>Menu</span></summary>
        <nav class="mobile-menu-panel" aria-label="Danh mục podcast">
            <?php categoryNavigation($categories, $category); ?>
        </nav>
    </details>
</header>

<div class="site-layout">
    <aside class="sidebar">
        <p class="sidebar-heading">THƯ VIỆN</p>
        <nav class="category-nav" aria-label="Danh mục podcast">
            <?php categoryNavigation($categories, $category); ?>
        </nav>
        <p class="sidebar-note">Đề tài 37<br>Website Podcast / Media Library</p>
    </aside>

    <main class="page">
        <section class="intro">
            <p class="eyebrow">PODCAST / MEDIA LIBRARY</p>
            <h1>Podcast / Audio</h1>
            <p>Nghe và khám phá các tập podcast theo danh mục.</p>
        </section>

        <aside class="library-note">
            <span class="note-icon" aria-hidden="true">♫</span>
            <div>
                <strong>Thư viện audio</strong>
                <p>Dùng ô tìm kiếm hoặc chọn danh mục để tìm tập muốn nghe.</p>
            </div>
        </aside>

        <section class="episode-section" aria-label="Các tập đã xuất bản">
            <div class="section-heading">
                <h2><?= $category !== '' ? 'Tập theo danh mục' : 'Tập mới nhất' ?></h2>
                <span class="result-count"><?= count($episodes) ?> tập</span>
            </div>

            <?php if (!$episodes): ?>
                <p class="empty">Chưa có tập phù hợp. Hãy thêm tập vào thư viện hoặc thử từ khóa khác.</p>
            <?php else: ?>
                <div class="episode-list">
                    <?php foreach ($episodes as $episode): ?>
                        <article class="episode-card">
                            <?php if ($episode['cover_path']): ?>
                                <img class="cover" src="<?= h($episode['cover_path']) ?>" alt="Ảnh bìa <?= h($episode['title']) ?>">
                            <?php else: ?>
                                <div class="cover cover-placeholder" aria-hidden="true">♫</div>
                            <?php endif; ?>
                            <div class="episode-content">
                                <p class="episode-meta">
                                    <span><?= h($episode['category_name'] ?: 'Chưa phân loại') ?></span>
                                    <?php if ($episode['published_at']): ?>
                                        <span><?= h(date('d/m/Y', strtotime($episode['published_at']))) ?></span>
                                   <?php endif; ?>
                                </p>
                                <h3><a href="/episode.php?id=<?= (int) $episode['id'] ?>"><?= h($episode['title']) ?></a></h3>
                                <p class="summary"><?= h($episode['description']) ?></p>
                                <audio controls preload="metadata" aria-label="Phát <?= h($episode['title']) ?>">
                                    <source src="<?= h($episode['audio_path']) ?>"<?= strtolower(pathinfo($episode['audio_path'], PATHINFO_EXTENSION)) === 'opus' ? ' type="audio/ogg; codecs=opus"' : '' ?>>
                                    Trình duyệt của bạn không hỗ trợ phát audio.
                                </audio>
                                <a class="details" href="/episode.php?id=<?= (int) $episode['id'] ?>">Xem chi tiết tập <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <footer class="site-footer">Podcast Library · Đề tài 37</footer>
    </main>
</div>
</body>
</html>
