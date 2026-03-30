<?php

/**
 * logout.php – Déconnexion de l'administrateur
 * ─────────────────────────────────────────────
 * Détruit la session en cours et redirige vers la page de connexion.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vider toutes les variables de session
$_SESSION = [];

// Supprimer le cookie de session côté navigateur
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Détruire la session serveur
session_destroy();

// Calcul de l'URL de login (même logique que auth.php)
$_docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$_currentDir = rtrim(str_replace('\\', '/', __DIR__), '/');

if ($_docRoot !== '' && str_starts_with($_currentDir, $_docRoot)) {
    $loginUrl = substr($_currentDir, strlen($_docRoot)) . '/login.php';
} else {
    $loginUrl = 'login.php';
}

header('Location: ' . $loginUrl . '?logged_out=1');
exit;
