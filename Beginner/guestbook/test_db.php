<?php
require __DIR__ . '/db.php';

try {
    $pdo = db();
    echo "✅ Database connection successful!<br>";

    // Safer test: version + timestamp (use a simple alias)
    $row = $pdo->query("SELECT VERSION() AS server_version, NOW() AS ts")->fetch(PDO::FETCH_ASSOC);
    echo "Server: " . htmlspecialchars($row['server_version']) . " — Time: " . htmlspecialchars($row['ts']) . "<br>";
} catch (Throwable $e) {
    echo "❌ Database query failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES);
}
