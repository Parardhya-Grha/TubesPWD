<?php
// db.php - connects to the database. Every page that needs data loads this file.
require_once __DIR__ . '/config.php';

// Normally we connect to MySQL using the settings in config.php.
// (The CAMPRENT_DSN line only exists so I could test with another database.
//  You can delete the "if" block below and keep just $dsn = "mysql:...".)
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
if (getenv('CAMPRENT_DSN')) {
    $dsn = getenv('CAMPRENT_DSN');
}

try {
    // $pdo is the open connection. All the functions below use it.
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    // Show real error messages while you are learning
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Give each row back as ['nama_produk' => '...'] (a named array)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    http_response_code(500);
    exit('Database connection failed. Check includes/config.php and that MySQL is running.');
}

/*
 * db_query() runs one SQL query.
 *   $sql    = the SQL text, with ? where a value goes
 *   $values = the values for the ? marks, in order
 * Sending values separately (instead of pasting them into the SQL text)
 * stops "SQL injection", so always do it this way for anything a visitor types.
 */
function db_query($sql, $values = [])
{
    global $pdo;                 // "global" lets this function use the $pdo made above
    $statement = $pdo->prepare($sql);
    $statement->execute($values);
    return $statement;
}
