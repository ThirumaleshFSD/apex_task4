<?php
session_start();
require_once 'db.php';
requireLogin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $chk = $db->prepare("SELECT user_id FROM posts WHERE id=?");
    $chk->bind_param("i",$id); $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if ($row && canDeletePost($row['user_id'])) {
        $del = $db->prepare("DELETE FROM posts WHERE id=?");
        $del->bind_param("i",$id); $del->execute();
        $db->close(); header("Location: posts.php?msg=deleted"); exit;
    } else {
        $db->close(); header("Location: posts.php?err=forbidden"); exit;
    }
}
$db->close(); header("Location: posts.php"); exit;
