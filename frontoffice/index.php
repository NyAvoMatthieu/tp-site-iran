<?php
require_once __DIR__ . '/db.php';

$rawBasePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath    = ($rawBasePath === '/' || $rawBasePath === '.') ? '' : rtrim($rawBasePath, '/');
$scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl     = $scheme . '://' . $host . $basePath;
$homeHref    = ($basePath === '') ? '/' : $basePath . '/';

$pdo = getDB();

/* ── Fetch all articles ordered by newest first ── */
$stmt = $pdo->query(
    "SELECT c.id, c.titre, c.slug, c.details, c.created_at,
            tc.libelle AS type_label,
            (SELECT url FROM image WHERE id_contenu = c.id LIMIT 1) AS cover_url
     FROM   contenu c
     LEFT JOIN type_contenu tc ON tc.id = c.id_type
     ORDER  BY c.created_at DESC"
);
$articles = $stmt->fetchAll();
$articles = array_map(static function (array $article) use ($basePath): array {
    $url = (string) ($article['cover_url'] ?? '');
    if ($url !== '' && !preg_match('#^(?:https?:)?//#i', $url) && strpos($url, '/') !== 0) {
        $article['cover_url'] = (($basePath === '') ? '/' : $basePath . '/') . ltrim($url, '/');
    }
    return $article;
}, $articles);

/* ── Tags per article ── */
$tagStmt = $pdo->query(
    "SELECT ct.id_contenu, t.libelle
     FROM   contenu_tag ct
     JOIN   tag t ON t.id = ct.id_tag"
);
$tagsRaw = $tagStmt->fetchAll();

$tagsByArticle = [];
foreach ($tagsRaw as $row) {
    $tagsByArticle[$row['id_contenu']][] = $row['libelle'];
}

$pageTitle       = 'Iran War News – Latest Updates & Reports';
$pageDescription = 'Follow the latest breaking news, analysis and reports on the Iran conflict. Stay informed with real-time coverage.';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:type" content="website">

    <!-- Canonical -->
    <link rel="canonical" href="<?= htmlspecialchars($baseUrl . '/') ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= htmlspecialchars((($basePath === '') ? '' : $basePath) . '/css/style.css') ?>">
</head>

