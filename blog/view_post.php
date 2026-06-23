<?php
session_start();
require_once 'db.php';
require_once 'head.php';
requireLogin();

$db=$db=getDB(); $id=intval($_GET['id']??0);
$stmt=$db->prepare("SELECT p.*,u.username,u.role as author_role FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=?");
$stmt->bind_param("i",$id); $stmt->execute();
$post=$stmt->get_result()->fetch_assoc();
if(!$post){$db->close();header("Location:posts.php");exit;}
$canEdit=canEditPost($post['user_id']); $canDelete=canDeletePost($post['user_id']);
$db->close();
renderHead(e($post['title']).' – BlogApp', e(substr($post['content'],0,150)));
?>
<?php include 'navbar.php'; ?>
<div class="container animate-fade-in-up" style="max-width:820px;padding-top:32px;padding-bottom:32px">
    <a href="posts.php" class="btn btn-light btn-sm mb-4 d-inline-flex align-items-center gap-1 border">
        <i class="bi bi-arrow-left"></i> All Posts
    </a>
    <div class="post-view glass-card shadow-sm">
        <!-- Meta -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="table-avatar"><?= strtoupper(substr($post['username'],0,1)) ?></div>
                <div>
                    <strong style="font-size:.9rem;color:#1e1b4b"><?= e($post['username']) ?></strong>
                    <?= roleBadge($post['author_role']) ?>
                </div>
            </div>
            <span class="text-muted" style="font-size:.82rem">
                <i class="bi bi-calendar3 me-1"></i><?= date('F d, Y', strtotime($post['created_at'])) ?>
            </span>
        </div>
        <h1 class="post-view-title"><?= e($post['title']) ?></h1>
        <div class="post-view-content" style="font-size:1.05rem; letter-spacing:0.01em;"><?= nl2br(e($post['content'])) ?></div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="posts.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
            <?php if($canEdit): ?>
            <a href="post_form.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
            <?php endif; ?>
            <?php if($canDelete): ?>
            <a href="delete_post.php?id=<?= $post['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this post?')"><i class="bi bi-trash me-1"></i>Delete</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<footer class="text-center"><p class="mb-0">© <?= date('Y') ?> <span>BlogApp</span></p></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bar = document.getElementById('scroll-progress');
    if (bar) {
        const updateProgress = () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
            bar.style.width = scrolled + '%';
        };
        window.addEventListener('scroll', updateProgress);
        updateProgress(); // Initial check
    }
});
</script>
</body></html>
