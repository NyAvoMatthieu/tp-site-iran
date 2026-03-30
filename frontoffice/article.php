<?php
require_once __DIR__ . '/db.php';

/* ── Validate slug ── */
$slug = trim($_GET['slug'] ?? '');

/* Build base paths so links/assets work whether app is at domain root or in a subfolder */
$rawBasePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath    = ($rawBasePath === '/' || $rawBasePath === '.') ? '' : rtrim($rawBasePath, '/');
$scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl     = $scheme . '://' . $host . $basePath;
$homeHref    = ($basePath === '') ? '/' : $basePath . '/';

if (empty($slug)) {
    http_response_code(400);
    header('Location: /');
    exit;
}

$pdo = getDB();

/* ── Fetch article ── */
$stmt = $pdo->prepare(
    "SELECT c.id, c.titre, c.slug, c.details, c.created_at, c.updated_at,
            tc.libelle AS type_label
     FROM   contenu c
     LEFT JOIN type_contenu tc ON tc.id = c.id_type
     WHERE  c.slug = :slug
     LIMIT  1"
);
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $pageTitle = '404 – Article Not Found | IranWatch';
    $notFound  = true;
} else {
    $notFound = false;

    /* ── Fetch images ── */
    $imgStmt = $pdo->prepare("SELECT url FROM image WHERE id_contenu = :id ORDER BY id");
    $imgStmt->execute([':id' => $article['id']]);
    $images = $imgStmt->fetchAll();
    $images = array_map(static function (array $img) use ($basePath): array {
        $url = (string) ($img['url'] ?? '');
        if ($url !== '' && !preg_match('#^(?:https?:)?//#i', $url) && strpos($url, '/') !== 0) {
            $img['url'] = (($basePath === '') ? '/' : $basePath . '/') . ltrim($url, '/');
        }
        return $img;
    }, $images);

    /* ── Fetch tags ── */
    $tagStmt = $pdo->prepare(
        "SELECT t.libelle FROM tag t
         JOIN   contenu_tag ct ON ct.id_tag = t.id
         WHERE  ct.id_contenu = :id
         ORDER  BY t.libelle"
    );
    $tagStmt->execute([':id' => $article['id']]);
    $tags = array_column($tagStmt->fetchAll(), 'libelle');

    /* ── SEO meta ── */
    $plainDetails = strip_tags($article['details']);
    $metaDesc     = mb_substr($plainDetails, 0, 160);
    if (mb_strlen($plainDetails) > 160) $metaDesc .= '…';

    $metaKeywords = implode(', ', $tags);
    $pageTitle    = htmlspecialchars($article['titre']) . ' | IranWatch';
    $dateISO      = date('c', strtotime($article['created_at']));
    $dateUpdISO   = date('c', strtotime($article['updated_at']));
    $dateDisplay  = date('F j, Y', strtotime($article['created_at']));
    $coverImg     = !empty($images) ? $images[0]['url'] : '';
    $canonicalUrl = $baseUrl . '/article/' . rawurlencode($slug);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <?php if ($notFound): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <?php if (!empty($metaKeywords)): ?>
    <meta name="keywords"    content="<?= htmlspecialchars($metaKeywords) ?>">
    <?php endif; ?>
    <meta name="robots"      content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title"       content="<?= htmlspecialchars($article['titre']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:type"        content="article">
    <?php if ($coverImg): ?>
    <meta property="og:image" content="<?= htmlspecialchars($coverImg) ?>">
    <?php endif; ?>

    <!-- Article structured data -->
    <meta property="article:published_time" content="<?= $dateISO ?>">
    <meta property="article:modified_time"  content="<?= $dateUpdISO ?>">
    <?php foreach ($tags as $tag): ?>
    <meta property="article:tag" content="<?= htmlspecialchars($tag) ?>">
    <?php endforeach; ?>

    <!-- Canonical -->
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <!-- JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "NewsArticle",
      "headline": <?= json_encode($article['titre']) ?>,
      "description": <?= json_encode($metaDesc) ?>,
      "datePublished": <?= json_encode($dateISO) ?>,
      "dateModified": <?= json_encode($dateUpdISO) ?>,
      "publisher": {
        "@type": "Organization",
        "name": "IranWatch"
      }
      <?php if ($coverImg): ?>
      ,"image": <?= json_encode($coverImg) ?>
      <?php endif; ?>
    }
    </script>
    <?php endif; ?>

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
            <a href="<?= htmlspecialchars($homeHref) ?>" class="logo-link" aria-label="IranWatch – go to homepage">
                <span class="logo-icon" aria-hidden="true">⚡</span>
                <span class="logo-text">IranWatch</span>
            </a>
        </div>
        <nav class="main-nav" aria-label="Primary navigation">
            <a href="<?= htmlspecialchars($homeHref) ?>" class="nav-link">Home</a>
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

<!-- ═══════════════════ BREADCRUMB ═══════════════════ -->
<nav class="breadcrumb-nav container" aria-label="Breadcrumb">
    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= htmlspecialchars($homeHref) ?>" itemprop="item"><span itemprop="name">Home</span></a>
            <meta itemprop="position" content="1">
        </li>
        <li aria-hidden="true" class="breadcrumb-sep">›</li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
            <?php if (!$notFound): ?>
            <span itemprop="name"><?= htmlspecialchars(mb_substr($article['titre'], 0, 50)) ?>…</span>
            <?php else: ?>
            <span>Not Found</span>
            <?php endif; ?>
            <meta itemprop="position" content="2">
        </li>
    </ol>
</nav>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<main id="main-content" class="container article-layout" role="main">

<?php if ($notFound): ?>
    <!-- 404 STATE -->
    <div class="error-state">
        <p class="error-code" aria-hidden="true">404</p>
        <h1>Article Not Found</h1>
        <p>The article you're looking for doesn't exist or may have been moved.</p>
        <a href="<?= htmlspecialchars($homeHref) ?>" class="btn-primary">← Back to Home</a>
    </div>

<?php else: ?>
    <!-- ARTICLE CONTENT -->
    <article class="article-full" itemscope itemtype="https://schema.org/NewsArticle">

        <!-- Article Header -->
        <header class="article-header">
            <?php if (!empty($article['type_label'])): ?>
            <span class="article-type-badge"><?= htmlspecialchars($article['type_label']) ?></span>
            <?php endif; ?>

            <h1 class="article-title" itemprop="headline">
                <?= htmlspecialchars($article['titre']) ?>
            </h1>

            <div class="article-meta">
                <time class="article-date" datetime="<?= $dateISO ?>" itemprop="datePublished">
                    📅 Published <?= $dateDisplay ?>
                </time>
                <?php if ($article['updated_at'] !== $article['created_at']): ?>
                <time class="article-updated" datetime="<?= $dateUpdISO ?>" itemprop="dateModified">
                    · Updated <?= date('F j, Y', strtotime($article['updated_at'])) ?>
                </time>
                <?php endif; ?>
            </div>
        </header>

        <!-- Cover Image -->
        <?php if (!empty($coverImg)): ?>
        <figure class="article-cover" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
            <img src="<?= htmlspecialchars($coverImg) ?>"
                 alt="Main image illustrating the article: <?= htmlspecialchars($article['titre']) ?>"
                 class="cover-img"
                 width="900" height="506"
                 loading="eager"
                 itemprop="url">
        </figure>
        <?php endif; ?>

        <!-- Article Body -->
        <div class="article-body" itemprop="articleBody">
            <?php
            /* Render each line as a paragraph; keep any <h2>/<h3> tags already in content */
            $lines = array_filter(array_map('trim', explode("\n", $article['details'])));
            foreach ($lines as $line):
                $line = trim($line);
                if (preg_match('/^<h[23]/i', $line)):
                    echo $line;
                else:
            ?>
            <p><?= nl2br(htmlspecialchars($line)) ?></p>
            <?php
                endif;
            endforeach;
            ?>
        </div>

        <!-- Gallery -->
        <?php if (count($images) > 1): ?>
        <section class="article-gallery" aria-label="Article photo gallery">
            <h2 class="gallery-title">Photo Gallery</h2>
            <div class="gallery-grid">
                <?php foreach (array_slice($images, 1) as $i => $img): ?>
                <figure class="gallery-item">
                    <img src="<?= htmlspecialchars($img['url']) ?>"
                         alt="Photo <?= $i + 2 ?> related to: <?= htmlspecialchars($article['titre']) ?>"
                         loading="lazy"
                         width="400" height="300">
                </figure>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Tags -->
        <?php if (!empty($tags)): ?>
        <footer class="article-tags-section">
            <h3 class="tags-label">Topics covered</h3>
            <ul class="article-tag-list" aria-label="Article tags">
                <?php foreach ($tags as $tag): ?>
                <li>
                    <a href="<?= htmlspecialchars($homeHref . '?tag=' . urlencode($tag)) ?>" class="tag tag--link">
                        <?= htmlspecialchars($tag) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </footer>
        <?php endif; ?>

    </article>

    <!-- BACK LINK -->
    <div class="back-link-wrap">
        <a href="<?= htmlspecialchars($homeHref) ?>" class="btn-back">← All Articles</a>
    </div>

<?php endif; ?>
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
