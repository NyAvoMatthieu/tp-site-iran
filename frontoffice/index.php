<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/seo.php';

$ctx = fo_context();
$basePath = $ctx['basePath'];
$baseUrl = $ctx['baseUrl'];
$homeHref = $ctx['homeHref'];

$pdo = getDB();

$tagSlug = trim((string) ($_GET['tag_slug'] ?? ''));
$typeSlug = trim((string) ($_GET['type_slug'] ?? ''));

if ($tagSlug === '' && isset($_GET['tag'])) {
    $tagSlug = fo_slugify((string) $_GET['tag']);
}
if ($typeSlug === '' && isset($_GET['type'])) {
    $typeSlug = fo_slugify((string) $_GET['type']);
}

$types = $pdo->query("SELECT id, libelle FROM type_contenu ORDER BY libelle")->fetchAll();
$tagsAll = $pdo->query("SELECT id, libelle FROM tag ORDER BY libelle")->fetchAll();

$typeBySlug = [];
foreach ($types as $t) {
    $typeBySlug[fo_slugify((string) $t['libelle'])] = $t;
}

$tagBySlug = [];
foreach ($tagsAll as $t) {
    $tagBySlug[fo_slugify((string) $t['libelle'])] = $t;
}

$where = [];
$params = [];
$joins = [];
$activeLabel = '';

if ($tagSlug !== '' && isset($tagBySlug[$tagSlug])) {
    $joins[] = 'JOIN contenu_tag fct ON fct.id_contenu = c.id';
    $where[] = 'fct.id_tag = :tag_id';
    $params[':tag_id'] = (int) $tagBySlug[$tagSlug]['id'];
    $activeLabel = (string) $tagBySlug[$tagSlug]['libelle'];
}

if ($typeSlug !== '' && isset($typeBySlug[$typeSlug])) {
    $where[] = 'c.id_type = :type_id';
    $params[':type_id'] = (int) $typeBySlug[$typeSlug]['id'];
    $activeLabel = (string) $typeBySlug[$typeSlug]['libelle'];
}

$sql = "SELECT DISTINCT c.id, c.titre, c.slug, c.details, c.created_at,
               c.meta_title, c.meta_description, c.keywords,
               tc.libelle AS type_label,
               (SELECT url FROM image WHERE id_contenu = c.id LIMIT 1) AS cover_url
        FROM contenu c
        LEFT JOIN type_contenu tc ON tc.id = c.id_type\n";

if (!empty($joins)) {
    $sql .= implode("\n", $joins) . "\n";
}
if (!empty($where)) {
    $sql .= 'WHERE ' . implode(' AND ', $where) . "\n";
}
$sql .= 'ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

$articles = array_map(static function (array $article) use ($basePath): array {
    $url = (string) ($article['cover_url'] ?? '');
    if ($url !== '' && !preg_match('#^(?:https?:)?//#i', $url) && strpos($url, '/') !== 0) {
        $article['cover_url'] = fo_asset_url($basePath, $url);
    }
    return $article;
}, $articles);

$tagStmt = $pdo->query(
    "SELECT ct.id_contenu, t.libelle
     FROM contenu_tag ct
     JOIN tag t ON t.id = ct.id_tag"
);
$tagsRaw = $tagStmt->fetchAll();

$tagsByArticle = [];
foreach ($tagsRaw as $row) {
    $tagsByArticle[$row['id_contenu']][] = $row['libelle'];
}

if ($tagSlug !== '' && isset($tagBySlug[$tagSlug])) {
    $pageTitle = fo_meta_trim('Tag: ' . $activeLabel . ' - Iran War News Coverage', 60);
    $pageDescription = fo_meta_trim('All articles tagged ' . $activeLabel . '. Follow analysis, updates and reports from IranWatch.', 160);
    $heroTitle = 'Tag: ' . $activeLabel;
    $heroSubtitle = 'Latest stories and analysis related to ' . $activeLabel . '.';
    $canonical = $baseUrl . '/tag/' . rawurlencode($tagSlug);
} elseif ($typeSlug !== '' && isset($typeBySlug[$typeSlug])) {
    $pageTitle = fo_meta_trim($activeLabel . ' - Iran War News & Reports', 60);
    $pageDescription = fo_meta_trim('Browse ' . $activeLabel . ' articles, updates and context from IranWatch.', 160);
    $heroTitle = $activeLabel;
    $heroSubtitle = 'Curated coverage for the category ' . $activeLabel . '.';
    $canonical = $baseUrl . '/categorie/' . rawurlencode($typeSlug);
} else {
    $pageTitle = fo_meta_trim('Iran War News - Latest Updates & Reports', 60);
    $pageDescription = fo_meta_trim('Follow the latest Iran war news, analysis and reports with live coverage from IranWatch.', 160);
    $heroTitle = 'Iran War News';
    $heroSubtitle = 'Iran war news, real-time reports and expert analysis from the front line.';
    $canonical = $baseUrl . '/';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

    <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">

    <?php fo_render_analytics(); ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(fo_asset_url($basePath, 'css/style.min.css'), ENT_QUOTES, 'UTF-8') ?>">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "IranWatch",
      "url": <?= json_encode($baseUrl . '/') ?>
    }
    </script>
