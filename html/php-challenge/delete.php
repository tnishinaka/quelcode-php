<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {
	$id = $_REQUEST['id'];
	
	// 投稿を検査する
	$messages = $db->prepare('SELECT * FROM posts WHERE id=?');
	$messages->execute(array($id));
	$message = $messages->fetch();

	if ($message['member_id'] == $_SESSION['id']) {
		// 削除する
		$del = $db->prepare('DELETE FROM posts WHERE id=?');
		$del->execute(array($id));

		$del = $db->prepare('DELETE FROM posts_option WHERE post_id=?');
		$del->execute(array($id));

		// お気に入りリツイートがあった場合は論理削除
		if($_REQUEST['favorite'] == 1 )
		{
			$member_id = $_SESSION['id'];
			$uniqueness_id = $_REQUEST['uniqueness_id'];
		
			$favorite_delete = $db->prepare('UPDATE posts_option SET favorite=0 WHERE member_id=? AND uniqueness_id=?');
				$favorite_delete->execute(array(
					$member_id,
					$uniqueness_id
				));	
		}
		if($_REQUEST['retweet'] == 1)
		{
			$member_id = $_SESSION['id'];
			$uniqueness_id = $_REQUEST['uniqueness_id'];
			
			$message = $db->prepare('UPDATE posts_option SET retweet=0 WHERE member_id=? AND uniqueness_id=?');
				$message->execute(array(
					$member_id,
					$uniqueness_id
				));
		}
	}
}

header('Location: index.php'); exit();
?>
