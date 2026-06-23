<?php
session_start();
require_once 'db.php';
require_once 'head.php';
requireAdmin();

$db = getDB();
$toast = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_POST['ajax']);
    
    if (isset($_POST['change_role'])) {
        csrf_verify();
        $uid = intval($_POST['user_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? '';
        $success = false;
        $message = '';
        
        if ($uid > 0 && in_array($newRole, ['admin','editor','reader'], true)) {
            if ($uid === (int)$_SESSION['user_id'] && $newRole !== 'admin') {
                $message = 'Cannot change your own role.';
            } else {
                $s = $db->prepare("UPDATE users SET role=? WHERE id=?");
                $s->bind_param("si", $newRole, $uid);
                if ($s->execute()) {
                    $success = true;
                    $message = 'Role updated to ' . ucfirst($newRole) . '.';
                } else {
                    $message = 'Failed to execute query.';
                }
            }
        } else {
            $message = 'Invalid parameters.';
        }
        
        if ($isAjax) {
            header('Content-Type: application/json');
            $stats = [
                'users'   => $db->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'],
                'posts'   => $db->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'],
                'admins'  => $db->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'],
                'editors' => $db->query("SELECT COUNT(*) c FROM users WHERE role='editor'")->fetch_assoc()['c'],
                'readers' => $db->query("SELECT COUNT(*) c FROM users WHERE role='reader'")->fetch_assoc()['c'],
            ];
            echo json_encode([
                'success' => $success,
                'message' => $message,
                'badge' => roleBadge($newRole),
                'stats' => $stats
            ]);
            $db->close();
            exit;
        } else {
            $toast = ($success ? 'success:' : 'danger:') . $message;
        }
    }
    
    if (isset($_POST['delete_user'])) {
        csrf_verify();
        $uid = intval($_POST['user_id'] ?? 0);
        $success = false;
        $message = '';
        
        if ($uid > 0 && $uid !== (int)$_SESSION['user_id']) {
            $db->query("DELETE FROM posts WHERE user_id=$uid");
            $db->query("DELETE FROM users WHERE id=$uid");
            $success = true;
            $message = 'User deleted.';
        } else {
            $message = 'Cannot delete yourself.';
        }
        
        if ($isAjax) {
            header('Content-Type: application/json');
            $stats = [
                'users'   => $db->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'],
                'posts'   => $db->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'],
                'admins'  => $db->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'],
                'editors' => $db->query("SELECT COUNT(*) c FROM users WHERE role='editor'")->fetch_assoc()['c'],
                'readers' => $db->query("SELECT COUNT(*) c FROM users WHERE role='reader'")->fetch_assoc()['c'],
            ];
            echo json_encode([
                'success' => $success,
                'message' => $message,
                'stats' => $stats
            ]);
            $db->close();
            exit;
        } else {
            $toast = ($success ? 'success:' : 'danger:') . $message;
        }
    }
}

$stats = [
    'users'   => $db->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'],
    'posts'   => $db->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'],
    'admins'  => $db->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'],
    'editors' => $db->query("SELECT COUNT(*) c FROM users WHERE role='editor'")->fetch_assoc()['c'],
    'readers' => $db->query("SELECT COUNT(*) c FROM users WHERE role='reader'")->fetch_assoc()['c'],
];
$users = $db->query("SELECT u.id,u.username,u.role,u.created_at,COUNT(p.id) pc FROM users u LEFT JOIN posts p ON p.user_id=u.id GROUP BY u.id ORDER BY u.id ASC");
[$toastType,$toastMsg] = $toast ? explode(':',$toast,2) : ['',''];

renderHead('Admin Panel – BlogApp');
?>
<?php include 'navbar.php'; ?>
<div class="container" style="max-width:1100px; padding-top:32px; padding-bottom:32px;">
    <!-- Toast (Standard fallback) -->
    <?php if ($toastMsg): ?>
    <div class="toast-container">
        <div class="toast show align-items-center text-white bg-<?= $toastType === 'success' ? 'success' : 'danger' ?> border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-<?= $toastType==='success'?'check-circle':'exclamation-circle' ?> me-2"></i><?= e($toastMsg) ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="page-header animate-fade-in-up">
        <h1><i class="bi bi-shield-check me-2" style="color:var(--primary)"></i>Admin Panel</h1>
        <p>Manage users, roles, and permissions</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4 animate-fade-in-up">
        <?php
        $stats_ui = [
            ['label'=>'Total Users',  'val'=>$stats['users'],   'icon'=>'people-fill',   'cls'=>'si-blue'],
            ['label'=>'Total Posts',  'val'=>$stats['posts'],   'icon'=>'file-text-fill','cls'=>'si-purple'],
            ['label'=>'Admins',       'val'=>$stats['admins'],  'icon'=>'star-fill',     'cls'=>'si-red'],
            ['label'=>'Editors',      'val'=>$stats['editors'], 'icon'=>'pencil-fill',   'cls'=>'si-yellow'],
            ['label'=>'Readers',      'val'=>$stats['readers'], 'icon'=>'book-fill',     'cls'=>'si-green'],
        ];
        foreach ($stats_ui as $s): 
            $sKey = strtolower(str_replace('Total ', '', $s['label'])); ?>
        <div class="col-6 col-md-4 col-lg-2_4" style="flex:0 0 auto;width:20%">
            <div class="stat-card">
                <div class="stat-icon <?= $s['cls'] ?>"><i class="bi bi-<?= $s['icon'] ?>"></i></div>
                <div><div class="stat-num" id="stat-val-<?= $sKey ?>"><?= $s['val'] ?></div><div class="stat-lbl"><?= $s['label'] ?></div></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- User composition dashboard meters -->
    <div class="card perm-card mb-4 animate-fade-in-up delay-1">
        <h6 class="fw-700 mb-3" style="font-size:0.9rem"><i class="bi bi-pie-chart me-2" style="color:var(--primary)"></i>User Role Distribution</h6>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size: 0.8rem; font-weight:600;"><span class="role-badge badge-admin">Admin</span></span>
                    <span class="text-muted" style="font-size: 0.75rem;" id="dist-lbl-admins"><?= $stats['admins'] ?> of <?= $stats['users'] ?></span>
                </div>
                <div class="dashboard-meter">
                    <div class="dashboard-meter-fill bg-danger" id="dist-bar-admins" style="width: <?= $stats['users'] > 0 ? ($stats['admins']/$stats['users'])*100 : 0 ?>%"></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size: 0.8rem; font-weight:600;"><span class="role-badge badge-editor">Editor</span></span>
                    <span class="text-muted" style="font-size: 0.75rem;" id="dist-lbl-editors"><?= $stats['editors'] ?> of <?= $stats['users'] ?></span>
                </div>
                <div class="dashboard-meter">
                    <div class="dashboard-meter-fill bg-warning" id="dist-bar-editors" style="width: <?= $stats['users'] > 0 ? ($stats['editors']/$stats['users'])*100 : 0 ?>%"></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size: 0.8rem; font-weight:600;"><span class="role-badge badge-reader">Reader</span></span>
                    <span class="text-muted" style="font-size: 0.75rem;" id="dist-lbl-readers"><?= $stats['readers'] ?> of <?= $stats['users'] ?></span>
                </div>
                <div class="dashboard-meter">
                    <div class="dashboard-meter-fill bg-success" id="dist-bar-readers" style="width: <?= $stats['users'] > 0 ? ($stats['readers']/$stats['users'])*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission reference -->
    <div class="perm-card mb-4 animate-fade-in-up delay-2">
        <h5 class="fw-700 mb-3" style="font-size:0.95rem"><i class="bi bi-key me-2" style="color:var(--primary)"></i>Role Permissions</h5>
        <div class="row g-3">
            <?php
            $roles_perm = [
                ['role'=>'admin', 'badge'=>'badge-admin', 'label'=>'Admin', 'yes'=>['Read all posts','Create posts','Edit any post','Delete any post','Manage user roles','Delete users','Access admin panel'], 'no'=>[]],
                ['role'=>'editor','badge'=>'badge-editor','label'=>'Editor','yes'=>['Read all posts','Create posts','Edit own posts','Delete own posts'], 'no'=>['Edit others\' posts','Manage users','Admin panel']],
                ['role'=>'reader','badge'=>'badge-reader','label'=>'Reader','yes'=>['Read all posts'], 'no'=>['Create posts','Edit posts','Delete posts','Manage users','Admin panel']],
            ];
            foreach ($roles_perm as $rp): ?>
            <div class="col-12 col-md-4">
                <div class="perm-col">
                    <div class="mb-2"><span class="role-badge <?= $rp['badge'] ?>"><?= $rp['label'] ?></span></div>
                    <?php foreach ($rp['yes'] as $y): ?><div class="perm-item perm-yes"><i class="bi bi-check-circle-fill me-1"></i><?= $y ?></div><?php endforeach; ?>
                    <?php foreach ($rp['no']  as $n): ?><div class="perm-item perm-no"><i class="bi bi-x-circle me-1"></i><?= $n ?></div><?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Users table -->
    <div class="card border-0 shadow-sm animate-fade-in-up delay-3" style="border-radius:14px;overflow:hidden">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-people me-2" style="color:var(--primary)"></i>All Users</span>
            <span class="badge bg-light text-muted border"><?= $stats['users'] ?> users</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 users-table">
                <thead>
                    <tr>
                        <th>ID</th><th>User</th><th>Role</th><th>Posts</th><th>Joined</th><th>Change Role</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($u = $users->fetch_assoc()):
                    $isSelf = ($u['id'] == $_SESSION['user_id']); ?>
                <tr <?= $isSelf ? 'class="table-light"' : '' ?>>
                    <td class="text-muted">#<?= $u['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="table-avatar"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                            <div>
                                <strong style="font-size:.875rem"><?= e($u['username']) ?></strong>
                                <?php if ($isSelf): ?><span class="you-tag">You</span><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= roleBadge($u['role']) ?></td>
                    <td class="text-center fw-600"><?= $u['pc'] ?></td>
                    <td class="text-muted" style="font-size:.8rem;white-space:nowrap"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if (!$isSelf): ?>
                        <form method="POST" class="role-change-form d-flex align-items-center gap-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="new_role" class="role-select form-select form-select-sm" style="width:100px">
                                <option value="reader" <?= $u['role']==='reader'?'selected':'' ?>>Reader</option>
                                <option value="editor" <?= $u['role']==='editor'?'selected':'' ?>>Editor</option>
                                <option value="admin"  <?= $u['role']==='admin' ?'selected':'' ?>>Admin</option>
                            </select>
                            <button type="submit" name="change_role" class="btn btn-outline-primary btn-sm">Save</button>
                        </form>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$isSelf): ?>
                        <form method="POST" class="delete-user-form" data-confirm-msg="Delete user <?= e($u['username']) ?> and all their posts?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" name="delete_user" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="text-center"><p class="mb-0">© <?= date('Y') ?> <span>BlogApp</span> · Admin Panel</p></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Hide native fallback toast if showing
    const fallbackToast = document.querySelector('.toast');
    if (fallbackToast) {
        const toastInstance = new bootstrap.Toast(fallbackToast, {delay: 3500});
        toastInstance.show();
    }

    const updateDashboardStats = (stats) => {
        // Update stat counts
        const valUsers = document.getElementById('stat-val-users');
        const valPosts = document.getElementById('stat-val-posts');
        const valAdmins = document.getElementById('stat-val-admins');
        const valEditors = document.getElementById('stat-val-editors');
        const valReaders = document.getElementById('stat-val-readers');
        
        if (valUsers) valUsers.textContent = stats.users;
        if (valPosts) valPosts.textContent = stats.posts;
        if (valAdmins) valAdmins.textContent = stats.admins;
        if (valEditors) valEditors.textContent = stats.editors;
        if (valReaders) valReaders.textContent = stats.readers;

        // Update distribution labels
        const lblAdmins = document.getElementById('dist-lbl-admins');
        const lblEditors = document.getElementById('dist-lbl-editors');
        const lblReaders = document.getElementById('dist-lbl-readers');
        
        if (lblAdmins) lblAdmins.textContent = `${stats.admins} of ${stats.users}`;
        if (lblEditors) lblEditors.textContent = `${stats.editors} of ${stats.users}`;
        if (lblReaders) lblReaders.textContent = `${stats.readers} of ${stats.users}`;

        // Update distribution bars
        const barAdmins = document.getElementById('dist-bar-admins');
        const barEditors = document.getElementById('dist-bar-editors');
        const barReaders = document.getElementById('dist-bar-readers');
        
        const pctAdmins = stats.users > 0 ? (stats.admins / stats.users) * 100 : 0;
        const pctEditors = stats.users > 0 ? (stats.editors / stats.users) * 100 : 0;
        const pctReaders = stats.users > 0 ? (stats.readers / stats.users) * 100 : 0;
        
        if (barAdmins) barAdmins.style.width = pctAdmins + '%';
        if (barEditors) barEditors.style.width = pctEditors + '%';
        if (barReaders) barReaders.style.width = pctReaders + '%';
        
        // Update badge count in header
        const headerBadge = document.querySelector('.card-header .badge');
        if (headerBadge) headerBadge.textContent = `${stats.users} users`;
    };

    // AJAX user role management
    const changeRoleForms = document.querySelectorAll('form.role-change-form');
    changeRoleForms.forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const initialText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '...';
            
            const formData = new FormData(form);
            formData.append('ajax', '1');
            formData.append('change_role', '1');
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = initialText;
                
                if (data.success) {
                    const tr = form.closest('tr');
                    const badgeCell = tr.querySelector('td:nth-child(3)');
                    if (badgeCell) badgeCell.innerHTML = data.badge;
                    
                    updateDashboardStats(data.stats);
                    
                    if (window.showToast) {
                        window.showToast('Role Updated', data.message, 'success');
                    }
                } else {
                    if (window.showToast) {
                        window.showToast('Failed to Update', data.message, 'danger');
                    }
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.textContent = initialText;
                console.error(err);
                if (window.showToast) {
                    window.showToast('Error', 'An error occurred during submission.', 'danger');
                }
            });
        });
    });

    // AJAX user deletion
    const deleteUserForms = document.querySelectorAll('form.delete-user-form');
    deleteUserForms.forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault();
            
            const confirmMsg = form.dataset.confirmMsg || 'Delete this user?';
            if (!confirm(confirmMsg)) return;
            
            const tr = form.closest('tr');
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            const formData = new FormData(form);
            formData.append('ajax', '1');
            formData.append('delete_user', '1');
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    tr.classList.add('row-delete-anim');
                    tr.addEventListener('animationend', () => tr.remove());
                    
                    updateDashboardStats(data.stats);
                    
                    if (window.showToast) {
                        window.showToast('User Deleted', data.message, 'success');
                    }
                } else {
                    submitBtn.disabled = false;
                    if (window.showToast) {
                        window.showToast('Deletion Failed', data.message, 'danger');
                    }
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                console.error(err);
                if (window.showToast) {
                    window.showToast('Error', 'An error occurred deleting this user.', 'danger');
                }
            });
        });
    });
});
</script>
</body>
</html>
<?php $db->close(); ?>
