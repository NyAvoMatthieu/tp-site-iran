<?php

/**
 * Database connection – PostgreSQL via PDO
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn  = 'pgsql:host=localhost;dbname=iran_war';
        $user = 'postgres';
        $pass = 'postgre';

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            ensureSeoSchema($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            die('<p style="color:red;font-family:sans-serif;">DB Error: '
                . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }

    return $pdo;
}

function ensureSeoSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;

    $sql = [
        'ALTER TABLE contenu ADD COLUMN IF NOT EXISTS meta_title VARCHAR(60)',
        'ALTER TABLE contenu ADD COLUMN IF NOT EXISTS meta_description VARCHAR(160)',
        'ALTER TABLE contenu ADD COLUMN IF NOT EXISTS keywords TEXT',
        'ALTER TABLE contenu ADD COLUMN IF NOT EXISTS author_name VARCHAR(120)',
    ];

    foreach ($sql as $stmt) {
        $pdo->exec($stmt);
    }
}
