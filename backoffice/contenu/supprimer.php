<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo = getDB();
$id  = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    header('Location: ' . bo_base_path() . 'contenu-liste');
    exit;
}

/* ── Load article for display ── */
$stmt = $pdo->prepare("SELECT titre FROM contenu WHERE id = :id");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: ' . bo_base_path() . 'contenu-liste');
    exit;
}

/* ── POST: confirmed delete ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    try {
        $pdo->beginTransaction();
        /* Cascade: pivot, images, then article */
        $pdo->prepare("DELETE FROM contenu_tag WHERE id_contenu = :id")->execute([':id' => $id]);
        $pdo->prepare("DELETE FROM image       WHERE id_contenu = :id")->execute([':id' => $id]);
        $pdo->prepare("DELETE FROM contenu     WHERE id = :id")->execute([':id' => $id]);
        $pdo->commit();
        header('Location: ' . bo_base_path() . 'contenu-liste?flash=' . urlencode('Article deleted successfully.'));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMsg = 'Delete failed: ' . $e->getMessage();
    }
}

bo_head('Delete Article', 'Confirm article deletion in the IranWatch backoffice.');
bo_nav('articles');
$base = bo_base_path();
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="bo-page" role="main">

    <?php if (isset($errorMsg)): ?>
        <?php bo_flash($errorMsg, 'error'); ?>
    <?php endif; ?>

    <div class="confirm-box" role="alertdialog" aria-modal="false" aria-labelledby="confirm-title" aria-describedby="confirm-desc">

        <h2 id="confirm-title">⚠️ Delete Article</h2>
        <p id="confirm-desc">
            You are about to permanently delete:<br>
            <strong><?= htmlspecialchars($article['titre']) ?></strong><br><br>
            This also removes all linked images. This action cannot be undone.
        </p>

        <div class="confirm-actions">
            <form method="post" action="">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger" id="btn-confirm-delete">
                    🗑 Yes, delete permanently
                </button>
            </form>
            <a href="<?= $base ?>contenu-liste" class="btn btn-secondary">Cancel</a>
        </div>

    </div>

</main>

<?php bo_foot(); ?>