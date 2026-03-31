<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/seo.php';

$ctx = fo_context();
$basePath = $ctx['basePath'];
$baseUrl = $ctx['baseUrl'];
$homeHref = $ctx['homeHref'];

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(400);
    header('Location: ' . $homeHref);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare(
    "SELECT c.id, c.titre, c.slug, c.details, c.created_at, c.updated_at,
            c.meta_title, c.meta_description, c.keywords, c.author_name,
            tc.libelle AS type_label
     FROM contenu c
     LEFT JOIN type_contenu tc ON tc.id = c.id_type
     WHERE c.slug = :slug
     LIMIT 1"
);
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $notFound = true;
    $pageTitle = 'Article not found | IranWatch';
    $metaDesc = 'The requested article does not exist.';
    $canonicalUrl = $baseUrl . '/article/' . rawurlencode($slug);
} else {
    $notFound = false;

    $imgStmt = $pdo->prepare("SELECT url FROM image WHERE id_contenu = :id ORDER BY id");
    $imgStmt->execute([':id' => $article['id']]);
    $images = $imgStmt->fetchAll();
    $images = array_map(static function (array $img) use ($basePath): array {
        $url = (string) ($img['url'] ?? '');
        if ($url !== '' && !preg_match('#^(?:https?:)?//#i', $url) && strpos($url, '/') !== 0) {
            $img['url'] = fo_asset_url($basePath, $url);
        }
        return $img;
    }, $images);

    $tagStmt = $pdo->prepare(
        "SELECT t.libelle FROM tag t
         JOIN contenu_tag ct ON ct.id_tag = t.id
         WHERE ct.id_contenu = :id
         ORDER BY t.libelle"
    );
    $tagStmt->execute([':id' => $article['id']]);
    $tags = array_column($tagStmt->fetchAll(), 'libelle');

    $rawDesc = (string) ($article['meta_description'] ?? '');
    if ($rawDesc === '') {
        $rawDesc = fo_meta_trim((string) $article['details'], 160);
    }

    $metaDesc = fo_meta_trim($rawDesc, 160);
    $metaKeywords = trim((string) ($article['keywords'] ?? ''));
    if ($metaKeywords === '' && !empty($tags)) {
        $metaKeywords = implode(', ', $tags);
    }

    $rawTitle = trim((string) ($article['meta_title'] ?? ''));
    if ($rawTitle === '') {
        $rawTitle = (string) $article['titre'];
    }
    $pageTitle = fo_meta_trim($rawTitle, 60);

    $dateISO = date('c', strtotime((string) $article['created_at']));
    $dateUpdISO = date('c', strtotime((string) $article['updated_at']));
    $dateDisplay = date('F j, Y', strtotime((string) $article['created_at']));
    $coverImg = !empty($images) ? (string) $images[0]['url'] : '';
    $authorName = trim((string) ($article['author_name'] ?? 'IranWatch Editorial')) ?: 'IranWatch Editorial';

    $canonicalUrl = $baseUrl . '/article/' . rawurlencode($slug);

    $relatedStmt = $pdo->prepare(
        "SELECT c.slug, c.titre
         FROM contenu c
         WHERE c.id <> :id
           AND (
             c.id_type = (SELECT id_type FROM contenu WHERE id = :id)
             OR c.id IN (
                 SELECT ct2.id_contenu
                 FROM contenu_tag ct1
                 JOIN contenu_tag ct2 ON ct2.id_tag = ct1.id_tag
                 WHERE ct1.id_contenu = :id
             )
           )
         ORDER BY c.created_at DESC
         LIMIT 4"
    );
    $relatedStmt->execute([':id' => (int) $article['id']]);
    $relatedArticles = $relatedStmt->fetchAll();

    $leadParagraph = 'Iran war update: ' . $article['titre'] . ' with key context and analysis.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!$notFound && !empty($metaKeywords)): ?>
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <meta name="robots" content="<?= $notFound ? 'noindex, nofollow' : 'index, follow' ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">

    <?php if (!$notFound): ?>
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="article">
    <?php if ($coverImg !== ''): ?><meta property="og:image" content="<?= htmlspecialchars($coverImg, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <meta property="article:published_time" content="<?= $dateISO ?>">
    <meta property="article:modified_time" content="<?= $dateUpdISO ?>">
    <?php foreach ($tags as $tag): ?><meta property="article:tag" content="<?= htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": <?= json_encode((string) $article['titre']) ?>,
      "description": <?= json_encode($metaDesc) ?>,
      "author": {"@type":"Person","name": <?= json_encode($authorName) ?>},
      "datePublished": <?= json_encode($dateISO) ?>,
      "dateModified": <?= json_encode($dateUpdISO) ?>,
      "mainEntityOfPage": <?= json_encode($canonicalUrl) ?>,
      "publisher": {"@type":"Organization","name":"IranWatch"}
      <?php if ($coverImg !== ''): ?>,
      "image": <?= json_encode([$coverImg]) ?>
      <?php endif; ?>
    }
    </script>
    <?php endif; ?>

    <?php fo_render_analytics(); ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(fo_asset_url($basePath, 'css/style.min.css'), ENT_QUOTES, 'UTF-8') ?>">
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
            <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="nav-link">Home</a>
            <?php if (!$notFound && !empty($article['type_label'])): ?>
                <?php $typeSlug = fo_slugify((string) $article['type_label']); ?>
                <a href="<?= htmlspecialchars($homeHref . 'categorie/' . rawurlencode($typeSlug), ENT_QUOTES, 'UTF-8') ?>" class="nav-link active" aria-current="page"><?= htmlspecialchars((string) $article['type_label'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </nav>
        <div class="header-badge"><span class="live-dot" aria-hidden="true"></span><span>Live Coverage</span></div>
    </div>
</header>

<nav class="breadcrumb-nav container" aria-label="Breadcrumb">
    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" itemprop="item"><span itemprop="name">Home</span></a>
            <meta itemprop="position" content="1">
        </li>
        <?php if (!$notFound && !empty($article['type_label'])): ?>
        <li aria-hidden="true" class="breadcrumb-sep">›</li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <?php $typeSlug = fo_slugify((string) $article['type_label']); ?>
            <a href="<?= htmlspecialchars($homeHref . 'categorie/' . rawurlencode($typeSlug), ENT_QUOTES, 'UTF-8') ?>" itemprop="item"><span itemprop="name"><?= htmlspecialchars((string) $article['type_label'], ENT_QUOTES, 'UTF-8') ?></span></a>
            <meta itemprop="position" content="2">
        </li>
        <?php endif; ?>
        <li aria-hidden="true" class="breadcrumb-sep">›</li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
            <span itemprop="name"><?= !$notFound ? htmlspecialchars(fo_meta_trim((string) $article['titre'], 52), ENT_QUOTES, 'UTF-8') : 'Not Found' ?></span>
            <meta itemprop="position" content="3">
        </li>
    </ol>
</nav>

<main id="main-content" class="container article-layout" role="main">
<?php if ($notFound): ?>
    <div class="error-state">
        <p class="error-code" aria-hidden="true">404</p>
        <h1>Article Not Found</h1>
        <p>The article you are looking for does not exist or has been moved.</p>
        <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Back to homepage</a>
    </div>
<?php else: ?>
    <article class="article-full" itemscope itemtype="https://schema.org/Article">
        <header class="article-header">
            <?php if (!empty($article['type_label'])): ?><span class="article-type-badge"><?= htmlspecialchars((string) $article['type_label'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            <h1 class="article-title" itemprop="headline"><?= htmlspecialchars((string) $article['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="article-meta">
                <time class="article-date" datetime="<?= $dateISO ?>" itemprop="datePublished">Published <?= $dateDisplay ?></time>
                <?php if ($article['updated_at'] !== $article['created_at']): ?>
                    <time class="article-updated" datetime="<?= $dateUpdISO ?>" itemprop="dateModified">· Updated <?= date('F j, Y', strtotime((string) $article['updated_at'])) ?></time>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($coverImg !== ''): ?>
        <figure class="article-cover" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
            <img src="<?= htmlspecialchars($coverImg, ENT_QUOTES, 'UTF-8') ?>" alt="Main image for article <?= htmlspecialchars((string) $article['titre'], ENT_QUOTES, 'UTF-8') ?>" class="cover-img" width="900" height="506" loading="eager" itemprop="url">
        </figure>
        <?php endif; ?>

        <div class="article-body" itemprop="articleBody">
            <p><?= htmlspecialchars($leadParagraph, ENT_QUOTES, 'UTF-8') ?></p>
            <?php
            $lines = array_filter(array_map('trim', explode("\n", (string) $article['details'])));
            foreach ($lines as $line):
                if (preg_match('/^<h[23]/i', $line)):
                    echo $line;
                else:
            ?>
                <p><?= nl2br(htmlspecialchars($line, ENT_QUOTES, 'UTF-8')) ?></p>
            <?php
                endif;
            endforeach;
            ?>
        </div>

        <?php if (count($images) > 1): ?>
        <section class="article-gallery" aria-label="Article photo gallery">
            <h2 class="gallery-title">Photo Gallery</h2>
            <div class="gallery-grid">
                <?php foreach (array_slice($images, 1) as $i => $img): ?>
                <figure class="gallery-item">
                    <img src="<?= htmlspecialchars((string) $img['url'], ENT_QUOTES, 'UTF-8') ?>" alt="Photo <?= $i + 2 ?> for article <?= htmlspecialchars((string) $article['titre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" width="400" height="300">
                </figure>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
        <footer class="article-tags-section">
            <h2 class="tags-label">Topics covered</h2>
            <ul class="article-tag-list" aria-label="Article tags">
                <?php foreach ($tags as $tag): ?>
                <?php $tagUrl = $homeHref . 'tag/' . rawurlencode(fo_slugify((string) $tag)); ?>
                <li><a href="<?= htmlspecialchars($tagUrl, ENT_QUOTES, 'UTF-8') ?>" class="tag tag--link"><?= htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
            </ul>
        </footer>
        <?php endif; ?>
    </article>

    <?php if (!empty($relatedArticles)): ?>
    <section class="article-gallery" aria-label="Related articles" style="margin-top:2rem;">
        <h2 class="gallery-title">Related Articles</h2>
        <ul style="display:grid;gap:.6rem;">
            <?php foreach ($relatedArticles as $rel): ?>
                <li><a href="<?= htmlspecialchars($homeHref . 'article/' . rawurlencode((string) $rel['slug']), ENT_QUOTES, 'UTF-8') ?>" class="btn-read-more"><?= htmlspecialchars((string) $rel['titre'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <div class="back-link-wrap"><a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="btn-back">All Articles</a></div>
<?php endif; ?>
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
