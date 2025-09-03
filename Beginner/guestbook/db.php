<?php
function db() {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            "mysql:host=127.0.0.1;dbname=guestbook;charset=utf8mb4",
            "contact_user",   // change if you use another MySQL user
            "Contact!123",    // change if your password is different
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}
