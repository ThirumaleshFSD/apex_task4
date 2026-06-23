<?php
/**
 * Blog App – One-time Database Setup Script
 * Run this ONCE at: http://localhost/Apex_planet_project/blog/setup.php
 * Delete this file after running for security.
 */

// ── Adjust these if needed ──
$host = 'localhost';
$user = 'root';
$pass = 'Thiru@1206';  // MySQL root password
// ────────────────────────────

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("<div style='color:red;font-family:monospace;padding:20px'>
        ❌ Connection failed: " . $conn->connect_error . "<br><br>
        Edit <b>setup.php</b> and update the \$pass variable with your MySQL password.
    </div>");
}

$sql = "
    CREATE DATABASE IF NOT EXISTS blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    USE blog;
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
";

$steps = [
    "CREATE DATABASE IF NOT EXISTS blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `blog`.`users` (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, role ENUM('user','admin','editor') NOT NULL DEFAULT 'user', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS `blog`.`posts` (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, user_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES `blog`.`users`(id) ON DELETE CASCADE)",
];

$results = [];
foreach ($steps as $step) {
    if ($conn->query($step)) {
        $results[] = ['ok' => true,  'sql' => $step];
    } else {
        $results[] = ['ok' => false, 'sql' => $step, 'err' => $conn->error];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: #0d0d1a; color: #e2e0ff; min-height: 100vh;
               display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #1a1a35; border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;
                padding: 40px; max-width: 600px; width: 100%; }
        h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px;
             background: linear-gradient(135deg,#fff 30%,#a78bfa); -webkit-background-clip: text;
             -webkit-text-fill-color: transparent; background-clip: text; }
        p.sub { color: #8b8fad; margin-bottom: 28px; }
        .step { padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; font-size: 0.88rem; }
        .ok  { background: rgba(34,197,94,0.12); color: #86efac; border: 1px solid rgba(34,197,94,0.3); }
        .err { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }
        .step .label { font-weight: 700; margin-bottom: 4px; }
        code { font-size: 0.78rem; color: #a78bfa; word-break: break-all; }
        .actions { margin-top: 28px; display: flex; gap: 12px; }
        .btn { display: inline-block; padding: 11px 22px; border-radius: 8px; font-family: inherit;
               font-weight: 600; font-size: 0.9rem; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #7c3aed; color: #fff; }
        .btn-primary:hover { background: #6d28d9; }
        .btn-ghost { background: transparent; color: #8b8fad; border: 1px solid rgba(255,255,255,0.08); }
        .warning { background: rgba(245,158,11,0.12); color: #fcd34d; border: 1px solid rgba(245,158,11,0.3);
                   padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; margin-top: 20px; }
    </style>
</head>
<body>
<div class="card">
    <h1>⚙️ Database Setup</h1>
    <p class="sub">Setting up the <strong>blog</strong> database and tables…</p>

    <?php foreach ($results as $r): ?>
        <div class="step <?= $r['ok'] ? 'ok' : 'err' ?>">
            <div class="label"><?= $r['ok'] ? '✅ Success' : '❌ Failed' ?></div>
            <code><?= htmlspecialchars(substr($r['sql'], 0, 80)) ?>…</code>
            <?php if (!$r['ok']): ?>
                <div style="margin-top:4px">Error: <?= htmlspecialchars($r['err']) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php $allOk = !in_array(false, array_column($results, 'ok')); ?>
    <?php if ($allOk): ?>
        <div class="warning">⚠️ Setup complete! <strong>Delete this file</strong> (setup.php) before going to production.</div>
        <div class="actions">
            <a href="login.php" class="btn btn-primary">Go to Blog →</a>
        </div>
    <?php else: ?>
        <div class="warning">Some steps failed. Check your MySQL credentials in <strong>db.php</strong> and <strong>setup.php</strong>.</div>
    <?php endif; ?>
</div>
</body>
</html>
