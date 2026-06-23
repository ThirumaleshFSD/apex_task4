<?php
session_start();
require_once 'db.php';
require_once 'head.php';
requireLogin();

$db      = getDB();
$success = $_GET['msg'] ?? '';
$err_msg = $_GET['err'] ?? '';
$per_page   = 6;
$page       = max(1, intval($_GET['page'] ?? 1));
$search     = trim($_GET['q'] ?? '');
$offset     = ($page - 1) * $per_page;

if ($search !== '') {
    $like = '%' . $search . '%';
    $cs = $db->prepare("SELECT COUNT(*) t FROM posts p JOIN users u ON p.user_id=u.id WHERE p.title LIKE ? OR p.content LIKE ?");
    $cs->bind_param("ss",$like,$like); $cs->execute();
    $total_rows = $cs->get_result()->fetch_assoc()['t'];
    $stmt = $db->prepare("SELECT p.id,p.title,p.content,p.created_at,p.user_id,u.username FROM posts p JOIN users u ON p.user_id=u.id WHERE p.title LIKE ? OR p.content LIKE ? ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii",$like,$like,$per_page,$offset);
} else {
    $cr = $db->query("SELECT COUNT(*) t FROM posts");
    $total_rows = $cr->fetch_assoc()['t'];
    $stmt = $db->prepare("SELECT p.id,p.title,p.content,p.created_at,p.user_id,u.username FROM posts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii",$per_page,$offset);
}
$stmt->execute();
$posts = $stmt->get_result();
$total_pages = (int)ceil($total_rows / $per_page);

function page_url($p,$q){ $a=['page'=>$p]; if($q!=='') $a['q']=$q; return 'posts.php?'.http_build_query($a); }

