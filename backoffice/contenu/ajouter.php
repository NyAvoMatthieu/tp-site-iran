<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$pdo    = getDB();
$error  = '';
$flash  = '';

/* ── POST: create article ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre   = trim($_POST['titre']   ?? '');
    $slug    = trim($_POST['slug']    ?? '');
    $id_type = (int) ($_POST['id_type'] ?? 0);
    $details = trim($_POST['details'] ?? '');
    $tagIds  = array_map('intval', $_POST['tags'] ?? []);

    if (empty($titre) || empty($slug) || empty($details)) {
        $error = 'Title, slug and content are required.';
    } else {
        /* Auto-generate slug if empty was passed (JS fallback) */
        if (empty($slug)) $slug = slugify($titre);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO contenu (id_type, titre, slug, details, created_at, updated_at)
                 VALUES (:id_type, :titre, :slug, :details, NOW(), NOW())
                 RETURNING id"
            );
            $stmt->execute([
                ':id_type'  => $id_type ?: null,
                ':titre'    => $titre,
                ':slug'     => $slug,
                ':details'  => $details,
            ]);
            $newId = (int) $stmt->fetchColumn();

            /* Assign tags */
            if ($tagIds) {
                $ins = $pdo->prepare("INSERT INTO contenu_tag(id_contenu, id_tag) VALUES(:c, :t) ON CONFLICT DO NOTHING");
                foreach ($tagIds as $tid) {
                    $ins->execute([':c' => $newId, ':t' => $tid]);
                }
            }

            $pdo->commit();
            header('Location: liste.php?flash=' . urlencode('Article created successfully.'));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$types = $pdo->query("SELECT id, libelle FROM type_contenu ORDER BY libelle")->fetchAll();
$tags  = $pdo->query("SELECT id, libelle FROM tag ORDER BY libelle")->fetchAll();

bo_head('Add Article', 'Create a new article in the IranWatch backoffice.');
bo_nav('articles');
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Add Article</h1>
        <a href="liste.php" class="btn btn-secondary">← Back to list</a>
    </div>

    <?php bo_flash($error, 'error'); ?>

    <section aria-labelledby="form-title">
        <h2 id="form-title" class="sr-only">New article form</h2>

        <form class="bo-form" method="post" action="" novalidate aria-label="Create article form">

            <div class="form-group">
                <label for="titre">Title <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <input type="text" id="titre" name="titre" required
                       value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>"
                       placeholder="Enter article title"
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label for="slug">Slug <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <input type="text" id="slug" name="slug"
                       value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>"
                       placeholder="auto-generated-from-title"
                       pattern="[a-z0-9\-]+"
                       autocomplete="off">
                <p class="form-hint" id="slug-hint">Lowercase, hyphens only. Auto-fills from title.</p>
            </div>

            <div class="form-group">
                <label for="id_type">Content type</label>
                <select id="id_type" name="id_type">
                    <option value="">— Select type —</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>"
                        <?= (($_POST['id_type'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['libelle']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="details">Content <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <textarea id="details" name="details" required
                          placeholder="Write the full article content here…"><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>
            </div>

            <?php if (!empty($tags)): ?>
            <div class="form-group">
                <label>Tags</label>
                <div class="checkbox-grid" role="group" aria-label="Select tags">
                    <?php foreach ($tags as $tag): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="tags[]"
                               value="<?= $tag['id'] ?>"
                               <?= in_array($tag['id'], (array)($_POST['tags'] ?? [])) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($tag['libelle']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btn-submit-article">💾 Save Article</button>
                <a href="liste.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </section>
</main>

<script>
/* Auto-generate slug from title */
(function(){
    var titre = document.getElementById('titre');
    var slug  = document.getElementById('slug');
    if (!titre || !slug) return;
    titre.addEventListener('input', function(){
        if (slug.dataset.manual) return;
        slug.value = titre.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/[\s_]+/g, '-').replace(/-+/g, '-');
    });
    slug.addEventListener('input', function(){ slug.dataset.manual = '1'; });
})();
</script>

<?php bo_foot(); ?>
