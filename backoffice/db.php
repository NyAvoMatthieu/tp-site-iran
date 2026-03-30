<?php

/**
 * Database connection – PostgreSQL via PDO
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
            die('<p style="color:red;font-family:sans-serif;">DB Error: '
                . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }

    return $pdo;
}
