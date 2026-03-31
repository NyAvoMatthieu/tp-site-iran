<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

admin_session_start();

if (admin_is_logged_in()) {
    header('Location: ' . admin_dashboard_url());
    exit;
}

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $token    = (string) ($_POST['csrf_token'] ?? '');

    if (!admin_validate_csrf($token)) {
        $error = 'Session invalide. Veuillez reessayer.';
    } elseif ($login === '' || $password === '') {
        $error = 'Veuillez renseigner le login et le mot de passe.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id, login, pswd FROM admin WHERE login = :login LIMIT 1');
        $stmt->execute([':login' => $login]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, (string) $admin['pswd'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_login'] = (string) $admin['login'];

            $pdo->prepare('UPDATE admin SET last_login = NOW() WHERE id = :id')
                ->execute([':id' => (int) $admin['id']]);

            $redirect = $_SESSION['redirect_after_login'] ?? admin_dashboard_url();
            unset($_SESSION['redirect_after_login'], $_SESSION['csrf_token']);

            if (!is_string($redirect) || $redirect === '' || str_contains($redirect, '://')) {
                $redirect = admin_dashboard_url();
            }

            header('Location: ' . $redirect);
            exit;
        }

        $error = 'Identifiants invalides.';
    }
}

$csrfToken = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Backoffice</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 420px;
            margin: 40px auto;
            padding: 0 16px;
        }

        form {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
        }

        label {
            display: block;
            margin: 10px 0 4px;
        }

        input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        button {
            margin-top: 14px;
            padding: 10px 14px;
        }

        .error {
            color: #b00020;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>Connexion backoffice</h1>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <label for="login">Login</label>
        <input id="login" name="login" type="text" required value="admin">

        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" required value="admin123">

        <button type="submit">Se connecter</button>
    </form>
</body>

</html>