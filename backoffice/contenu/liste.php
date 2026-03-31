<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo = getDB();

$articles = $pdo->query(
    "SELECT c.id, c.titre, c.slug, c.created_at, tc.libelle AS type_label,
            LENGTH(COALESCE(c.meta_title, '')) AS meta_title_len,
            LENGTH(COALESCE(c.meta_description, '')) AS meta_desc_len
     FROM   contenu c
     LEFT JOIN type_contenu tc ON tc.id = c.id_type
     ORDER  BY c.created_at DESC"
)->fetchAll();

bo_head('Articles', 'List of all articles in the IranWatch backoffice.');
bo_nav('articles');
$base = bo_base_path();
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Articles</h1>
        <a href="<?= $base ?>contenu-ajouter" class="btn btn-primary" id="btn-new-article">＋ New Article</a>
    </div>

    <?php if (empty($articles)): ?>
        <div class="empty-state" role="status">
            <p>No articles found. Create your first one.</p>
            <a href="<?= $base ?>contenu-ajouter" class="btn btn-primary">＋ New Article</a>
        </div>
    <?php else: ?>
        <div class="bo-table-wrap">
            <table class="bo-table" aria-label="All articles">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Title</th>
                        <th scope="col">Slug</th>
                        <th scope="col">Type</th>
                        <th scope="col">SEO</th>
                        <th scope="col">Published</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $i => $art): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <h2 style="font-size:.95rem;font-family:var(--font-body);font-weight:600;color:var(--clr-white);">
                                    <?= htmlspecialchars($art['titre']) ?>
                                </h2>
                            </td>
                            <td><span class="slug-pill"><?= htmlspecialchars($art['slug']) ?></span></td>
                            <td>
                                <?php if ($art['type_label']): ?>
                                    <span class="tag-pill"><?= htmlspecialchars($art['type_label']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--clr-muted)">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $seoOk = ($art['meta_title_len'] >= 50 && $art['meta_title_len'] <= 60) && ($art['meta_desc_len'] >= 150 && $art['meta_desc_len'] <= 160); ?>
                                <span class="tag-pill" style="background:<?= $seoOk ? '#1f7a39' : '#8b1f2e' ?>;color:#fff;">
                                    <?= $seoOk ? 'Good' : 'Fix' ?>
                                </span>
                            </td>
                            <td>
                                <time datetime="<?= date('c', strtotime($art['created_at'])) ?>">
                                    <?= date('d M Y', strtotime($art['created_at'])) ?>
                                </time>
                            </td>
                            <td class="actions">
                                <a href="<?= $base ?>contenu-modifier?id=<?= $art['id'] ?>"
                                    class="btn btn-secondary btn-sm"
                                    aria-label="Edit article: <?= htmlspecialchars($art['titre']) ?>">
                                    ✏️ Edit
                                </a>
                                <a href="<?= $base ?>contenu-supprimer?id=<?= $art['id'] ?>"
                                    class="btn btn-danger btn-sm"
                                    aria-label="Delete article: <?= htmlspecialchars($art['titre']) ?>">
                                    🗑 Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</main>

<?php bo_foot(); ?>