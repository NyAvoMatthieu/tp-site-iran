<?php

/**
 * auth.php – Garde d'authentification
 * ─────────────────────────────────────
 * À inclure en PREMIÈRE ligne de chaque page protégée du backoffice.
 * Démarre la session si elle n'est pas encore active, puis vérifie
 * que l'administrateur est bien connecté. Sinon redirige vers login.php.
 */

declare(strict_types=1);

// Démarrer la session une seule fois
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Calcul de l'URL absolue vers login.php ───────────────────────────────────
// auth.php se trouve dans backoffice/includes/ → login.php est dans backoffice/
$_auth_docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$_auth_backoffice  = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

if ($_auth_docRoot !== '' && str_starts_with($_auth_backoffice, $_auth_docRoot)) {
    $_auth_loginUrl = substr($_auth_backoffice, strlen($_auth_docRoot)) . '/login.php';
} else {
    $_auth_loginUrl = '../login.php';   // fallback relatif
}

// ── Vérification de la session ───────────────────────────────────────────────
if (empty($_SESSION['admin_id'])) {
    // Mémoriser la page demandée pour redirection post-login (optionnel)
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . $_auth_loginUrl);
    exit;
}

// Nettoyage des variables temporaires pour ne pas polluer la portée globale
unset($_auth_docRoot, $_auth_backoffice, $_auth_loginUrl);
