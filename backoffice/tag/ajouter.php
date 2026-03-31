<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo   = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = trim($_POST['libelle'] ?? '');

    if (empty($libelle)) {
        $error = 'Tag name is required.';
    } else {
        try {
            $pdo->prepare("INSERT INTO tag (libelle) VALUES (:l)")->execute([':l' => $libelle]);
            header('Location: liste.php?flash=' . urlencode('Tag "' . $libelle . '" created.'));
            exit;
        } catch (PDOException $e) {
            /* Duplicate libelle */
            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')) {
                $error = 'A tag with that name already exists.';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

bo_head('Add Tag', 'Create a new tag in the IranWatch backoffice.');
bo_nav('tags');
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <div class="page-header">
        <h1>Add Tag</h1>
        <a href="liste.php" class="btn btn-secondary">← Back to tags</a>
    </div>

    <?php bo_flash($error, 'error'); ?>

    <section aria-labelledby="tag-form-title">
        <h2 id="tag-form-title" class="sr-only">New tag form</h2>

        <form class="bo-form" method="post" action="" aria-label="Create tag form">

            <div class="form-group">
                <label for="libelle">Tag name <span aria-hidden="true" style="color:var(--clr-accent)">*</span></label>
                <input type="text" id="libelle" name="libelle" required
                    value="<?= htmlspecialchars($_POST['libelle'] ?? '') ?>"
                    placeholder="e.g. Iran, Sanctions, Military…"
                    autocomplete="off"
                    maxlength="80">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btn-save-tag">💾 Save Tag</button>
                <a href="liste.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </section>
</main>

<?php bo_foot(); ?>