<?php

declare(strict_types=1);

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function backoffice_base_url(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptName !== '' && preg_match('#^(.*?/backoffice)(?:/|$)#i', $scriptName, $m)) {
        return rtrim($m[1], '/') . '/';
    }

    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $boDir   = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

    if ($docRoot !== '' && str_starts_with($boDir, $docRoot)) {
        return substr($boDir, strlen($docRoot)) . '/';
    }

    return '/backoffice/';
}

function admin_login_url(): string
{
    return backoffice_base_url() . 'login';
}

function admin_dashboard_url(): string
{
    return backoffice_base_url() . 'dashboard';
}

function admin_is_logged_in(): bool
{
    admin_session_start();
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_login']);
}

function admin_require_auth(): void
{
    admin_session_start();

    if (admin_is_logged_in()) {
        return;
    }

    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . admin_login_url());
    exit;
}

function admin_csrf_token(): string
{
    admin_session_start();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function admin_validate_csrf(?string $token): bool
{
    admin_session_start();
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($token)
        && is_string($sessionToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}
