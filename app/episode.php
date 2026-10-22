<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$categories = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = $pdo->prepare(
    "SELECT e.id, e.title, e.description, e.audio_path, e.cover_path,
            e.duration_seconds, e.published_at, c.name AS category_name
     FROM episodes e
     LEFT JOIN categories c ON c.id = e.category_id
     WHERE e.id = :id AND e.status = 'published'
     LIMIT 1"
);
$statement->execute(['id' => $id ?: 0]);
$episode = $statement->fetch();

if (!$episode) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $episode ? h($episode['title']) : 'Không tìm thấy tập' ?> · Podcast Library</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/" aria-label="Podcast Library - trang chủ">
        <span class="brand-mark" aria-hidden="true">▶</span>
        <span>Podcast Library</span>
    </a>
    <a class="back-link" href="/">← Tất cả tập</a>
    <details class="mobile-menu">
        <summary aria-label="Mở danh mục">☰ <span>Menu</span></summary>
        <nav class="mobile-menu-panel" aria-label="Danh mục podcast">
            <a href="/">Tất cả tập</a>
            <?php foreach ($categories as $item): ?>
                <a href="/?category=<?= rawurlencode($item['slug']) ?>"><?= h($item['name']) ?></a>
            <?php endforeach; ?>
        </nav>
    </details>
</header>

<div class="site-layout">
    <aside class="sidebar">
        <p class="sidebar-heading">THƯ VIỆN</p>
        <nav class="category-nav" aria-label="Danh mục podcast">
            <a href="/">Tất cả tập</a>
            <?php foreach ($categories as $item): ?>
                <a href="/?category=<?= rawurlencode($item['slug']) ?>"><?= h($item['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <p class="sidebar-note">Đề tài 37<br>Website Podcast / Media Library</p>
    </aside>

    <main class="page detail-page">
        <?php if (!$episode): ?>
            <p class="eyebrow">PODCAST / AUDIO</p>
            <h1>Không tìm thấy tập này</h1>
            <p class="description">Tập có thể chưa được xuất bản hoặc đường dẫn không còn hợp lệ.</p>
            <a class="details" href="/">← Quay lại thư viện</a>
        <?php else: ?>
            <p class="eyebrow"><?= h($episode['category_name'] ?: 'Podcast / Audio') ?></p>
            <h1><?= h($episode['title']) ?></h1>
            <?php if ($episode['published_at']): ?>
                <p class="detail-date">Ngày đăng: <?= h(date('d/m/Y', strtotime($episode['published_at']))) ?></p>
            <?php endif; ?>
            <?php if ($episode['cover_path']): ?>
                <img class="detail-cover" src="<?= h($episode['cover_path']) ?>" alt="Ảnh bìa <?= h($episode['title']) ?>">
            <?php endif; ?>
            <audio class="detail-player" controls preload="metadata">
                <source src="<?= h($episode['audio_path']) ?>"<?= strtolower(pathinfo($episode['audio_path'], PATHINFO_EXTENSION)) === 'opus' ? ' type="audio/ogg; codecs=opus"' : '' ?>>
                Trình duyệt của bạn không hỗ trợ phát audio.
            </audio>
            <div class="description"><?= nl2br(h($episode['description'])) ?></div>
        <?php endif; ?>
        <footer class="site-footer">Podcast Library · Đề tài 37</footer>
    </main>
</div>
</body>
</html>
