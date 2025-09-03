<?php
require __DIR__ . '/db.php';
session_start();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = ['id'=>$user['id'], 'name'=>$user['name'], 'email'=>$user['email']];
        header("Location: index.php");
        exit;
    } else {
        $errors[] = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Login</title></head>
<body>
<h1>Login</h1>
<?php foreach ($errors as $e) echo "<p style='color:red;'>".htmlspecialchars($e)."</p>"; ?>
<form method="post">
    <label>Email: <input type="email" name="email" value="<?=htmlspecialchars($email)?>"></label><br>
    <label>Password: <input type="password" name="password"></label><br>
    <button type="submit">Login</button>
</form>
<p><a href="register.php">Need an account? Register</a></p>
</body>
</html>