<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo = getDB();

$flash = $_GET['flash'] ?? null;

/* Tag list with usage count */
$tags = $pdo->query(
    "SELECT t.id, t.libelle,
            COUNT(ct.id_contenu) AS usage_count
     FROM   tag t
     LEFT JOIN contenu_tag ct ON ct.id_tag = t.id
     GROUP  BY t.id, t.libelle
     ORDER  BY t.libelle"
)->fetchAll();

bo_head('Tags', 'Manage content tags in the IranWatch backoffice.');
bo_nav('tags');
$base = bo_base_path();
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Tags</h1>
        <a href="<?= $base ?>tag-ajouter" class="btn btn-primary" id="btn-new-tag">＋ New Tag</a>
    </div>

    <?php bo_flash($flash); ?>

    <?php if (empty($tags)): ?>
        <div class="empty-state" role="status">
            <p>No tags yet. Add your first tag.</p>
            <a href="<?= $base ?>tag-ajouter" class="btn btn-primary">＋ New Tag</a>
        </div>
    <?php else: ?>

        <section aria-label="Tag list">
            <h2 style="font-family:var(--font-heading);font-size:1.2rem;color:var(--clr-white);margin-bottom:1rem;">
                All Tags <small style="font-size:.8rem;color:var(--clr-muted);font-family:var(--font-body);">(<?= count($tags) ?>)</small>
            </h2>
            <div class="bo-table-wrap">
                <table class="bo-table" aria-label="All tags">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Tag</th>
                            <th scope="col">Used in</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tags as $i => $tag): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <h3 style="font-size:.9rem;font-family:var(--font-body);font-weight:600;color:var(--clr-white);display:inline;">
                                        <span class="tag-pill"><?= htmlspecialchars($tag['libelle']) ?></span>
                                    </h3>
                                </td>
                                <td>
                                    <span style="color:var(--clr-muted);font-size:.85rem;">
                                        <?= (int)$tag['usage_count'] ?> article<?= $tag['usage_count'] != 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <form method="post" action="<?= $base ?>tag-supprimer"
                                        onsubmit="return confirm('Delete tag: <?= htmlspecialchars(addslashes($tag['libelle'])) ?>?')"
                                        aria-label="Delete tag <?= htmlspecialchars($tag['libelle']) ?>">
                                        <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            id="btn-del-tag-<?= $tag['id'] ?>">
                                            🗑 Delete
                                        </button>
                                    </form>
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