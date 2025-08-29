<?php
// index.php
declare(strict_types=1);

// Helper: allow both dot and comma decimals; return float|false
function to_float($input) {
    if (!is_string($input)) return false;
    // normalize European commas to dot
    $normalized = str_replace(',', '.', trim($input));
    // allow thousands separators like 1,234.56 or 1 234,56
    $normalized = preg_replace('/\s+/', '', $normalized);
    return filter_var($normalized, FILTER_VALIDATE_FLOAT);
}

$result = null;
$error = '';
$rawA = $_POST['a'] ?? '';
$rawB = $_POST['b'] ?? '';
$op   = $_POST['op'] ?? '+';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = to_float($rawA);
    $b = to_float($rawB);

    // Validate inputs
    if ($a === false || $b === false) {
        $error = 'Please enter valid numbers (e.g., 12, 3.14, 1,5).';
    } else {
        switch ($op) {
            case '+':
                $result = $a + $b;
                break;
            case '-':
                $result = $a - $b;
                break;
            case '*':
                $result = $a * $b;
                break;
            case '/':
                if (abs($b) < 1e-12) {
                    $error = 'Division by zero is not allowed.';
                } else {
                    $result = $a / $b;
                }
                break;
            default:
                $error = 'Unsupported operator.';
        }
    }
}

// Utility for sticky form values
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>PHP Calculator</title>
<style>
  :root { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
  body { display:flex; min-height:100svh; align-items:center; justify-content:center; background:#f6f7f9; margin:0; }
  .card { background:#fff; padding:24px; border-radius:16px; box-shadow: 0 10px 25px rgba(0,0,0,.06); width: min(520px, 92vw); }
  h1 { margin:0 0 16px; font-size:1.25rem; }
  form { display:grid; gap:12px; }
  .row { display:grid; grid-template-columns: 1fr 110px 1fr; gap:10px; }
  input, select, button {
    font: inherit; padding:10px 12px; border:1px solid #d8dde7; border-radius:10px; outline:none;
  }
  input:focus, select:focus { border-color:#7aa2ff; box-shadow:0 0 0 3px rgba(122,162,255,.2); }
  .actions { display:flex; gap:10px; }
  .error { background:#ffe8e8; color:#9a1b1b; padding:10px 12px; border-radius:10px; border:1px solid #ffc9c9; }
  .result { background:#eef8ff; color:#0b3d8d; padding:10px 12px; border-radius:10px; border:1px solid #cfe6ff; }
  .help { color:#667085; font-size:.9rem; }
</style>
</head>
<body>
  <div class="card">
    <h1>PHP Calculator</h1>
    <p class="help">Enter two numbers. Decimals can use <strong>.</strong> or <strong>,</strong>. Choose an operation and press Calculate.</p>

    <?php if ($error): ?>
      <div class="error"><?= h($error) ?></div>
    <?php elseif ($result !== null): ?>
      <div class="result">
        <strong>Result:</strong>
        <?= h(number_format((float)$result, 10, '.', ' ')) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="row">
        <input type="text" name="a" placeholder="First number" value="<?= h($rawA) ?>" required />
        <select name="op" aria-label="Operator">
          <option value="+" <?= $op==='+'?'selected':'' ?>>+</option>
          <option value="-" <?= $op==='-'?'selected':'' ?>>−</option>
          <option value="*" <?= $op==='*'?'selected':'' ?>>×</option>
          <option value="/" <?= $op==='/'?'selected':'' ?>>÷</option>
        </select>
        <input type="text" name="b" placeholder="Second number" value="<?= h($rawB) ?>" required />
      </div>
      <div class="actions">
        <button type="submit">Calculate</button>
        <button type="reset">Clear</button>
      </div>
    </form>
  </div>
</body>
</html>
