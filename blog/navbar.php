<?php
// navbar.php – Bootstrap 5 shared navigation
$cur = basename($_SERVER['PHP_SELF']);
?>
<!-- Scroll progress indicator -->
<div id="scroll-progress"></div>

<!-- Custom Toast Notification Container -->
<div class="custom-toast-container" id="toast-container"></div>

<nav class="navbar navbar-expand-lg sticky-top glass-nav">
    <div class="container-fluid px-4">
        <!-- Brand -->
        <a class="navbar-brand" href="posts.php">
            <span class="brand-dot"></span>BlogApp
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="bi bi-list fs-4" style="color:#4f46e5"></i>
        </button>

        <!-- Nav links -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto ms-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $cur==='posts.php'?'active':'' ?>" href="posts.php">
                        <i class="bi bi-grid me-1"></i>Posts
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $cur==='admin.php'?'active':'' ?>" href="admin.php">
                        <i class="bi bi-shield-check me-1"></i>Admin Panel
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Right side -->
            <div class="d-flex align-items-center gap-2">
                <?php if (canWrite()): ?>
                <a href="post_form.php" class="btn btn-primary btn-sm d-none d-md-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-lg"></i> New Post
                </a>
                <?php endif; ?>

                <!-- User dropdown -->
                <div class="dropdown">
                    <div class="user-pill dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer">
                        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'],0,1)) ?></div>
                        <span class="d-none d-sm-inline" style="color:#374151;font-size:.84rem;font-weight:600"><?= e($_SESSION['username']) ?></span>
                        <?= roleBadge(getRole()) ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border border-light mt-1" style="border-radius:12px;min-width:200px">
                        <li class="px-3 py-2 border-bottom">
                            <strong style="font-size:.85rem;color:#1e1b4b"><?= e($_SESSION['username']) ?></strong><br>
                            <small class="text-muted"><?= ucfirst(getRole()) ?> Account</small>
                        </li>
                        <?php if (canWrite()): ?>
                        <li><a class="dropdown-item py-2" href="post_form.php"><i class="bi bi-pencil me-2"></i>New Post</a></li>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item py-2" href="admin.php"><i class="bi bi-people me-2"></i>Manage Users</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
// Global Toast System
window.showToast = function(title, message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `custom-toast toast-${type}`;
    
    let iconClass = 'bi-check-circle-fill';
    if (type === 'danger') iconClass = 'bi-exclamation-octagon-fill';
    else if (type === 'warning') iconClass = 'bi-exclamation-triangle-fill';
    else if (type === 'info') iconClass = 'bi-info-circle-fill';
    
    toast.innerHTML = `
        <div class="toast-icon"><i class="bi ${iconClass}"></i></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>
    `;
    
    container.appendChild(toast);
    
    // Auto-remove animation
    setTimeout(() => {
        toast.classList.add('hide');
        toast.addEventListener('transitionend', () => toast.remove());
        // Fallback in case transition event doesn't fire
        setTimeout(() => toast.remove(), 400);
    }, 4000);
};
</script>

