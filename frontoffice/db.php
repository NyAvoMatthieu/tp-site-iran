<?php

/**
 * Database connection using PDO + PostgreSQL
 */
function getDB(): PDO {
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
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<p style="color:red;font-family:sans-serif;">Database connection failed: '
                 . htmlspecialchars($e->getMessage()) . '</p>';
            exit;
        }
    }

    return $pdo;
}
