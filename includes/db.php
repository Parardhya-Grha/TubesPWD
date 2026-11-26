<?php
require_once __DIR__ . '/config.php';

/**
 * Shared PDO connection (prepared statements everywhere).
 * CAMPRENT_DSN is an optional override, only used for automated tests.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = getenv('CAMPRENT_DSN') ?: sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('CampRent DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Database connection failed. Check includes/config.php and that MySQL/MariaDB is running.');
    }

    return $pdo;
}

/** Run a prepared query. Integers are bound as integers (needed for LIMIT/OFFSET). */
function db_query(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    foreach (array_values($params) as $i => $value) {
        $st->bindValue($i + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    return $st;
}
