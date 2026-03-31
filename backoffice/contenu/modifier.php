<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo   = getDB();
$error = '';
$id    = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    header('Location: ' . bo_base_path() . 'contenu-liste');
    exit;
}

/* ── Load article ── */
$stmt = $pdo->prepare("SELECT * FROM contenu WHERE id = :id");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: ' . bo_base_path() . 'contenu-liste');
    exit;
}

/* ── Current tags ── */
$curTagsStmt = $pdo->prepare("SELECT id_tag FROM contenu_tag WHERE id_contenu = :id");
$curTagsStmt->execute([':id' => $id]);
$currentTagIds = array_column($curTagsStmt->fetchAll(), 'id_tag');

/* ── POST: update ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre           = trim($_POST['titre'] ?? '');
    $slug            = trim($_POST['slug'] ?? '');
    $id_type         = (int) ($_POST['id_type'] ?? 0);
    $details         = trim($_POST['details'] ?? '');
    $metaTitle       = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $keywords        = trim($_POST['keywords'] ?? '');
    $authorName      = trim($_POST['author_name'] ?? 'IranWatch Editorial');
    $tagIds          = array_map('intval', $_POST['tags'] ?? []);

    if (empty($titre) || empty($slug) || empty($details)) {
        $error = 'Title, slug and content are required.';
    } elseif (mb_strlen($metaTitle) > 60) {
        $error = 'Meta title must be 60 characters or less.';
    } elseif (mb_strlen($metaDescription) > 160) {
        $error = 'Meta description must be 160 characters or less.';
    } else {
        try {
            $pdo->beginTransaction();

            $pdo->prepare(
                "UPDATE contenu
                 SET id_type=:it,
                     titre=:ti,
                     slug=:sl,
                     details=:de,
                     meta_title=:mt,
                     meta_description=:md,
                     keywords=:kw,
                     author_name=:au,
                     updated_at=NOW()
                 WHERE id=:id"
            )->execute([
                ':it' => $id_type ?: null,
                ':ti' => $titre,
                ':sl' => $slug,
                ':de' => $details,
                ':mt' => $metaTitle !== '' ? $metaTitle : mb_substr($titre, 0, 60),
                ':md' => $metaDescription !== '' ? $metaDescription : mb_substr(strip_tags($details), 0, 160),
                ':kw' => $keywords,
                ':au' => $authorName !== '' ? $authorName : 'IranWatch Editorial',
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
            header('Location: ' . bo_base_path() . 'contenu-liste?flash=' . urlencode('Article updated successfully.'));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    /* Keep POST values on error */
    $article['titre']            = $_POST['titre'] ?? $article['titre'];
    $article['slug']             = $_POST['slug'] ?? $article['slug'];
    $article['id_type']          = $_POST['id_type'] ?? $article['id_type'];
    $article['details']          = $_POST['details'] ?? $article['details'];
    $article['meta_title']       = $_POST['meta_title'] ?? ($article['meta_title'] ?? '');
    $article['meta_description'] = $_POST['meta_description'] ?? ($article['meta_description'] ?? '');
    $article['keywords']         = $_POST['keywords'] ?? ($article['keywords'] ?? '');
    $article['author_name']      = $_POST['author_name'] ?? ($article['author_name'] ?? 'IranWatch Editorial');
    $currentTagIds               = $tagIds;
}

$article['meta_title']       = $article['meta_title'] ?? mb_substr((string) $article['titre'], 0, 60);
$article['meta_description'] = $article['meta_description'] ?? mb_substr(strip_tags((string) $article['details']), 0, 160);
$article['keywords']         = $article['keywords'] ?? '';
$article['author_name']      = $article['author_name'] ?? 'IranWatch Editorial';

$types = $pdo->query("SELECT id, libelle FROM type_contenu ORDER BY libelle")->fetchAll();
$tags  = $pdo->query("SELECT id, libelle FROM tag ORDER BY libelle")->fetchAll();
$keywordSuggestions = array_map(static fn(array $t): string => (string) $t['libelle'], $tags);