</head>
<body>
<header class="site-header" role="banner">
    <div class="container header-inner">
        <div class="logo">
            <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="logo-link" aria-label="IranWatch homepage">
                <span class="logo-icon" aria-hidden="true">⚡</span>
                <span class="logo-text">IranWatch</span>
            </a>
        </div>
        <nav class="main-nav" aria-label="Primary navigation">
            <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="nav-link<?= ($tagSlug === '' && $typeSlug === '') ? ' active' : '' ?>">Home</a>
            <?php foreach ($types as $type): ?>
                <?php $slug = fo_slugify((string) $type['libelle']); ?>
                <a href="<?= htmlspecialchars($homeHref . 'categorie/' . rawurlencode($slug), ENT_QUOTES, 'UTF-8') ?>" class="nav-link<?= ($typeSlug === $slug) ? ' active' : '' ?>">
                    <?= htmlspecialchars((string) $type['libelle'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="header-badge">
            <span class="live-dot" aria-hidden="true"></span>
            <span>Live Coverage</span>
        </div>
    </div>
</header>

<nav class="breadcrumb-nav container" aria-label="Breadcrumb">
    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" itemprop="item"><span itemprop="name">Home</span></a>
            <meta itemprop="position" content="1">
        </li>
        <?php if ($activeLabel !== ''): ?>
            <li aria-hidden="true" class="breadcrumb-sep">›</li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span itemprop="name"><?= htmlspecialchars($activeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <meta itemprop="position" content="2">
            </li>
        <?php endif; ?>
    </ol>
</nav>

<section class="hero" aria-labelledby="hero-title">
    <div class="container">
        <p class="hero-kicker">Breaking Coverage</p>
        <h1 id="hero-title"><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="hero-subtitle"><?= htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<main id="main-content" class="container main-grid" role="main">
    <section class="articles-section" aria-label="Latest articles">
        <div class="section-header">
            <h2 class="section-title">Latest Articles</h2>
            <span class="article-count"><?= count($articles) ?> stories</span>
        </div>

        <?php if (empty($articles)): ?>
            <div class="empty-state" role="status"><p>No articles published for this section yet.</p></div>
        <?php else: ?>
            <div class="article-list" role="list">
                <?php foreach ($articles as $article): ?>
                    <?php
                    $excerpt = fo_meta_trim((string) $article['details'], 170);
                    $dateISO = date('c', strtotime((string) $article['created_at']));
                    $date = date('F j, Y', strtotime((string) $article['created_at']));
                    $href = $homeHref . 'article/' . rawurlencode((string) $article['slug']);
                    $articleTags = $tagsByArticle[$article['id']] ?? [];
                    ?>
                    <article class="article-card" role="listitem">
                        <?php if (!empty($article['cover_url'])): ?>
                            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="card-image-link" aria-label="Read article <?= htmlspecialchars((string) $article['titre'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="card-image">
                                    <img src="<?= htmlspecialchars((string) $article['cover_url'], ENT_QUOTES, 'UTF-8') ?>"
                                         alt="Cover image for article <?= htmlspecialchars((string) $article['titre'], ENT_QUOTES, 'UTF-8') ?>"
                                         loading="lazy" width="400" height="225">
                                </div>
                            </a>
                        <?php endif; ?>

                        <div class="card-body">
                            <?php if (!empty($article['type_label'])): ?>
                                <?php $typeCardSlug = fo_slugify((string) $article['type_label']); ?>
                                <a class="card-type" href="<?= htmlspecialchars($homeHref . 'categorie/' . rawurlencode($typeCardSlug), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) $article['type_label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endif; ?>

                            <h2 class="card-title"><a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $article['titre'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                            <p class="card-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>

                            <footer class="card-footer">
                                <time class="card-date" datetime="<?= $dateISO ?>"><?= $date ?></time>
                                <?php if (!empty($articleTags)): ?>
                                    <ul class="card-tags" aria-label="Tags for this article">
                                        <?php foreach (array_slice($articleTags, 0, 4) as $tag): ?>
                                            <?php $tagUrl = $homeHref . 'tag/' . rawurlencode(fo_slugify((string) $tag)); ?>
                                            <li><a class="tag tag--link" href="<?= htmlspecialchars($tagUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8') ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="btn-read-more" aria-label="Read full article <?= htmlspecialchars((string) $article['titre'], ENT_QUOTES, 'UTF-8') ?>">Read full analysis</a>
                            </footer>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <aside class="sidebar" aria-label="Sidebar">
        <div class="sidebar-widget">
            <h2 class="widget-title">About IranWatch</h2>
            <p class="widget-text">Independent reporting, structured context and daily updates about the Iran conflict.</p>
        </div>
        <?php if (!empty($tagsAll)): ?>
            <div class="sidebar-widget">
                <h2 class="widget-title">Browse by tags</h2>
                <ul class="tag-cloud" aria-label="Tag pages">
                    <?php foreach ($tagsAll as $tag): ?>
                        <?php $tagSlugLocal = fo_slugify((string) $tag['libelle']); ?>
                        <li><a href="<?= htmlspecialchars($homeHref . 'tag/' . rawurlencode($tagSlugLocal), ENT_QUOTES, 'UTF-8') ?>" class="tag tag--link"><?= htmlspecialchars((string) $tag['libelle'], ENT_QUOTES, 'UTF-8') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </aside>
</main>

<footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
        <p class="footer-brand">IranWatch</p>
        <p class="footer-copy">&copy; <?= date('Y') ?> IranWatch. All rights reserved.</p>
        <nav class="footer-nav" aria-label="Footer navigation">
            <a href="<?= htmlspecialchars($homeHref . 'sitemap.xml', ENT_QUOTES, 'UTF-8') ?>" class="footer-link">Sitemap</a>
            <a href="<?= htmlspecialchars($homeHref . 'robots.txt', ENT_QUOTES, 'UTF-8') ?>" class="footer-link">Robots</a>
        </nav>
    </div>
</footer>

<script src="<?= htmlspecialchars(fo_asset_url($basePath, 'js/script.min.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
