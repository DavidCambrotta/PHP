<?php
declare(strict_types=1);

/**
 * Returns a singleton PDO connection to storage/contact.sqlite.
 * Also bootstraps the schema on first run.
 */
//SQLite function
/*
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dir = __DIR__ . '/storage';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create storage directory.');
    }

    $dsn = 'sqlite:' . $dir . '/contact.sqlite';
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Schema (id, timestamps, fields, status)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS submissions (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT    NOT NULL,
            ip         TEXT,
            name       TEXT    NOT NULL,
            email      TEXT    NOT NULL,
            subject    TEXT    NOT NULL,
            message    TEXT    NOT NULL,
            ua         TEXT,
            status     TEXT    NOT NULL DEFAULT 'new'  -- 'new' | 'read'
        );
        CREATE INDEX IF NOT EXISTS idx_submissions_created_at ON submissions(created_at);
        CREATE INDEX IF NOT EXISTS idx_submissions_status      ON submissions(status);
        CREATE INDEX IF NOT EXISTS idx_submissions_email       ON submissions(email);
    ");

    return $pdo;
}
*/


//MySQL

//version 1
/*
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host = '127.0.0.1'; $dbname = 'contact_app'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at DATETIME NOT NULL,
            ip VARCHAR(45) NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(254) NOT NULL,
            subject VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            ua TEXT NULL,
            status ENUM('new','read') NOT NULL DEFAULT 'new',
            INDEX (created_at), INDEX (status), INDEX (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    return $pdo;
    */
    //version 2
    
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $host = '127.0.0.1';   // use 127.0.0.1 to force TCP on Windows
    $port = 3306;          // XAMPP default; change if you customized MySQL
    $dbname = 'contactform';
    $user = 'contact_user';      // or 'root'
    $pass = 'Contact!123';       // your chosen password (root is usually empty on XAMPP)

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
