<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo   = getDB();
$error = '';
$flash = $_GET['flash'] ?? null;

/* ── Articles for dropdown ── */
$articles = $pdo->query("SELECT id, titre, slug FROM contenu ORDER BY titre")->fetchAll();

/* ── Images list ── */
$images = $pdo->query(
    "SELECT im.id, im.url, c.titre AS article_titre
     FROM   image im
     JOIN   contenu c ON c.id = im.id_contenu
     ORDER  BY im.id DESC"
)->fetchAll();

/* ── POST: add image ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_contenu = (int) ($_POST['id_contenu'] ?? 0);
    $url        = trim($_POST['url'] ?? '');

    if (!$id_contenu || empty($url)) {
        $error = 'Article and image URL are required.';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid URL.';
    } else {
        try {
            $pdo->prepare("INSERT INTO image (id_contenu, url) VALUES (:c, :u)")
                ->execute([':c' => $id_contenu, ':u' => $url]);
            header('Location: ' . bo_base_path() . 'image-ajouter?flash=' . urlencode('Image linked successfully.'));
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

bo_head('Images', 'Manage article images in the IranWatch backoffice.');
bo_nav('images');
$base = bo_base_path();
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Images</h1>
    </div>

    <?php bo_flash($flash); ?>
    <?php bo_flash($error, 'error'); ?>

    <!-- Add image form -->
    <section aria-labelledby="img-form-title" style="margin-bottom:2.5rem;">
        <h2 id="img-form-title" style="font-family:var(--font-heading);font-size:1.3rem;color:var(--clr-white);margin-bottom:1rem;">
            Link New Image
        </h2>

        <form class="bo-form" method="post" action="" aria-label="Add image form">

            <div class="form-group">
                <label for="id_contenu">Article <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <select id="id_contenu" name="id_contenu" required>
                    <option value="">— Select article —</option>
                    <?php foreach ($articles as $art): ?>
                        <option value="<?= $art['id'] ?>"
                            <?= (($_POST['id_contenu'] ?? '') == $art['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($art['titre']) ?>
                            [<?= htmlspecialchars($art['slug']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="url">Image URL <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <input type="url" id="url" name="url" required
                    value="<?= htmlspecialchars($_POST['url'] ?? '') ?>"
                    placeholder="https://example.com/image.jpg"
                    autocomplete="off">
                <p class="form-hint">Must be a publicly accessible URL. The alt text is auto-derived from the article title.</p>
            </div>

            <!-- Preview -->
            <div class="form-group" id="preview-wrap" style="display:none;" aria-live="polite">
                <p style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--clr-muted);margin-bottom:.4rem;">
                    Preview
                </p>
                <img id="img-preview" src="" alt="Preview of entered image URL"
                    style="max-height:220px;border-radius:var(--radius-md);border:1px solid var(--clr-border);">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btn-add-image">💾 Link Image</button>
            </div>

        </form>
    </section>

    <!-- Images list -->
    <section aria-labelledby="img-list-title">
        <h2 id="img-list-title" style="font-family:var(--font-heading);font-size:1.3rem;color:var(--clr-white);margin-bottom:1rem;">
            Linked Images <small style="font-size:.8rem;color:var(--clr-muted);font-family:var(--font-body);">(<?= count($images) ?>)</small>
        </h2>

        <?php if (empty($images)): ?>
            <div class="empty-state" role="status">
                <p>No images linked yet.</p>
            </div>
        <?php else: ?>
            <div class="bo-table-wrap">
                <table class="bo-table" aria-label="Linked images">
                    <thead>
                        <tr>
                            <th scope="col">Preview</th>
                            <th scope="col">URL</th>
                            <th scope="col">Article</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($images as $img): ?>
                            <tr>
                                <td style="width:80px;">
                                    <img src="<?= htmlspecialchars($img['url']) ?>"
                                        alt="Thumbnail for article: <?= htmlspecialchars($img['article_titre']) ?>"
                                        loading="lazy"
                                        width="72" height="48"
                                        style="object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--clr-border);">
                                </td>
                                <td style="max-width:280px;word-break:break-all;">
                                    <a href="<?= htmlspecialchars($img['url']) ?>" target="_blank" rel="noopener noreferrer"
                                        style="font-size:.8rem;"
                                        aria-label="Open image in new tab">
                                        <?= htmlspecialchars(mb_substr($img['url'], 0, 60)) ?>…
                                    </a>
                                </td>
                                <td style="font-size:.85rem;"><?= htmlspecialchars($img['article_titre']) ?></td>
                                <td class="actions">
                                    <form method="post" action="<?= $base ?>image-supprimer"
                                        onsubmit="return confirm('Remove this image link?')"
                                        aria-label="Delete image #<?= $img['id'] ?>">
                                        <input type="hidden" name="id" value="<?= $img['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            id="btn-del-img-<?= $img['id'] ?>">
                                            🗑 Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</main>

<script>
    /* Live URL preview */
    (function() {
        var urlInput = document.getElementById('url');
        var preview = document.getElementById('img-preview');
        var previewWrap = document.getElementById('preview-wrap');
        if (!urlInput || !preview) return;

        var timer;
        urlInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                var v = urlInput.value.trim();
                if (!v) {
                    previewWrap.style.display = 'none';
                    return;
                }
                preview.src = v;
                preview.onload = function() {
                    previewWrap.style.display = 'block';
                };
                preview.onerror = function() {
                    previewWrap.style.display = 'none';
                };
            }, 600);
        });
    })();
</script>

<?php bo_foot(); ?>