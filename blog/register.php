<?php
session_start();
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header("Location: posts.php"); exit; }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';
    $role     = $_POST['role']     ?? 'reader';

    // Only allow reader/editor on self-registration (admin needs admin panel)
    $allowed_roles = ['reader', 'editor'];
    if (!in_array($role, $allowed_roles, true)) $role = 'reader';

    $errors = [];
    if (!$username)                          $errors[] = "Username is required.";
    elseif (strlen($username) < 3)            $errors[] = "Username must be at least 3 characters.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = "Only letters, numbers and underscores.";

    if (!$password)                          $errors[] = "Password is required.";
    elseif (strlen($password) < 6)            $errors[] = "Password must be at least 6 characters.";

    if ($password && $confirm !== $password)  $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $db  = getDB();
        $chk = $db->prepare("SELECT id FROM users WHERE username = ?");
        $chk->bind_param("s", $username); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = "Username already taken. Please choose another.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $username, $hashed, $role);
            $success = $ins->execute()
                ? "Account created as <strong>" . ucfirst($role) . "</strong>! You can now <a href='login.php'>sign in</a>."
                : "Registration failed. Please try again.";
        }
        $db->close();
    } else { $error = implode('<br>', $errors); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – BlogApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<div class="auth-card" style="max-width:500px">
    <!-- Brand -->
    <div class="auth-brand">
        <span class="auth-brand-icon">🚀</span>
        <span class="auth-brand-name">BlogApp</span>
    </div>

    <h2 class="text-center">Create account</h2>
    <p class="subtitle text-center">Join and start your blogging journey</p>

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-circle-fill mt-1 flex-shrink-0"></i>
        <span><?= $error ?></span>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success d-flex align-items-start gap-2" role="status">
        <i class="bi bi-check-circle-fill mt-1 flex-shrink-0"></i>
        <span><?= $success ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" id="reg-form" novalidate>
        <?= csrf_field() ?>

        <!-- Username -->
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Letters, numbers, underscores"
                       value="<?= e($_POST['username'] ?? '') ?>" required minlength="3" pattern="[a-zA-Z0-9_]+">
            </div>
            <div class="invalid-feedback d-block" id="err-username"></div>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Min. 6 characters" required minlength="6">
                <button type="button" class="btn-pw-toggle" id="pw-toggle">
                    <i class="bi bi-eye" id="eye-icon"></i>
                </button>
            </div>
            <div class="strength-bar mt-2"><div class="strength-fill" id="s-fill"></div></div>
            <small class="text-muted" id="s-label">Password strength</small>
            <div class="invalid-feedback d-block" id="err-password"></div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label for="confirm" class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" id="confirm" name="confirm" class="form-control"
                       placeholder="Repeat your password" required>
            </div>
            <div class="invalid-feedback d-block" id="err-confirm"></div>
        </div>

        <!-- Role Selection -->
        <div class="mb-4">
            <label class="form-label">Account Role</label>
            <div class="row g-2">
                <!-- Reader -->
                <div class="col-6">
                    <input type="radio" class="btn-check" name="role" id="role-reader" value="reader"
                           <?= (($_POST['role'] ?? 'reader') === 'reader') ? 'checked' : '' ?>>
                    <label class="btn w-100 role-option-btn role-reader-btn" for="role-reader">
                        <i class="bi bi-book-fill d-block mb-1" style="font-size:1.4rem"></i>
                        <strong>Reader</strong>
                        <small class="d-block text-muted">Read posts only</small>
                    </label>
                </div>
                <!-- Editor -->
                <div class="col-6">
                    <input type="radio" class="btn-check" name="role" id="role-editor" value="editor"
                           <?= (($_POST['role'] ?? '') === 'editor') ? 'checked' : '' ?>>
                    <label class="btn w-100 role-option-btn role-editor-btn" for="role-editor">
                        <i class="bi bi-pencil-square d-block mb-1" style="font-size:1.4rem"></i>
                        <strong>Editor</strong>
                        <small class="d-block text-muted">Create &amp; manage posts</small>
                    </label>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">
                <i class="bi bi-info-circle me-1"></i>
                <em>Admin role can only be assigned by an existing Admin after registration.</em>
            </small>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-person-plus me-2"></i>Create Account
        </button>
    </form>

    <p class="text-center mt-3 mb-0" style="font-size:.85rem;color:#6b7280">
        Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600">Sign in</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password strength meter
const pw = document.getElementById('password');
const fill = document.getElementById('s-fill');
const lbl = document.getElementById('s-label');
pw.addEventListener('input', () => {
    const v = pw.value;
    const s = [v.length>=6, v.length>=10, /[A-Z]/.test(v), /[0-9]/.test(v), /[^a-zA-Z0-9]/.test(v)].filter(Boolean).length;
    fill.style.width = (s * 20) + '%';
    fill.style.background = ['','#ef4444','#f97316','#eab308','#22c55e','#16a34a'][s];
    lbl.textContent = v ? ['','Weak','Fair','Good','Strong','Very Strong'][s] : 'Password strength';
});

// Password toggle
document.getElementById('pw-toggle').addEventListener('click', function() {
    const p = document.getElementById('password');
    const i = document.getElementById('eye-icon');
    p.type = p.type === 'password' ? 'text' : 'password';
    i.className = p.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

// Client-side validation
document.getElementById('reg-form').addEventListener('submit', function(e) {
    let ok = true;
    const u = document.getElementById('username');
    const p = document.getElementById('password');
    const c = document.getElementById('confirm');
    const eu = document.getElementById('err-username');
    const ep = document.getElementById('err-password');
    const ec = document.getElementById('err-confirm');

    [u,p,c].forEach(x => x.classList.remove('is-invalid'));
    [eu,ep,ec].forEach(x => x.textContent = '');

    if (!u.value.trim()) { eu.textContent='Username required.'; u.classList.add('is-invalid'); ok=false; }
    else if (u.value.trim().length<3) { eu.textContent='Min 3 characters.'; u.classList.add('is-invalid'); ok=false; }
    else if (!/^[a-zA-Z0-9_]+$/.test(u.value)) { eu.textContent='Letters, numbers, underscores only.'; u.classList.add('is-invalid'); ok=false; }

    if (!p.value) { ep.textContent='Password required.'; p.classList.add('is-invalid'); ok=false; }
    else if (p.value.length<6) { ep.textContent='Min 6 characters.'; p.classList.add('is-invalid'); ok=false; }

    if (p.value && c.value !== p.value) { ec.textContent='Passwords do not match.'; c.classList.add('is-invalid'); ok=false; }

    if (!ok) e.preventDefault();
});
</script>

<!-- Role button styling -->
<style>
.role-option-btn {
    border: 2px solid #e5e7eb !important;
    border-radius: 12px !important;
    padding: 14px 10px !important;
    background: #fafafa;
    color: #374151;
    text-align: center;
    transition: all .2s;
    cursor: pointer;
}
.role-option-btn:hover { border-color: #c7d2fe !important; background: #eef2ff; }
.btn-check:checked + .role-reader-btn {
    border-color: #818cf8 !important;
    background: #eef2ff;
    color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
}
.btn-check:checked + .role-editor-btn {
    border-color: #fbbf24 !important;
    background: #fffbeb;
    color: #92400e;
    box-shadow: 0 0 0 3px rgba(245,158,11,.15);
}
.role-reader-btn i { color: #6366f1; }
.role-editor-btn i { color: #d97706; }
</style>
</body>
</html>
