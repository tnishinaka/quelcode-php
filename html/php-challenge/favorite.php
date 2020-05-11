<?php
session_start();
require('dbconnect.php');

//fav追加
if (isset($_SESSION['id']) && intval($_REQUEST['favorite']) === 0) {
    $member_id = $_SESSION['id'];
    $post_id = $_REQUEST['id'];
    $uniqueness_id = $_REQUEST['uniqueness_id'];

	$favorite_add = $db->prepare('UPDATE posts_option SET favorite=1 WHERE member_id=? AND uniqueness_id=?');
		$favorite_add->execute(array(
			$member_id,
            $uniqueness_id
		));
}

//fav取り消し
if (isset($_SESSION['id']) && intval($_REQUEST['favorite']) === 1) {
    $member_id = $_SESSION['id'];
    $post_id = $_REQUEST['id'];
    $uniqueness_id = $_REQUEST['uniqueness_id'];

	$favorite_delete = $db->prepare('UPDATE posts_option SET favorite=0 WHERE member_id=? AND uniqueness_id=?');
		$favorite_delete->execute(array(
			$member_id,
            $uniqueness_id
		));
}

header("Location: index.php?page=$_REQUEST[page]");
exit();
?>