<body>

    <!-- ═══════════════════ HEADER ═══════════════════ -->
    <header class="site-header" role="banner">
        <div class="container header-inner">
            <div class="logo">
                <span class="logo-icon" aria-hidden="true">⚡</span>
                <span class="logo-text">IranWatch</span>
            </div>
            <nav class="main-nav" aria-label="Primary navigation">
                <a href="<?= htmlspecialchars($homeHref) ?>" class="nav-link active" aria-current="page">Home</a>
                <a href="<?= htmlspecialchars($homeHref . '?type=news') ?>" class="nav-link">News</a>
                <a href="<?= htmlspecialchars($homeHref . '?type=analysis') ?>" class="nav-link">Analysis</a>
                <a href="<?= htmlspecialchars($homeHref . '?type=reports') ?>" class="nav-link">Reports</a>
            </nav>
            <div class="header-badge">
                <span class="live-dot" aria-hidden="true"></span>
                <span>Live Coverage</span>
            </div>
        </div>
    </header>

    <!-- ═══════════════════ HERO ═══════════════════ -->
    <section class="hero" aria-labelledby="hero-title">
        <div class="container">
            <p class="hero-kicker">Breaking Coverage</p>
            <h1 id="hero-title">Iran War News</h1>
            <p class="hero-subtitle">Real-time reports, in-depth analysis, and expert perspectives on the Iran conflict.</p>
        </div>
    </section>

    <!-- ═══════════════════ MAIN CONTENT ═══════════════════ -->
    <main id="main-content" class="container main-grid" role="main">

        <!-- ARTICLES LIST -->
        <section class="articles-section" aria-label="Latest articles">
            <div class="section-header">
                <h2 class="section-title">Latest Articles</h2>
                <span class="article-count"><?= count($articles) ?> stories</span>
            </div>

            <?php if (empty($articles)): ?>
                <div class="empty-state" role="status">
                    <p>No articles published yet. Check back soon.</p>
                </div>
            <?php else: ?>
                <div class="article-list" role="list">
                    <?php foreach ($articles as $article):
                        $excerpt  = mb_substr(strip_tags($article['details']), 0, 150);
                        if (mb_strlen(strip_tags($article['details'])) > 150) $excerpt .= '…';
                        $tags     = $tagsByArticle[$article['id']] ?? [];
                        $date     = date('F j, Y', strtotime($article['created_at']));
                        $dateISO  = date('c', strtotime($article['created_at']));
                        $href     = 'article/' . urlencode($article['slug']);
                    ?>
                        <article class="article-card" role="listitem">
                            <?php if (!empty($article['cover_url'])): ?>
                                <a href="<?= htmlspecialchars($href) ?>" class="card-image-link" tabindex="-1" aria-hidden="true">
                                    <div class="card-image">
                                        <img src="<?= htmlspecialchars($article['cover_url']) ?>"
                                            alt="Cover image for: <?= htmlspecialchars($article['titre']) ?>"
                                            loading="lazy"
                                            width="400" height="225">
                                    </div>
                                </a>
                            <?php endif; ?>

                            <div class="card-body">
                                <?php if (!empty($article['type_label'])): ?>
                                    <span class="card-type"><?= htmlspecialchars($article['type_label']) ?></span>
                                <?php endif; ?>

                                <h2 class="card-title">
                                    <a href="<?= htmlspecialchars($href) ?>">
                                        <?= htmlspecialchars($article['titre']) ?>
                                    </a>
                                </h2>

                                <p class="card-excerpt"><?= htmlspecialchars($excerpt) ?></p>

                                <footer class="card-footer">
                                    <time class="card-date" datetime="<?= $dateISO ?>">
                                        <?= $date ?>
                                    </time>

                                    <?php if (!empty($tags)): ?>
                                        <ul class="card-tags" aria-label="Tags for this article">
                                            <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
                                                <li><span class="tag"><?= htmlspecialchars($tag) ?></span></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <a href="<?= htmlspecialchars($href) ?>"
                                        class="btn-read-more"
                                        aria-label="Read full article: <?= htmlspecialchars($article['titre']) ?>">
                                        Read More →
                                    </a>
                                </footer>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- SIDEBAR -->
        <aside class="sidebar" aria-label="Sidebar">
            <div class="sidebar-widget">
                <h3 class="widget-title">About This Site</h3>
                <p class="widget-text">IranWatch delivers independent, fact-based reporting on the Iran war — updated around the clock.</p>
            </div>

            <?php
            /* Popular tags */
            $tagCountStmt = $pdo->query(
                "SELECT t.libelle, COUNT(*) AS cnt
             FROM   tag t
             JOIN   contenu_tag ct ON ct.id_tag = t.id
             GROUP  BY t.libelle
             ORDER  BY cnt DESC
             LIMIT  15"
            );
            $popularTags = $tagCountStmt->fetchAll();
            ?>
            <?php if (!empty($popularTags)): ?>
                <div class="sidebar-widget">
                    <h3 class="widget-title">Popular Tags</h3>
                    <ul class="tag-cloud" aria-label="Popular tags">
                        <?php foreach ($popularTags as $pt): ?>
                            <li>
                                <a href="<?= htmlspecialchars($homeHref . '?tag=' . urlencode($pt['libelle'])) ?>" class="tag tag--link">
                                    <?= htmlspecialchars($pt['libelle']) ?>
                                    <sup><?= (int)$pt['cnt'] ?></sup>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>

    </main>

    <!-- ═══════════════════ FOOTER ═══════════════════ -->
    <footer class="site-footer" role="contentinfo">
        <div class="container footer-inner">
            <p class="footer-brand">IranWatch</p>
            <p class="footer-copy">&copy; <?= date('Y') ?> IranWatch. All rights reserved.</p>
            <nav class="footer-nav" aria-label="Footer navigation">
                <a href="<?= htmlspecialchars($homeHref . 'privacy') ?>" class="footer-link">Privacy</a>
                <a href="<?= htmlspecialchars($homeHref . 'contact') ?>" class="footer-link">Contact</a>
            </nav>
        </div>
    </footer>

    <script src="<?= htmlspecialchars((($basePath === '') ? '' : $basePath) . '/js/script.js') ?>" defer></script>
</body>

</html>