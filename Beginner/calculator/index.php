<?php
// index.php
declare(strict_types=1);
session_start();

/**
 * Allow dot or comma decimals. Return float|false on bad input.
 */
function to_float($input) {
    if (!is_string($input)) return false;
    $normalized = str_replace(',', '.', trim($input)); // convert EU commas to dot
    $normalized = preg_replace('/\s+/', '', $normalized); // remove spaces
    return filter_var($normalized, FILTER_VALIDATE_FLOAT);
}

/**
 * Format result with thousands groups and trimmed trailing zeros.
 * Example: 1234.5000000000 => "1 234.5", 2.0000 => "2"
 */
function format_result($x): string {
    $s = number_format((float)$x, 10, '.', ' '); // 10 decimals, space thousands
    if (strpos($s, '.') !== false) {
        $s = rtrim($s, '0');
        $s = rtrim($s, '.');
    }
    return $s;
}

$result = null;
$error = '';
$rawA = $_POST['a'] ?? '';
$rawB = $_POST['b'] ?? '';
$op   = $_POST['op'] ?? '+';
$action = $_POST['action'] ?? 'calculate';

// session-backed history (keep last 10)
if (!isset($_SESSION['history']) || !is_array($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'clear_history') {
        $_SESSION['history'] = [];
    } else {
        $a = to_float($rawA);
        $b = to_float($rawB);

        if ($a === false || $b === false) {
            $error = 'Please enter valid numbers (e.g., 12, 3.14, 1,5).';
        } else {
            switch ($op) {
                case '+': $result = $a + $b; break;
                case '-': $result = $a - $b; break;
                case '*': $result = $a * $b; break;
                case '/':
                    if (abs($b) < 1e-12) { $error = 'Division by zero is not allowed.'; }
                    else { $result = $a / $b; }
                    break;
                case '%':
                    if (abs($b) < 1e-12) { $error = 'Modulo by zero is not allowed.'; }
                    else { $result = fmod($a, $b); } // fmod supports floats
                    break;
                case '**':
                    $result = $a ** $b; // exponent
                    break;
                default:
                    $error = 'Unsupported operator.';
            }

            // On success, push into history (newest first, max 10)
            if ($error === '' && $result !== null) {
                array_unshift($_SESSION['history'], [
                    'a' => $a,
                    'op' => $op,
                    'b' => $b,
                    'result' => $result,
                    'ts' => time(),
                ]);
                $_SESSION['history'] = array_slice($_SESSION['history'], 0, 10);
            }
        }
    }
}

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
  .card { background:#fff; padding:24px; border-radius:16px; box-shadow: 0 10px 25px rgba(0,0,0,.06); width: min(720px, 92vw); }
  h1 { margin:0 0 12px; font-size:1.25rem; }
  form { display:grid; gap:12px; }
  .row { display:grid; grid-template-columns: 1fr 140px 1fr; gap:10px; }
  input, select, button {
    font: inherit; padding:10px 12px; border:1px solid #d8dde7; border-radius:10px; outline:none; background:#fff;
  }
  input:focus, select:focus, button:focus { border-color:#7aa2ff; box-shadow:0 0 0 3px rgba(122,162,255,.2); }
  .actions { display:flex; gap:10px; flex-wrap:wrap; }
  .error { background:#ffe8e8; color:#9a1b1b; padding:10px 12px; border-radius:10px; border:1px solid #ffc9c9; }
  .result { background:#eef8ff; color:#0b3d8d; padding:10px 12px; border-radius:10px; border:1px solid #cfe6ff; }
  .help { color:#667085; font-size:.9rem; }
  .history { margin-top:16px; padding:0; list-style:none; }
  .history li { padding:8px 10px; border:1px solid #eef0f5; border-radius:8px; margin-bottom:8px; display:flex; justify-content:space-between; gap:10px; }
  .ts { color:#8a92a6; font-size:.85rem; white-space:nowrap; }
</style>
</head>
<body>
  <div class="card">
    <h1>PHP Calculator</h1>
    <p class="help">
      Enter two numbers (decimals can use <strong>.</strong> or <strong>,</strong>).
      Operators: +, −, ×, ÷, <code>%</code> (mod), <code>**</code> (power).
      Press <kbd>Enter</kbd> to calculate.
    </p>

    <?php if ($error): ?>
      <div class="error"><?= h($error) ?></div>
    <?php elseif ($result !== null): ?>
      <div class="result">
        <strong>Result:</strong>
        <?= h(format_result($result)) ?>
      </div>
    <?php endif; ?>

    <form id="calc-form" method="post" action="">
      <div class="row">
        <input type="text" name="a" placeholder="First number" value="<?= h($rawA) ?>" required />
        <select name="op" aria-label="Operator">
          <option value="+"  <?= $op==='+'?'selected':'' ?>>+</option>
          <option value="-"  <?= $op==='-'?'selected':'' ?>>−</option>
          <option value="*"  <?= $op==='*'?'selected':'' ?>>×</option>
          <option value="/"  <?= $op==='/'?'selected':'' ?>>÷</option>
          <option value="%"  <?= $op==='%'?'selected':'' ?>>%</option>
          <option value="**" <?= $op==='**'?'selected':'' ?>>**</option>
        </select>
        <input type="text" name="b" placeholder="Second number" value="<?= h($rawB) ?>" required />
      </div>
      <div class="actions">
        <button type="submit" name="action" value="calculate">Calculate</button>
        <button type="reset">Reset form</button>
        <button type="submit" name="action" value="clear_history" title="Clear the calculation history">Clear history</button>
      </div>
    </form>

    <?php if (!empty($_SESSION['history'])): ?>
      <h2 style="margin:18px 0 8px; font-size:1.05rem;">History (last 10)</h2>
      <ul class="history">
        <?php foreach ($_SESSION['history'] as $item): ?>
          <li>
            <div>
              <?= h(format_result($item['a'])) ?> <?= h($item['op']) ?>
              <?= h(format_result($item['b'])) ?> = <strong><?= h(format_result($item['result'])) ?></strong>
            </div>
            <span class="ts"><?= date('Y-m-d H:i:s', $item['ts']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Keyboard: submit on Enter anywhere inside the form -->
  <script>
    const form = document.getElementById('calc-form');
    form.addEventListener('keydown', function (e) {
      // If user presses Enter in any input/select, submit (but don't double-submit buttons)
      if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
        e.preventDefault();
        form.requestSubmit();
      }
    });
  </script>
</body>
</html>
