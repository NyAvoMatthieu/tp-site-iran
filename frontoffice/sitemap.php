<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/seo.php';

$ctx = fo_context();
$baseUrl = $ctx['baseUrl'];

$pdo = getDB();

$urls = [];
$now = date('c');

$urls[] = [
    'loc' => $baseUrl . '/',
    'lastmod' => $now,
    'priority' => '1.0',
];

$articles = $pdo->query("SELECT slug, updated_at FROM contenu ORDER BY updated_at DESC")->fetchAll();
foreach ($articles as $article) {
    $urls[] = [
        'loc' => $baseUrl . '/article/' . rawurlencode((string) $article['slug']),
        'lastmod' => date('c', strtotime((string) $article['updated_at'])),
        'priority' => '0.8',
    ];
}

$tags = $pdo->query("SELECT libelle FROM tag ORDER BY libelle")->fetchAll();
foreach ($tags as $tag) {
    $slug = fo_slugify((string) $tag['libelle']);
    if ($slug === '') {
        continue;
    }
    $urls[] = [
        'loc' => $baseUrl . '/tag/' . rawurlencode($slug),
        'lastmod' => $now,
        'priority' => '0.6',
    ];
}

$types = $pdo->query("SELECT libelle FROM type_contenu ORDER BY libelle")->fetchAll();
foreach ($types as $type) {
    $slug = fo_slugify((string) $type['libelle']);
    if ($slug === '') {
        continue;
    }
    $urls[] = [
        'loc' => $baseUrl . '/categorie/' . rawurlencode($slug),
        'lastmod' => $now,
        'priority' => '0.7',
    ];
}

header('Content-Type: application/xml; charset=UTF-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($urls as $url): ?>
        <url>
            <loc><?= htmlspecialchars($url['loc'], ENT_XML1) ?></loc>
            <lastmod><?= htmlspecialchars($url['lastmod'], ENT_XML1) ?></lastmod>
            <priority><?= htmlspecialchars($url['priority'], ENT_XML1) ?></priority>
        </url>
    <?php endforeach; ?>
</urlset>