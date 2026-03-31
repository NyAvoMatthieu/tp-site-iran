<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/layout.php';

admin_require_auth();

$pdo = getDB();
$base = bo_base_path();

$rows = $pdo->query(
    "SELECT c.id, c.titre, c.slug, c.details, c.meta_title, c.meta_description,
            (SELECT COUNT(*) FROM image i WHERE i.id_contenu = c.id) AS image_count
     FROM contenu c
     ORDER BY c.updated_at DESC"
)->fetchAll();

$audits = [];
foreach ($rows as $row) {
    $metaTitle = trim((string) ($row['meta_title'] ?? ''));
    $metaDesc  = trim((string) ($row['meta_description'] ?? ''));
    $slug      = (string) $row['slug'];

    $detailsHtml = (string) ($row['details'] ?? '');
    preg_match_all('/<img\b[^>]*>/i', $detailsHtml, $imgMatches);
    $imgTagsInContent = $imgMatches[0] ?? [];
    $missingAltCount = 0;
    foreach ($imgTagsInContent as $imgTag) {
        if (!preg_match('/\balt\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', $imgTag)) {
            $missingAltCount++;
        }
    }

    $checks = [
        'h1' => trim((string) $row['titre']) !== '',
        'title_len' => mb_strlen($metaTitle) >= 50 && mb_strlen($metaTitle) <= 60,
        'meta_len' => mb_strlen($metaDesc) >= 150 && mb_strlen($metaDesc) <= 160,
        'alt_images' => $missingAltCount === 0,
        'url_length' => mb_strlen($slug) <= 75,
    ];

    $score = (int) round((array_sum(array_map(static fn($v) => $v ? 1 : 0, $checks)) / count($checks)) * 100);

    $audits[] = [
        'id' => (int) $row['id'],
        'titre' => (string) $row['titre'],
        'slug' => $slug,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDesc,
        'checks' => $checks,
        'score' => $score,
    ];
}

bo_head('SEO Audit', 'Audit rapide SEO des contenus du site.');
bo_nav('seo');
?>
<a href="#main-content" class="skip-link">Skip to main content</a>
<main id="main-content" class="bo-page" role="main">
    <div class="page-header">
        <h1>SEO Audit</h1>
        <a href="<?= $base ?>contenu-liste" class="btn btn-secondary">Back to Articles</a>
    </div>

    <section style="margin-bottom:1.2rem;">
        <h2 style="font-size:1rem;margin-bottom:.6rem;">Core Web Vitals Indicator</h2>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <div class="tag-pill" style="padding:.45rem .7rem;">LCP: <strong id="cwv-lcp">-</strong></div>
            <div class="tag-pill" style="padding:.45rem .7rem;">CLS: <strong id="cwv-cls">-</strong></div>
            <div class="tag-pill" style="padding:.45rem .7rem;">INP: <strong id="cwv-inp">-</strong></div>
        </div>
    </section>

    <div class="bo-table-wrap">
        <table class="bo-table" aria-label="SEO audit table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Score</th>
                    <th>H1</th>
                    <th>Title 50-60</th>
                    <th>Description 150-160</th>
                    <th>Alt images</th>
                    <th>URL length</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audits as $audit): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($audit['titre']) ?></strong><br>
                            <span class="slug-pill"><?= htmlspecialchars($audit['slug']) ?></span>
                        </td>
                        <td>
                            <span class="tag-pill" style="background:<?= $audit['score'] >= 80 ? '#1f7a39' : ($audit['score'] >= 60 ? '#8a6d1f' : '#8b1f2e') ?>;color:#fff;">
                                <?= $audit['score'] ?>%
                            </span>
                        </td>
                        <td><?= $audit['checks']['h1'] ? 'OK' : 'NO' ?></td>
                        <td><?= $audit['checks']['title_len'] ? 'OK' : 'NO' ?></td>
                        <td><?= $audit['checks']['meta_len'] ? 'OK' : 'NO' ?></td>
                        <td><?= $audit['checks']['alt_images'] ? 'OK' : 'NO' ?></td>
                        <td><?= $audit['checks']['url_length'] ? 'OK' : 'NO' ?></td>
                        <td>
                            <a href="<?= $base ?>contenu-modifier?id=<?= $audit['id'] ?>" class="btn btn-secondary btn-sm">Fix</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<script>
    (function() {
        var lcpNode = document.getElementById('cwv-lcp');
        var clsNode = document.getElementById('cwv-cls');
        var inpNode = document.getElementById('cwv-inp');
        if (!lcpNode || !clsNode || !inpNode || !('PerformanceObserver' in window)) return;

        var clsValue = 0;

        try {
            new PerformanceObserver(function(entryList) {
                var entries = entryList.getEntries();
                var last = entries[entries.length - 1];
                if (last && last.startTime) {
                    lcpNode.textContent = (last.startTime / 1000).toFixed(2) + 's';
                }
            }).observe({
                type: 'largest-contentful-paint',
                buffered: true
            });

            new PerformanceObserver(function(entryList) {
                entryList.getEntries().forEach(function(entry) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                        clsNode.textContent = clsValue.toFixed(3);
                    }
                });
            }).observe({
                type: 'layout-shift',
                buffered: true
            });

            new PerformanceObserver(function(entryList) {
                var entries = entryList.getEntries();
                if (!entries.length) return;
                var worst = entries.reduce(function(max, e) {
                    return e.duration > max.duration ? e : max;
                }, entries[0]);
                inpNode.textContent = Math.round(worst.duration) + 'ms';
            }).observe({
                type: 'event',
                buffered: true,
                durationThreshold: 40
            });
        } catch (e) {
            lcpNode.textContent = 'n/a';
            clsNode.textContent = 'n/a';
            inpNode.textContent = 'n/a';
        }
    })();
</script>
<?php bo_foot(); ?>