<?php
require 'db.php';

$name = $message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters.";
    }
    if ($message === '' || strlen($message) < 3) {
        $errors[] = "Message must be at least 3 characters.";
    }

    if (!$errors) {
        $stmt = db()->prepare("INSERT INTO guestbook (name, message) VALUES (?, ?)");
        $stmt->execute([$name, $message]);

        // redirect to avoid resubmission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$comments = db()->query("SELECT * FROM guestbook ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Guestbook</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        form { margin-bottom: 2rem; }
        .comment { border-bottom: 1px solid #ddd; padding: 1rem 0; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Guestbook</h1>

    <?php if ($errors): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <form method="post">
        <label>Name:<br><input type="text" name="name" value="<?= htmlspecialchars($name) ?>"></label><br><br>
        <label>Message:<br><textarea name="message" rows="3" cols="40"><?= htmlspecialchars($message) ?></textarea></label><br><br>
        <button type="submit">Post Comment</button>
    </form>

    <h2>Comments</h2>
    <?php foreach ($comments as $c): ?>
        <div class="comment">
            <strong><?= htmlspecialchars($c['name']) ?></strong>
            <em><?= $c['created_at'] ?></em>
            <p><?= nl2br(htmlspecialchars($c['message'])) ?></p>
        </div>
    <?php endforeach ?>
</body>
</html>