// Render dynamic inner content helper
function renderInner($posts, $total_rows, $total_pages, $page, $search) {
    ?>
    <?php if ($search !== ''): ?>
    <p class="text-muted mb-3 animate-fade-in-up"><strong><?= $total_rows ?></strong> result<?= $total_rows!==1?'s':'' ?> for "<em><?= e($search) ?></em>"</p>
    <?php endif; ?>

    <!-- Posts grid -->
    <?php if ($posts->num_rows === 0): ?>
    <div class="empty-state animate-fade-in-up">
        <span class="empty-icon"><?= $search !== ''?'🔎':'📝' ?></span>
        <h4 class="fw-700"><?= $search !== ''?'No posts found':'No posts yet' ?></h4>
        <p class="text-muted"><?= $search !== ''?'Try different keywords.':'Be the first to write something!' ?></p>
        <?php if ($search === '' && canWrite()): ?>
        <a href="post_form.php" class="btn btn-primary">Write First Post</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-5">
        <?php 
        $delayIndex = 1;
        while ($post = $posts->fetch_assoc()): ?>
        <div class="col animate-fade-in-up delay-<?= min($delayIndex++, 5) ?>">
            <div class="post-card">
                <div class="post-meta">
                    <span class="post-author"><i class="bi bi-person-fill me-1"></i><?= e($post['username']) ?></span>
                    <span><i class="bi bi-calendar3 me-1"></i><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                </div>
                <div class="post-title">
                    <?php 
                    $t = e($post['title']); 
                    if($search !== '') {
                        $t = preg_replace('/('.preg_quote(e($search),'/').')/i','<mark>$1</mark>',$t); 
                    }
                    echo $t; 
                    ?>
                </div>
                <div class="post-excerpt">
                    <?php 
                    $ex = e(substr($post['content'],0,155)); 
                    if($search !== '') {
                        $ex = preg_replace('/('.preg_quote(e($search),'/').')/i','<mark>$1</mark>',$ex); 
                    }
                    echo $ex; 
                    ?>…
                </div>
                <div class="post-actions">
                    <a href="view_post.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>Read</a>
                    <?php if (canEditPost($post['user_id'])): ?>
                    <a href="post_form.php?id=<?= $post['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <?php endif; ?>
                    <?php if (canDeletePost($post['user_id'])): ?>
                    <a href="delete_post.php?id=<?= $post['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this post?')"><i class="bi bi-trash me-1"></i>Delete</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Post pages" class="d-flex justify-content-center mb-2 animate-fade-in-up">
        <ul class="pagination">
            <li class="page-item <?= $page<=1?'disabled':'' ?>">
                <a class="page-link" href="<?= page_url($page-1,$search) ?>" data-page="<?= $page-1 ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php
            $s = max(1,$page-3); $en = min($total_pages,$page+3);
            if($s > 1) { 
                echo '<li class="page-item"><a class="page-link" href="'.page_url(1,$search).'" data-page="1">1</a></li>'; 
                if($s > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; 
            }
            for($i = $s; $i <= $en; $i++) {
                echo '<li class="page-item '.($i===$page?'active':'').'"><a class="page-link" href="'.page_url($i,$search).'" data-page="'.$i.'">'.$i.'</a></li>';
            }
            if($en < $total_pages) { 
                if($en < $total_pages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; 
                echo '<li class="page-item"><a class="page-link" href="'.page_url($total_pages,$search).'" data-page="'.$total_pages.'">'.$total_pages.'</a></li>'; 
            }
            ?>
            <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                <a class="page-link" href="<?= page_url($page+1,$search) ?>" data-page="<?= $page+1 ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <p class="text-center text-muted animate-fade-in-up" style="font-size:.82rem">Page <?= $page ?> of <?= $total_pages ?> · <?= $total_rows ?> posts</p>
    <?php endif; ?>
    <?php endif;
}

// Handle AJAX Request
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    renderInner($posts, $total_rows, $total_pages, $page, $search);
    $html = ob_get_clean();
    echo json_encode([
        'html' => $html,
        'total_rows' => $total_rows,
        'count_text' => 'Browsing ' . $total_rows . ' published ' . ($total_rows===1?'post':'posts')
    ]);
    $db->close();
    exit;
}

renderHead('All Posts – BlogApp', 'Browse all blog posts.');
include 'navbar.php';
?>

<div class="container" style="max-width:1100px">
    <!-- Page Header -->
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="mb-1"><i class="bi bi-grid me-2" style="color:var(--primary)"></i>All Posts</h1>
            <p class="mb-0">Browsing <?= $total_rows ?> published <?= $total_rows===1?'post':'posts' ?></p>
        </div>
        <?php if (canWrite()): ?>
        <a href="post_form.php" class="btn btn-primary align-self-start">
            <i class="bi bi-plus-lg me-1"></i>New Post
        </a>
        <?php endif; ?>
    </div>

    <!-- Alerts -->
    <?php if ($success === 'created'): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Post created successfully!</div>
    <?php elseif ($success === 'updated'): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Post updated!</div>
    <?php elseif ($success === 'deleted'): ?><div class="alert alert-success"><i class="bi bi-trash me-2"></i>Post deleted.</div>
    <?php elseif ($err_msg === 'forbidden'): ?><div class="alert alert-danger"><i class="bi bi-exclamation-octagon me-2"></i>You don't have permission to do that.</div>
    <?php elseif ($err_msg === 'readonly'): ?><div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Readers cannot create posts. Ask an Admin for Editor access.</div>
    <?php endif; ?>

    <!-- Reader notice -->
    <?php if (isReader()): ?>
    <div class="reader-notice mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-info-circle flex-shrink-0"></i>
        <span>You have <strong>Reader</strong> access — read-only. Contact an Admin to get Editor or Admin privileges.</span>
    </div>
    <?php endif; ?>

    <!-- Search bar -->
    <form class="d-flex gap-2 mb-4" method="GET" action="posts.php" id="search-form" role="search">
        <div class="search-wrap flex-grow-1">
            <i class="bi bi-search search-icon-abs"></i>
            <input type="text" id="search-input" name="q" class="form-control"
                   placeholder="Search posts by title or content…"
                   value="<?= e($search) ?>" autocomplete="off">
        </div>
        <?php if ($search !== ''): ?>
        <a href="posts.php" class="btn btn-outline-secondary" id="clear-search"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
    </form>

    <!-- Dynamic Container for AJAX loading -->
    <div id="dynamic-posts-area">
        <?php renderInner($posts, $total_rows, $total_pages, $page, $search); ?>
    </div>
</div>

<footer class="text-center">
    <p class="mb-0">© <?= date('Y') ?> <span>BlogApp</span> · Apex Planet Project</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const si = document.getElementById('search-input');
    const sf = document.getElementById('search-form');
    const dynamicArea = document.getElementById('dynamic-posts-area');
    const clearBtn = document.getElementById('clear-search');
    const countLabel = document.querySelector('.page-header p');
    let debounceTimer;

    const fetchPosts = (query, page) => {
        if (!dynamicArea) return;
        dynamicArea.classList.add('posts-loading');

        const url = new URL(window.location.href);
        url.searchParams.set('q', query);
        url.searchParams.set('page', page);
        url.searchParams.set('ajax', '1');

        fetch(url)
            .then(res => res.json())
            .then(data => {
                dynamicArea.innerHTML = data.html;
                dynamicArea.classList.remove('posts-loading');
                if (countLabel) countLabel.textContent = data.count_text;

                // Adjust clean button visibility
                if (query.trim()) {
                    if (!document.getElementById('clear-search')) {
                        const clearLink = document.createElement('a');
                        clearLink.href = 'posts.php';
                        clearLink.id = 'clear-search';
                        clearLink.className = 'btn btn-outline-secondary';
                        clearLink.innerHTML = '<i class="bi bi-x-lg"></i>';
                        sf.insertBefore(clearLink, sf.lastElementChild);
                        clearLink.addEventListener('click', (e) => {
                            e.preventDefault();
                            si.value = '';
                            clearLink.remove();
                            fetchPosts('', 1);
                        });
                    }
                } else {
                    const existingClear = document.getElementById('clear-search');
                    if (existingClear) existingClear.remove();
                }

                // Update URL history state
                const displayUrl = new URL(window.location.href);
                if (query) displayUrl.searchParams.set('q', query);
                else displayUrl.searchParams.delete('q');
                displayUrl.searchParams.set('page', page);
                window.history.pushState({ path: displayUrl.href }, '', displayUrl.href);
            })
            .catch(err => {
                console.error('Failed to load posts:', err);
                dynamicArea.classList.remove('posts-loading');
            });
    };

    if (si) {
        si.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchPosts(si.value, 1);
            }, 300);
        });

        si.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                si.value = '';
                fetchPosts('', 1);
            }
        });
    }

    if (sf) {
        sf.addEventListener('submit', e => {
            e.preventDefault();
            fetchPosts(si ? si.value : '', 1);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', e => {
            e.preventDefault();
            si.value = '';
            clearBtn.remove();
            fetchPosts('', 1);
        });
    }

    if (dynamicArea) {
        dynamicArea.addEventListener('click', e => {
            const link = e.target.closest('.page-link');
            if (link && link.dataset.page) {
                e.preventDefault();
                fetchPosts(si ? si.value : '', link.dataset.page);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
});
</script>
</body>
</html>
<?php $db->close(); ?>
