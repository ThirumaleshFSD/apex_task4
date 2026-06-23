<?php
session_start();
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header("Location: posts.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];
    if (!$username) $errors[] = "Username is required.";
    if (!$password) $errors[] = "Password is required.";

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'] ?? 'reader';
            $db->close(); header("Location: posts.php"); exit;
        } else { $error = "Invalid username or password."; }
        $db->close();
    } else { $error = implode(' ', $errors); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – BlogApp</title>
    <meta name="description" content="Sign in to your BlogApp account.">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <!-- Brand -->
    <div class="auth-brand">
        <span class="auth-brand-icon">✍️</span>
        <span class="auth-brand-name">BlogApp</span>
    </div>

    <h2 class="text-center">Welcome back</h2>
    <p class="subtitle text-center">Sign in to continue to your dashboard</p>

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="login-form" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Enter your username"
                       value="<?= e($_POST['username'] ?? '') ?>" required autocomplete="username">
            </div>
            <div class="invalid-feedback" id="err-username"></div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Enter your password" required autocomplete="current-password">
                <button type="button" class="btn-pw-toggle" id="pw-toggle" title="Toggle password">
                    <i class="bi bi-eye" id="eye-icon"></i>
                </button>
            </div>
            <div class="invalid-feedback" id="err-password"></div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
    </form>

    <!-- Role Info Panel -->
    <div class="role-info-panel">
        <div class="panel-title"><i class="bi bi-shield-check me-1"></i>Account Roles</div>
        <div class="role-info-item">
            <span class="role-badge badge-admin"><i class="bi bi-star-fill"></i> Admin</span>
            <span>Full access · Manage users &amp; posts</span>
        </div>
        <div class="role-info-item">
            <span class="role-badge badge-editor"><i class="bi bi-pencil-fill"></i> Editor</span>
            <span>Create &amp; manage own posts</span>
        </div>
        <div class="role-info-item">
            <span class="role-badge badge-reader"><i class="bi bi-book-fill"></i> Reader</span>
            <span>Read-only access</span>
        </div>
    </div>

    <p class="text-center mt-3 mb-0" style="font-size:.85rem;color:#6b7280">
        Don't have an account? <a href="register.php" class="fw-600" style="color:var(--primary)">Register here</a>
    </p>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Client-side validation
document.getElementById('login-form').addEventListener('submit', function(e) {
    let ok = true;
    const u = document.getElementById('username');
    const p = document.getElementById('password');
    const eu = document.getElementById('err-username');
    const ep = document.getElementById('err-password');

    u.classList.remove('is-invalid'); p.classList.remove('is-invalid');

    if (!u.value.trim()) {
        u.classList.add('is-invalid'); eu.textContent = 'Username is required.'; ok = false;
    }
    if (!p.value) {
        p.classList.add('is-invalid'); ep.textContent = 'Password is required.'; ok = false;
    }
    if (!ok) e.preventDefault();
});

// Password toggle
document.getElementById('pw-toggle').addEventListener('click', function() {
    const pw = document.getElementById('password');
    const ic = document.getElementById('eye-icon');
    if (pw.type === 'password') { pw.type = 'text'; ic.className = 'bi bi-eye-slash'; }
    else { pw.type = 'password'; ic.className = 'bi bi-eye'; }
});
</script>
</body>
</html>
