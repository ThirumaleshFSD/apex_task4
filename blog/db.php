<?php
// ════════════════════════════════════════════════════════════════
//  Blog App – db.php  (Core helpers: DB, CSRF, Roles, Security)
// ════════════════════════════════════════════════════════════════
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Thiru@1206');
define('DB_NAME', 'blog');

// ── Database Connection ──────────────────────────────────────────
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("<div style='font-family:monospace;color:red;padding:20px'>
            ❌ DB Connection failed: " . htmlspecialchars($conn->connect_error) . "
        </div>");
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── CSRF Protection ──────────────────────────────────────────────
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;text-align:center">
            <h2 style="color:#ef4444">⛔ Invalid Request</h2>
            <p>CSRF token mismatch. <a href="javascript:history.back()">Go back</a></p>
        </div>');
    }
}

// ── Role System ──────────────────────────────────────────────────
/*
 * Roles (ENUM in DB):
 *   admin  — full access: manage users, all posts, all settings
 *   editor — can create + edit/delete OWN posts, read all
 *   reader — read-only, cannot create/edit/delete posts
 */

function getRole()    { return $_SESSION['role'] ?? 'reader'; }
function isAdmin()    { return getRole() === 'admin'; }
function isEditor()   { return getRole() === 'editor'; }
function isReader()   { return getRole() === 'reader'; }

// Can user write (create new posts)?
function canWrite()   { return isAdmin() || isEditor(); }

// Can user edit a specific post? (admin = any post, editor = own posts only)
function canEditPost($post_user_id) {
    return isAdmin() || (isEditor() && $_SESSION['user_id'] == $post_user_id);
}

// Can user delete a specific post?
function canDeletePost($post_user_id) {
    return isAdmin() || (isEditor() && $_SESSION['user_id'] == $post_user_id);
}

// Redirect helpers
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: posts.php?err=forbidden");
        exit;
    }
}

function requireWriter() {
    requireLogin();
    if (!canWrite()) {
        header("Location: posts.php?err=readonly");
        exit;
    }
}

// ── XSS / Output helpers ─────────────────────────────────────────
function e($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }

// Role badge HTML
function roleBadge($role) {
    $map = [
        'admin'  => ['label' => 'Admin',  'class' => 'badge-admin'],
        'editor' => ['label' => 'Editor', 'class' => 'badge-editor'],
        'reader' => ['label' => 'Reader', 'class' => 'badge-reader'],
        'user'   => ['label' => 'Reader', 'class' => 'badge-reader'],
    ];
    $r = $map[$role] ?? $map['reader'];
    return '<span class="role-badge ' . $r['class'] . '">' . $r['label'] . '</span>';
}
