<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo    = getDB();
$error  = '';
$flash  = '';

/* ── POST: create article ── */
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
        /* Auto-generate slug if empty was passed (JS fallback) */
        if (empty($slug)) $slug = slugify($titre);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO contenu (id_type, titre, slug, details, meta_title, meta_description, keywords, author_name, created_at, updated_at)
                 VALUES (:id_type, :titre, :slug, :details, :meta_title, :meta_description, :keywords, :author_name, NOW(), NOW())
                 RETURNING id"
            );
            $stmt->execute([
                ':id_type'           => $id_type ?: null,
                ':titre'             => $titre,
                ':slug'              => $slug,
                ':details'           => $details,
                ':meta_title'        => $metaTitle !== '' ? $metaTitle : mb_substr($titre, 0, 60),
                ':meta_description'  => $metaDescription !== '' ? $metaDescription : mb_substr(strip_tags($details), 0, 160),
                ':keywords'          => $keywords,
                ':author_name'       => $authorName !== '' ? $authorName : 'IranWatch Editorial',
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
            header('Location: ' . bo_base_path() . 'contenu-liste?flash=' . urlencode('Article created successfully.'));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$types = $pdo->query("SELECT id, libelle FROM type_contenu ORDER BY libelle")->fetchAll();
$tags  = $pdo->query("SELECT id, libelle FROM tag ORDER BY libelle")->fetchAll();
$keywordSuggestions = array_map(static fn(array $t): string => (string) $t['libelle'], $tags);

bo_head('Add Article', 'Create a new article in the IranWatch backoffice.');
bo_nav('articles');
$base = bo_base_path();
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Add Article</h1>
        <a href="<?= $base ?>contenu-liste" class="btn btn-secondary">← Back to list</a>
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

            <div class="form-group">
                <label for="meta_title">Meta title (max 60)</label>
                <input type="text" id="meta_title" name="meta_title" maxlength="60"
                    value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>"
                    placeholder="SEO title shown on Google results">
                <p class="form-hint"><span id="meta-title-count">0</span>/60</p>
            </div>

            <div class="form-group">
                <label for="meta_description">Meta description (max 160)</label>
                <textarea id="meta_description" name="meta_description" maxlength="160"
                    placeholder="Short summary for search engines"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
                <p class="form-hint"><span id="meta-desc-count">0</span>/160</p>
            </div>

            <div class="form-group">
                <label for="keywords">Keywords (comma-separated)</label>
                <input type="text" id="keywords" name="keywords" list="seo-keywords-suggestions"
                    value="<?= htmlspecialchars($_POST['keywords'] ?? '') ?>"
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
                    value="<?= htmlspecialchars($_POST['author_name'] ?? 'IranWatch Editorial') ?>"
                    placeholder="IranWatch Editorial">
            </div>

            <div class="form-group" aria-live="polite">
                <p class="form-hint" style="margin-bottom:.35rem;">SERP preview</p>
                <div style="border:1px solid var(--clr-border);border-radius:var(--radius-sm);padding:.8rem;background:#0f1420;">
                    <p id="serp-title" style="color:#8ab4f8;font-size:1.05rem;line-height:1.3;"><?= htmlspecialchars($_POST['meta_title'] ?? ($_POST['titre'] ?? '')) ?></p>
                    <p style="font-size:.8rem;color:#6fd58f;"><?= htmlspecialchars($base . 'article/' . ($_POST['slug'] ?? 'slug-article')) ?></p>
                    <p id="serp-desc" style="color:#bdc1c6;font-size:.88rem;"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></p>
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
                                    <?= in_array($tag['id'], (array)($_POST['tags'] ?? [])) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($tag['libelle']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btn-submit-article">💾 Save Article</button>
                <a href="<?= $base ?>contenu-liste" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </section>
</main>

<script>
    /* Auto-generate slug from title and update SEO preview */
    (function() {
        var titre = document.getElementById('titre');
        var slug = document.getElementById('slug');
        var metaTitle = document.getElementById('meta_title');
        var metaDesc = document.getElementById('meta_description');
        var serpTitle = document.getElementById('serp-title');
        var serpDesc = document.getElementById('serp-desc');
        var titleCount = document.getElementById('meta-title-count');
        var descCount = document.getElementById('meta-desc-count');

        if (!titre || !slug) return;

        function toSlug(v) {
            return v.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/[\s_]+/g, '-').replace(/-+/g, '-');
        }

        function refreshPreview() {
            if (serpTitle && metaTitle) serpTitle.textContent = metaTitle.value || titre.value || 'Titre Google';
            if (serpDesc && metaDesc) serpDesc.textContent = metaDesc.value || 'Description Google';
            if (titleCount && metaTitle) titleCount.textContent = String(metaTitle.value.length);
            if (descCount && metaDesc) descCount.textContent = String(metaDesc.value.length);
        }

        titre.addEventListener('input', function() {
            if (!slug.dataset.manual) {
                slug.value = toSlug(titre.value);
            }
            if (metaTitle && !metaTitle.dataset.manual) {
                metaTitle.value = titre.value.slice(0, 60);
            }
            if (metaDesc && !metaDesc.dataset.manual) {
                metaDesc.value = (document.getElementById('details')?.value || '').replace(/\s+/g, ' ').trim().slice(0, 160);
            }
            refreshPreview();
        });

        slug.addEventListener('input', function() {
            slug.dataset.manual = '1';
        });
        if (metaTitle) metaTitle.addEventListener('input', function() {
            metaTitle.dataset.manual = '1';
            refreshPreview();
        });
        if (metaDesc) metaDesc.addEventListener('input', function() {
            metaDesc.dataset.manual = '1';
            refreshPreview();
        });
        var details = document.getElementById('details');
        if (details) details.addEventListener('input', function() {
            if (metaDesc && !metaDesc.dataset.manual) {
                metaDesc.value = details.value.replace(/\s+/g, ' ').trim().slice(0, 160);
            }
            refreshPreview();
        });

        refreshPreview();
    })();
</script>

<?php bo_foot(); ?>