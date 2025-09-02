<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$pdo = db();

/** Handle POST actions: delete, toggle status */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        exit('Bad CSRF token');
    }
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && $action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM submissions WHERE id = :id');
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?msg=deleted'); exit;
    }
    if ($id > 0 && $action === 'toggle') {
        $stmt = $pdo->prepare('UPDATE submissions SET status = CASE status WHEN "new" THEN "read" ELSE "new" END WHERE id = :id');
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?id=' . $id); exit;
    }
}

/** View single? */
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/** Listing params */
$q       = trim((string)($_GET['q'] ?? ''));
$status  = $_GET['status'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE :q OR email LIKE :q OR subject LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if (in_array($status, ['new','read'], true)) {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/** Single view fetch */
$one = null;
if ($viewId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM submissions WHERE id = :id');
    $stmt->execute([':id' => $viewId]);
    $one = $stmt->fetch();
}

/** Count + list */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$listSql = "SELECT * FROM submissions $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset"; // integers are safe
$stmt = $pdo->prepare($listSql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pages = max(1, (int)ceil($total / $perPage));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Submissions (SQLite)</title>
<style>
  :root { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
  body { background:#f6f7f9; margin:0; padding:20px; }
  .wrap { max-width: 1000px; margin:0 auto; }
  h1 { margin:0 0 12px; font-size:1.25rem; }
  .bar { display:flex; gap:10px; align-items:center; margin-bottom:14px; flex-wrap:wrap; }
  input, select, button { padding:8px 10px; border:1px solid #d8dde7; border-radius:8px; font:inherit; }
  table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; }
  th, td { padding:10px; border-bottom:1px solid #eef0f5; text-align:left; vertical-align:top; }
  tr:last-child td { border-bottom:none; }
  .tag { padding:2px 8px; border-radius:999px; border:1px solid #d8dde7; font-size:.85rem; }
  .tag.new { background:#fff8e8; border-color:#ffd9a5; }
  .tag.read { background:#e7fbef; border-color:#baf2d2; }
  .actions { display:flex; gap:8px; }
  .card { background:#fff; padding:16px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,.06); margin-top:16px; }
  .muted { color:#8a92a6; font-size:.9rem; }
  .pager { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
  .pager a { padding:6px 10px; border:1px solid #d8dde7; border-radius:8px; text-decoration:none; }
  .danger { background:#ffe8e8; border:1px solid #ffc9c9; }
</style>
</head>
<body>
  <div class="wrap">
    <h1>Submissions</h1>

    <form class="bar" method="get" action="">
      <input type="text" name="q" placeholder="Search name/email/subject" value="<?= h($q) ?>">
      <select name="status">
        <option value="">All statuses</option>
        <option value="new"  <?= $status==='new'?'selected':'' ?>>New</option>
        <option value="read" <?= $status==='read'?'selected':'' ?>>Read</option>
      </select>
      <button type="submit">Filter</button>
      <a href="index.php">← Back to form</a>
    </form>

    <?php if ($viewId > 0 && $one): ?>
      <div class="card">
        <h2>#<?= (int)$one['id'] ?> — <?= h($one['subject']) ?></h2>
        <p class="muted">
          <strong><?= h($one['name']) ?></strong> &lt;<?= h($one['email']) ?>&gt; ·
          <?= h($one['created_at']) ?> · IP: <?= h((string)$one['ip']) ?> · UA: <?= h((string)$one['ua']) ?>
        </p>
        <pre style="white-space:pre-wrap;"><?= h($one['message']) ?></pre>

        <form method="post" class="actions">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="id" value="<?= (int)$one['id'] ?>">
          <button name="action" value="toggle" type="submit">
            Mark as <?= $one['status']==='new' ? 'read' : 'new' ?>
          </button>
          <button name="action" value="delete" type="submit" class="danger" onclick="return confirm('Delete this submission?');">
            Delete
          </button>
        </form>
      </div>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>ID</th><th>Created</th><th>From</th><th>Subject</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><span class="muted"><?= h($r['created_at']) ?></span></td>
          <td><?= h($r['name']) ?><br><span class="muted"><?= h($r['email']) ?></span></td>
          <td><?= h($r['subject']) ?></td>
          <td><span class="tag <?= h($r['status']) ?>"><?= h($r['status']) ?></span></td>
          <td class="actions">
            <a href="admin.php?id=<?= (int)$r['id'] ?>">View</a>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button name="action" value="toggle" type="submit" title="Toggle status">Toggle</button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this submission?');">
              <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button name="action" value="delete" type="submit" class="danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="pager">
      <?php for ($p=1; $p <= $pages; $p++): ?>
        <?php
          $qs = http_build_query(array_filter(['q'=>$q, 'status'=>$status, 'page'=>$p]));
        ?>
        <a href="?<?= h($qs) ?>"<?= $p===$page ? ' style="font-weight:700;"' : '' ?>><?= $p ?></a>
      <?php endfor; ?>
    </div>
  </div>
</body>
</html>
