<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/layout.php';

admin_require_auth();

$pdo = getDB();

$totalArticles = (int) $pdo->query("SELECT COUNT(*) FROM contenu")->fetchColumn();
$totalTags     = (int) $pdo->query("SELECT COUNT(*) FROM tag")->fetchColumn();
$totalImages   = (int) $pdo->query("SELECT COUNT(*) FROM image")->fetchColumn();
$totalTypes    = (int) $pdo->query("SELECT COUNT(*) FROM type_contenu")->fetchColumn();

/* 5 most recent articles */
$recent = $pdo->query(
    "SELECT titre, slug, created_at FROM contenu ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

bo_head('Dashboard', 'IranWatch admin dashboard – article, tag and image management.');
bo_nav('dashboard');
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Dashboard</h1>
        <a href="contenu/ajouter.php" class="btn btn-primary">＋ New Article</a>
    </div>

    <!-- Stats -->
    <div class="dash-stats" aria-label="Key statistics">
        <div class="dash-stat">
            <div class="dash-stat-num"><?= $totalArticles ?></div>
            <div class="dash-stat-label">Articles</div>
        </div>
        <div class="dash-stat">
            <div class="dash-stat-num"><?= $totalTags ?></div>
            <div class="dash-stat-label">Tags</div>
        </div>
        <div class="dash-stat">
            <div class="dash-stat-num"><?= $totalImages ?></div>
            <div class="dash-stat-label">Images</div>
        </div>
        <div class="dash-stat">
            <div class="dash-stat-num"><?= $totalTypes ?></div>
            <div class="dash-stat-label">Types</div>
        </div>
    </div>

    <!-- Quick access cards -->
    <section aria-labelledby="quickaccess-title">
        <h2 id="quickaccess-title" class="sr-only">Quick access</h2>
        <div class="dash-grid">

            <a href="contenu/liste.php" class="dash-card" aria-label="Manage articles (<?= $totalArticles ?> total)">
                <span class="dash-card-icon" aria-hidden="true">📰</span>
                <span class="dash-card-title">Articles</span>
                <span class="dash-card-count"><?= $totalArticles ?></span>
                <span class="dash-card-sub">View, edit, delete articles</span>
            </a>

            <a href="tag/liste.php" class="dash-card" aria-label="Manage tags (<?= $totalTags ?> total)">
                <span class="dash-card-icon" aria-hidden="true">🏷️</span>
                <span class="dash-card-title">Tags</span>
                <span class="dash-card-count"><?= $totalTags ?></span>
                <span class="dash-card-sub">Manage content tags</span>
            </a>

            <a href="image/ajouter.php" class="dash-card" aria-label="Add images (<?= $totalImages ?> total)">
                <span class="dash-card-icon" aria-hidden="true">🖼️</span>
                <span class="dash-card-title">Images</span>
                <span class="dash-card-count"><?= $totalImages ?></span>
                <span class="dash-card-sub">Link images to articles</span>
            </a>

        </div>
    </section>

    <!-- Recent articles -->
    <?php if (!empty($recent)): ?>
        <section class="recent-section" style="margin-top:2.5rem;" aria-labelledby="recent-title">
            <h2 id="recent-title" style="font-family:var(--font-heading);font-size:1.2rem;color:var(--clr-white);margin-bottom:1rem;">
                Recent Articles
            </h2>
            <div class="bo-table-wrap">
                <table class="bo-table" aria-label="5 most recent articles">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Slug</th>
                            <th scope="col">Published</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $art): ?>
                            <tr>
                                <td><?= htmlspecialchars($art['titre']) ?></td>
                                <td><span class="slug-pill"><?= htmlspecialchars($art['slug']) ?></span></td>
                                <td><?= date('d M Y', strtotime($art['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="contenu/liste.php" class="btn btn-secondary btn-sm">View all</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php bo_foot(); ?>