bo_head('Edit Article', 'Edit an existing article in the IranWatch backoffice.');
bo_nav('articles');
$base = bo_base_path();
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Edit Article</h1>
        <a href="<?= $base ?>contenu-liste" class="btn btn-secondary">← Back to list</a>
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

            <div class="form-group">
                <label for="meta_title">Meta title (max 60)</label>
                <input type="text" id="meta_title" name="meta_title" maxlength="60"
                    value="<?= htmlspecialchars($article['meta_title']) ?>"
                    placeholder="SEO title shown on Google results">
                <p class="form-hint"><span id="meta-title-count">0</span>/60</p>
            </div>

            <div class="form-group">
                <label for="meta_description">Meta description (max 160)</label>
                <textarea id="meta_description" name="meta_description" maxlength="160"
                    placeholder="Short summary for search engines"><?= htmlspecialchars($article['meta_description']) ?></textarea>
                <p class="form-hint"><span id="meta-desc-count">0</span>/160</p>
            </div>

            <div class="form-group">
                <label for="keywords">Keywords (comma-separated)</label>
                <input type="text" id="keywords" name="keywords" list="seo-keywords-suggestions"
                    value="<?= htmlspecialchars($article['keywords']) ?>"
                    placeholder="iran, geopolitics, missiles">
            </div>

            <datalist id="seo-keywords-suggestions">
                <?php foreach ($keywordSuggestions as $kw): ?>
                    <option value="<?= htmlspecialchars($kw) ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div class="form-group">
                <label for="author_name">Author</label>
                <input type="text" id="author_name" name="author_name"
                    value="<?= htmlspecialchars($article['author_name']) ?>"
                    placeholder="IranWatch Editorial">
            </div>

            <div class="form-group" aria-live="polite">
                <p class="form-hint" style="margin-bottom:.35rem;">SERP preview</p>
                <div style="border:1px solid var(--clr-border);border-radius:var(--radius-sm);padding:.8rem;background:#0f1420;">
                    <p id="serp-title" style="color:#8ab4f8;font-size:1.05rem;line-height:1.3;"><?= htmlspecialchars($article['meta_title']) ?></p>
                    <p style="font-size:.8rem;color:#6fd58f;"><?= htmlspecialchars($base . 'article/' . $article['slug']) ?></p>
                    <p id="serp-desc" style="color:#bdc1c6;font-size:.88rem;"><?= htmlspecialchars($article['meta_description']) ?></p>
                </div>
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
                <a href="<?= $base ?>contenu-supprimer?id=<?= $id ?>" class="btn btn-danger">🗑 Delete Article</a>
                <a href="<?= $base ?>contenu-liste" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </section>
</main>

<script>
    (function() {
        var titre = document.getElementById('titre');
        var metaTitle = document.getElementById('meta_title');
        var metaDesc = document.getElementById('meta_description');
        var serpTitle = document.getElementById('serp-title');
        var serpDesc = document.getElementById('serp-desc');
        var titleCount = document.getElementById('meta-title-count');
        var descCount = document.getElementById('meta-desc-count');

        function refreshPreview() {
            if (serpTitle && metaTitle) serpTitle.textContent = metaTitle.value || (titre ? titre.value : 'Titre Google');
            if (serpDesc && metaDesc) serpDesc.textContent = metaDesc.value || 'Description Google';
            if (titleCount && metaTitle) titleCount.textContent = String(metaTitle.value.length);
            if (descCount && metaDesc) descCount.textContent = String(metaDesc.value.length);
        }

        if (metaTitle) metaTitle.addEventListener('input', refreshPreview);
        if (metaDesc) metaDesc.addEventListener('input', refreshPreview);
        if (titre) titre.addEventListener('input', refreshPreview);
        refreshPreview();
    })();
</script>

<?php bo_foot(); ?>