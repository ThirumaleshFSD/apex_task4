<?php
session_start();
require_once 'db.php';
require_once 'head.php';
requireLogin();
requireWriter();

$db=$getDB=getDB(); $id=intval($_GET['id']??0); $post=null; $error=''; $isEdit=false;
if($id>0){
    $s=$db->prepare("SELECT * FROM posts WHERE id=?"); $s->bind_param("i",$id); $s->execute();
    $post=$s->get_result()->fetch_assoc();
    if($post){ if(!canEditPost($post['user_id'])){$db->close();header("Location:posts.php?err=forbidden");exit;} $isEdit=true; }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $title=trim($_POST['title']??''); $content=trim($_POST['content']??''); $postId=intval($_POST['post_id']??0);
    $errors=[];
    if(!$title) $errors[]="Title is required."; elseif(strlen($title)<3) $errors[]="Min 3 characters."; elseif(strlen($title)>255) $errors[]="Max 255 characters.";
    if(!$content) $errors[]="Content is required."; elseif(strlen($content)<10) $errors[]="Min 10 characters.";
    if(empty($errors)){
        if($postId>0){
            $chk=$db->prepare("SELECT user_id FROM posts WHERE id=?"); $chk->bind_param("i",$postId); $chk->execute();
            $row=$chk->get_result()->fetch_assoc();
            if($row&&canEditPost($row['user_id'])){ $s=$db->prepare("UPDATE posts SET title=?,content=? WHERE id=?"); $s->bind_param("ssi",$title,$content,$postId); $s->execute(); $db->close(); header("Location:posts.php?msg=updated"); exit; }
            else { $db->close(); header("Location:posts.php?err=forbidden"); exit; }
        } else {
            $uid=$_SESSION['user_id']; $s=$db->prepare("INSERT INTO posts(title,content,user_id)VALUES(?,?,?)"); $s->bind_param("ssi",$title,$content,$uid); $s->execute(); $db->close(); header("Location:posts.php?msg=created"); exit;
        }
    } else { $error=implode('<br>',$errors); }
}
$db->close();
renderHead(($isEdit?'Edit Post':'New Post').' – BlogApp');
?>
<?php include 'navbar.php'; ?>
<div class="container" style="max-width:780px; padding-top:32px; padding-bottom:32px;">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <h1><?= $isEdit?'<i class="bi bi-pencil me-2" style="color:var(--primary)"></i>Edit Post':'<i class="bi bi-plus-square me-2" style="color:var(--primary)"></i>New Post' ?></h1>
            <p><?= $isEdit?'Update your blog post below':'Share something with the world' ?></p>
        </div>
        <a href="posts.php" class="btn btn-outline-secondary btn-sm align-self-start"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <!-- Draft Restore Banner -->
    <div class="composer-draft-banner d-none" id="draft-restore-banner">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-4">💾</span>
            <div>
                <strong class="d-block" style="font-size: .875rem; color: #166534">Unsaved Draft Found</strong>
                <span class="text-muted" style="font-size: .8rem">We recovered an unsaved draft from your last typing session.</span>
            </div>
        </div>
        <div>
            <button type="button" id="btn-restore-draft" class="btn btn-success btn-sm me-2"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
            <button type="button" id="btn-discard-draft" class="btn btn-outline-secondary btn-sm">Discard</button>
        </div>
    </div>

    <?php if($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= $error ?></div><?php endif; ?>

    <div class="form-card animate-fade-in-up">
        <form method="POST" id="post-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="post_id" value="<?= $isEdit?$post['id']:0 ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Post Title</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-type-h1"></i></span>
                    <input type="text" id="title" name="title" class="form-control"
                           placeholder="Enter an interesting title…"
                           value="<?= $isEdit?e($post['title']):e($_POST['title']??'') ?>" required maxlength="255">
                </div>
                <div class="invalid-feedback d-block" id="err-title"></div>
            </div>
            <div class="mb-4">
                <label for="content" class="form-label">Content</label>
                <textarea id="content" name="content" class="form-control" rows="14"
                          placeholder="Write your post content here…" required><?= $isEdit?e($post['content']):e($_POST['content']??'') ?></textarea>
                
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div>
                        <small class="text-muted" id="char-info">0 characters</small>
                        <span class="composer-draft-badge pulsing ms-2 d-none" id="draft-status-badge">
                            <i class="bi bi-cloud-arrow-up-fill"></i> Draft saved
                        </span>
                    </div>
                    <div class="invalid-feedback d-block" id="err-content" style="text-align:right; width: auto;"></div>
                </div>
                
                <!-- Content progress bar -->
                <div class="progress mt-2" style="height: 3px;">
                    <div class="progress-bar bg-danger" id="content-progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="posts.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-floppy me-1"></i><?= $isEdit?'Update Post':'Publish Post' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<footer class="text-center"><p class="mb-0">© <?= date('Y') ?> <span>BlogApp</span></p></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    const form = document.getElementById('post-form');
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    const postId = <?= $isEdit ? $post['id'] : '0' ?>;

    const errTitle = document.getElementById('err-title');
    const errContent = document.getElementById('err-content');
    const charInfo = document.getElementById('char-info');
    const contentProgress = document.getElementById('content-progress-bar');
    const draftBanner = document.getElementById('draft-restore-banner');
    const btnRestore = document.getElementById('btn-restore-draft');
    const btnDiscard = document.getElementById('btn-discard-draft');
    const draftBadge = document.getElementById('draft-status-badge');
    let autosaveTimer;

    // 1. Validation logic
    const validateTitle = () => {
        const val = titleInput.value.trim();
        titleInput.classList.remove('is-invalid', 'is-valid');
        errTitle.textContent = '';
        
        if (!val) {
            titleInput.classList.add('is-invalid');
            errTitle.textContent = 'Title is required.';
            return false;
        }
        if (val.length < 3) {
            titleInput.classList.add('is-invalid');
            errTitle.textContent = 'Title must be at least 3 characters.';
            return false;
        }
        if (val.length > 255) {
            titleInput.classList.add('is-invalid');
            errTitle.textContent = 'Title cannot exceed 255 characters.';
            return false;
        }
        titleInput.classList.add('is-valid');
        return true;
    };

    const validateContent = () => {
        const val = contentInput.value.trim();
        contentInput.classList.remove('is-invalid', 'is-valid');
        errContent.textContent = '';
        
        charInfo.textContent = contentInput.value.length + ' characters';
        
        const minLen = 10;
        const progressPercent = Math.min((contentInput.value.length / minLen) * 100, 100);
        contentProgress.style.width = progressPercent + '%';
        
        if (contentInput.value.length < minLen) {
            contentProgress.className = 'progress-bar bg-danger';
        } else {
            contentProgress.className = 'progress-bar bg-success';
        }

        if (!val) {
            contentInput.classList.add('is-invalid');
            errContent.textContent = 'Content is required.';
            return false;
        }
        if (val.length < minLen) {
            contentInput.classList.add('is-invalid');
            errContent.textContent = 'Content must be at least 10 characters.';
            return false;
        }
        contentInput.classList.add('is-valid');
        return true;
    };

    // Real-time validation and autosave trigger
    titleInput.addEventListener('input', () => {
        validateTitle();
        debounceAutosave();
    });
    contentInput.addEventListener('input', () => {
        validateContent();
        debounceAutosave();
    });

    // Form submit validation
    form.addEventListener('submit', (e) => {
        const titleOk = validateTitle();
        const contentOk = validateContent();
        if (!titleOk || !contentOk) {
            e.preventDefault();
            if (window.showToast) {
                window.showToast('Validation Failed', 'Please verify your post details and try again.', 'danger');
            }
        } else {
            // Successful submit - clear local draft
            localStorage.removeItem(`blogapp_draft_${postId}`);
        }
    });

    // 2. Local storage draft autosave
    const draftKey = `blogapp_draft_${postId}`;
    
    const debounceAutosave = () => {
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(performAutosave, 800);
    };

    const performAutosave = () => {
        const titleVal = titleInput.value;
        const contentVal = contentInput.value;
        
        if (!titleVal.trim() && !contentVal.trim()) {
            localStorage.removeItem(draftKey);
            draftBadge.classList.add('d-none');
            return;
        }
        
        const draft = {
            title: titleVal,
            content: contentVal,
            timestamp: Date.now()
        };
        
        localStorage.setItem(draftKey, JSON.stringify(draft));
        
        if (draftBadge) {
            draftBadge.classList.remove('d-none');
            draftBadge.innerHTML = '<i class="bi bi-cloud-arrow-up-fill text-primary"></i> Saving draft...';
            setTimeout(() => {
                draftBadge.innerHTML = '<i class="bi bi-cloud-check-fill text-success"></i> Draft saved';
            }, 600);
        }
    };

    // Check for draft recovery
    const savedDraftStr = localStorage.getItem(draftKey);
    if (savedDraftStr) {
        try {
            const savedDraft = JSON.parse(savedDraftStr);
            const currentTitle = titleInput.value;
            const currentContent = contentInput.value;
            
            if (savedDraft.title !== currentTitle || savedDraft.content !== currentContent) {
                if (draftBanner) {
                    draftBanner.classList.remove('d-none');
                }
            }
            
            if (btnRestore) {
                btnRestore.addEventListener('click', () => {
                    titleInput.value = savedDraft.title;
                    contentInput.value = savedDraft.content;
                    validateTitle();
                    validateContent();
                    if (draftBanner) draftBanner.classList.add('d-none');
                    if (window.showToast) {
                        window.showToast('Draft Restored', 'Loaded your unsubmitted workspace draft successfully.', 'success');
                    }
                });
            }
            
            if (btnDiscard) {
                btnDiscard.addEventListener('click', () => {
                    localStorage.removeItem(draftKey);
                    if (draftBanner) draftBanner.classList.add('d-none');
                    if (window.showToast) {
                        window.showToast('Draft Discarded', 'Unsaved backup draft was removed.', 'info');
                    }
                });
            }
        } catch (e) {
            console.error('Failed to restore draft:', e);
        }
    }

    // Run baseline check
    validateContent();
});
</script>
</body>
</html>
