<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo = getDB();
$id  = (int) ($_POST['id'] ?? 0);

if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        /* Remove from pivot first */
        $pdo->prepare("DELETE FROM contenu_tag WHERE id_tag = :id")->execute([':id' => $id]);
        $pdo->prepare("DELETE FROM tag WHERE id = :id")->execute([':id' => $id]);
        header('Location: liste.php?flash=' . urlencode('Tag deleted.'));
    } catch (PDOException $e) {
        header('Location: liste.php?flash=' . urlencode('Error: ' . $e->getMessage()));
    }
} else {
    header('Location: liste.php');
}
exit;
