<?php
declare(strict_types=1);
session_start();

/**
 * STEP 1 — CSRF token bootstrap (protects against cross-site request forgery)
 * We generate a random token and store it in the session, then verify it on POST.
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Helpers
 */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function str_len(string $s): int { return mb_strlen($s, 'UTF-8'); }

/**
 * State for this request
 */
$errors = [];           // per-field errors: ['name' => 'Too short', ...]
$old = [                // sticky values (what the user typed)
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => '',
];
$successMessage = '';
$warningMessage = '';

/**
 * STEP 2 — Handle POST (form submission)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2a) Basic anti-bot measures
    $postedToken = $_POST['csrf'] ?? '';
    $honeypot    = trim((string)($_POST['website'] ?? '')); // must stay empty

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
        $errors['form'] = 'Invalid form token. Please reload and try again.';
    }
    if ($honeypot !== '') {
        $errors['form'] = 'Submission blocked.';
    }

    // 2b) Read and trim inputs
    $old['name']    = trim((string)($_POST['name'] ?? ''));
    $old['email']   = trim((string)($_POST['email'] ?? ''));
    $old['subject'] = trim((string)($_POST['subject'] ?? ''));
    $old['message'] = trim((string)($_POST['message'] ?? ''));

    // 2c) Rate limit: minimum 5 seconds between submissions
    $now = time();
    $last = $_SESSION['last_submit'] ?? 0;
    if ($now - $last < 5) {
        $errors['form'] = 'Please wait a few seconds before submitting again.';
    }

    // 2d) Validate each field
    // Name: 2–60 chars, letters/space/’- allowed
    if ($old['name'] === '') {
        $errors['name'] = 'Name is required.';
    } elseif (str_len($old['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    } elseif (str_len($old['name']) > 60) {
        $errors['name'] = 'Name must be at most 60 characters.';
    } elseif (!preg_match("/^[\p{L}\p{M}'\- ]+$/u", $old['name'])) {
        $errors['name'] = 'Use letters, spaces, apostrophes, or hyphens only.';
    }

    // Email: required + valid format
    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    // Subject: 3–100 chars
    if ($old['subject'] === '') {
        $errors['subject'] = 'Subject is required.';
    } elseif (str_len($old['subject']) < 3) {
        $errors['subject'] = 'Subject must be at least 3 characters.';
    } elseif (str_len($old['subject']) > 100) {
        $errors['subject'] = 'Subject must be at most 100 characters.';
    }

    // Message: 10–2000 chars
    if ($old['message'] === '') {
        $errors['message'] = 'Message is required.';
    } elseif (str_len($old['message']) < 10) {
        $errors['message'] = 'Message must be at least 10 characters.';
    } elseif (str_len($old['message']) > 2000) {
        $errors['message'] = 'Message must be at most 2000 characters.';
    }

    // 2e) If valid: persist to a file (simulates handling the submission)
    if (!$errors) {
        $dir = __DIR__ . '/storage/submissions';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $warningMessage = 'Could not create submissions directory. Submission not saved.';
        } else {
            $record = [
                'ts'      => gmdate('c'),
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
                'name'    => $old['name'],
                'email'   => $old['email'],
                'subject' => $old['subject'],
                'message' => $old['message'],
                'ua'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ];
            $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            $file = $dir . '/' . date('Y-m-d') . '.log';
            if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
                $warningMessage = 'Could not save your submission to disk.';
            }
        }

        // 2f) (Optional) Here you could send an email using a library like PHPMailer.

        // 2g) Success UX: message + clear the form values
        $successMessage = 'Thanks! Your message has been received.';
        $_SESSION['last_submit'] = $now;
        // Rotate the CSRF token after each successful POST
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // Clear sticky values so the form shows blank after success
        $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contact Form (PHP)</title>
<style>
  :root { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
  body { background:#f6f7f9; margin:0; display:flex; min-height:100svh; align-items:center; justify-content:center; }
  .card { background:#fff; padding:24px; border-radius:16px; width:min(760px, 92vw); box-shadow:0 10px 25px rgba(0,0,0,.06); }
  h1 { margin:0 0 12px; font-size:1.25rem; }
  p.help { color:#667085; margin:0 0 12px; }
  form { display:grid; gap:12px; }
  .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
  label { display:block; font-weight:600; margin-bottom:6px; }
  input[type="text"], input[type="email"], textarea {
    width:100%; padding:10px 12px; border:1px solid #d8dde7; border-radius:10px; font:inherit; outline:none; background:#fff;
  }
  textarea { min-height:120px; resize:vertical; }
  input:focus, textarea:focus { border-color:#7aa2ff; box-shadow:0 0 0 3px rgba(122,162,255,.2); }
  .actions { display:flex; gap:10px; flex-wrap:wrap; }
  button { padding:10px 14px; border:1px solid #d8dde7; border-radius:10px; background:#0b63f6; color:#fff; font:inherit; cursor:pointer; }
  button.secondary { background:#fff; color:#0b63f6; }
  .error { background:#ffe8e8; color:#9a1b1b; padding:10px 12px; border-radius:10px; border:1px solid #ffc9c9; }
  .success { background:#e7fbef; color:#116d47; padding:10px 12px; border-radius:10px; border:1px solid #baf2d2; }
  .warning { background:#fff8e1; color:#7a5b00; padding:10px 12px; border-radius:10px; border:1px solid #ffe08a; }
  .field-error { color:#9a1b1b; font-size:.9rem; margin-top:6px; }
  .sr-only { position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }
</style>
</head>
<body>
  <div class="card">
    <h1>Contact Us</h1>
    <p class="help">All fields are required. We’ll keep your info private.</p>

    <?php if (!empty($errors['form'])): ?>
      <div class="error" role="alert"><?= h($errors['form']) ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
      <div class="success" role="status"><?= h($successMessage) ?></div>
    <?php endif; ?>

    <?php if ($warningMessage): ?>
      <div class="warning" role="status"><?= h($warningMessage) ?></div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
      <!-- CSRF token -->
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_token']) ?>">

      <!-- Honeypot (hidden from humans, bots fill it) -->
      <div class="sr-only" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="row">
        <div>
          <label for="name">Name</label>
          <input id="name" name="name" type="text" required value="<?= h($old['name']) ?>" maxlength="60" autocomplete="name" />
          <?php if (!empty($errors['name'])): ?>
            <div class="field-error"><?= h($errors['name']) ?></div>
          <?php endif; ?>
        </div>
        <div>
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required value="<?= h($old['email']) ?>" maxlength="254" autocomplete="email" />
          <?php if (!empty($errors['email'])): ?>
            <div class="field-error"><?= h($errors['email']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <label for="subject">Subject</label>
        <input id="subject" name="subject" type="text" required value="<?= h($old['subject']) ?>" maxlength="100" />
        <?php if (!empty($errors['subject'])): ?>
          <div class="field-error"><?= h($errors['subject']) ?></div>
        <?php endif; ?>
      </div>

      <div>
        <label for="message">Message</label>
        <textarea id="message" name="message" required maxlength="2000"><?= h($old['message']) ?></textarea>
        <?php if (!empty($errors['message'])): ?>
          <div class="field-error"><?= h($errors['message']) ?></div>
        <?php endif; ?>
      </div>

      <div class="actions">
        <button type="submit">Send message</button>
        <button type="reset" class="secondary">Clear</button>
      </div>
    </form>
  </div>
</body>
</html>
