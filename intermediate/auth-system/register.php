<?php
require __DIR__ . '/db.php';
session_start();

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($name) < 2) $errors[] = "Name must be at least 2 chars.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 chars.";

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = db()->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)");
            $stmt->execute([$name, $email, $hash]);
            $_SESSION['user'] = ['id' => db()->lastInsertId(), 'name' => $name, 'email' => $email];
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Email already registered.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Register</title></head>
<body>
<h1>Register</h1>
<?php foreach ($errors as $e) echo "<p style='color:red;'>".htmlspecialchars($e)."</p>"; ?>
<form method="post">
    <label>Name: <input type="text" name="name" value="<?=htmlspecialchars($name)?>"></label><br>
    <label>Email: <input type="email" name="email" value="<?=htmlspecialchars($email)?>"></label><br>
    <label>Password: <input type="password" name="password"></label><br>
    <button type="submit">Sign up</button>
</form>
<p><a href="login.php">Already have an account? Login</a></p>
</body>
</html>