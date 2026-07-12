<?php
// ================================================
//  Configuration base de données
//  Modifier selon votre configuration XAMPP
// ================================================
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_USER', 'if0_41573920');
define('DB_PASS', 'E7ReqR1ZYsc');
define('DB_NAME', 'if0_41573920_portfolio_db');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Erreur base de données.']));
    }
    return $pdo;
}
