<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo   = getDB();
$error = '';
$id    = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    header('Location: liste.php');
    exit;
}

/* ── Load article ── */
$stmt = $pdo->prepare("SELECT * FROM contenu WHERE id = :id");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: liste.php');
    exit;
}

/* ── Current tags ── */
$curTagsStmt = $pdo->prepare("SELECT id_tag FROM contenu_tag WHERE id_contenu = :id");
$curTagsStmt->execute([':id' => $id]);
$currentTagIds = array_column($curTagsStmt->fetchAll(), 'id_tag');

/* ── POST: update ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre   = trim($_POST['titre']   ?? '');
    $slug    = trim($_POST['slug']    ?? '');
    $id_type = (int) ($_POST['id_type'] ?? 0);
    $details = trim($_POST['details'] ?? '');
    $tagIds  = array_map('intval', $_POST['tags'] ?? []);

    if (empty($titre) || empty($slug) || empty($details)) {
        $error = 'Title, slug and content are required.';
    } else {
        try {
            $pdo->beginTransaction();

            $pdo->prepare(
                "UPDATE contenu SET id_type=:it, titre=:ti, slug=:sl, details=:de, updated_at=NOW()
                 WHERE id=:id"
            )->execute([
                ':it' => $id_type ?: null,
                ':ti' => $titre,
                ':sl' => $slug,
                ':de' => $details,
                ':id' => $id,
            ]);

            /* Sync tags */
            $pdo->prepare("DELETE FROM contenu_tag WHERE id_contenu = :c")->execute([':c' => $id]);
            if ($tagIds) {
                $ins = $pdo->prepare("INSERT INTO contenu_tag(id_contenu, id_tag) VALUES(:c, :t)");
                foreach ($tagIds as $tid) {
                    $ins->execute([':c' => $id, ':t' => $tid]);
                }
            }

            $pdo->commit();
            header('Location: liste.php?flash=' . urlencode('Article updated successfully.'));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    /* Keep POST values on error */
    $article['titre']   = $_POST['titre']   ?? $article['titre'];
    $article['slug']    = $_POST['slug']    ?? $article['slug'];
    $article['id_type'] = $_POST['id_type'] ?? $article['id_type'];
    $article['details'] = $_POST['details'] ?? $article['details'];
    $currentTagIds      = $tagIds;
}

$types = $pdo->query("SELECT id, libelle FROM type_contenu ORDER BY libelle")->fetchAll();
$tags  = $pdo->query("SELECT id, libelle FROM tag ORDER BY libelle")->fetchAll();

bo_head('Edit Article', 'Edit an existing article in the IranWatch backoffice.');
bo_nav('articles');
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Edit Article</h1>
        <a href="liste.php" class="btn btn-secondary">← Back to list</a>
    </div>

    <?php bo_flash($error, 'error'); ?>

    <section aria-labelledby="edit-form-title">
        <h2 id="edit-form-title" class="sr-only">Edit article form</h2>

        <form class="bo-form" method="post" action="" novalidate aria-label="Edit article form">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="form-group">
                <label for="titre">Title <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <input type="text" id="titre" name="titre" required
                    value="<?= htmlspecialchars($article['titre']) ?>"
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label for="slug">Slug <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <input type="text" id="slug" name="slug"
                    value="<?= htmlspecialchars($article['slug']) ?>"
                    pattern="[a-z0-9\-]+"
                    autocomplete="off">
                <p class="form-hint">Changing the slug will break existing links.</p>
            </div>

            <div class="form-group">
                <label for="id_type">Content type</label>
                <select id="id_type" name="id_type">
                    <option value="">— None —</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>"
                            <?= ($article['id_type'] == $t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="details">Content <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <textarea id="details" name="details" required><?= htmlspecialchars($article['details']) ?></textarea>
            </div>

            <?php if (!empty($tags)): ?>
                <div class="form-group">
                    <label>Tags</label>
                    <div class="checkbox-grid" role="group" aria-label="Select tags">
                        <?php foreach ($tags as $tag): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="tags[]"
                                    value="<?= $tag['id'] ?>"
                                    <?= in_array((int)$tag['id'], array_map('intval', $currentTagIds)) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($tag['libelle']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btn-update-article">💾 Save Changes</button>
                <a href="supprimer.php?id=<?= $id ?>" class="btn btn-danger">🗑 Delete Article</a>
                <a href="liste.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </section>
</main>

<?php bo_foot(); ?>