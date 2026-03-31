<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../layout.php';

admin_require_auth();

$pdo = getDB();
$id  = (int) ($_POST['id'] ?? 0);

if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->prepare("DELETE FROM image WHERE id = :id")->execute([':id' => $id]);
        header('Location: ' . bo_base_path() . 'image-ajouter?flash=' . urlencode('Image removed.'));
    } catch (PDOException $e) {
        header('Location: ' . bo_base_path() . 'image-ajouter?flash=' . urlencode('Error: ' . $e->getMessage()));
    }
} else {
    header('Location: ' . bo_base_path() . 'image-ajouter');
}
exit;
