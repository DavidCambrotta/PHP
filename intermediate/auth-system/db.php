<?php
function db() {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            "mysql:host=127.0.0.1;dbname=authsystem;charset=utf8mb4",
            "contact_user",   // change if different
            "Contact!123",    // your MySQL password
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}