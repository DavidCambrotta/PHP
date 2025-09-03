<?php
session_start();
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Home</title></head>
<body>
<h1>Home</h1>
<?php if ($user): ?>
    <p>Welcome, <?=htmlspecialchars($user['name'])?>! (<a href="logout.php">Logout</a>)</p>
<?php else: ?>
    <p>You are not logged in. <a href="login.php">Login</a> or <a href="register.php">Register</a></p>
<?php endif ?>
</body>
</html>