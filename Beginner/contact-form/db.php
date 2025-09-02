<?php
declare(strict_types=1);

/**
 * Returns a singleton PDO connection to storage/contact.sqlite.
 * Also bootstraps the schema on first run.
 */
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
