<?php
session_start();
require('dbconnect.php');

//retweetする
if (isset($_SESSION['id']) && $_REQUEST['retweet'] == 0) {
    $member_id = $_SESSION['id'];
    $uniqueness_id = $_REQUEST['uniqueness_id'];
    $messages =  $_REQUEST['message'];
    if(mb_substr($messages,0,2) == 'RT')
    {
        $message = $messages;
    }
    else
    {
        $message = 'RT'.$messages;
    }

    // 新規メッセージとして追加
    $retweet = $db->prepare('INSERT INTO posts SET member_id=?,uniqueness_id=?, message=?,created=NOW()');
		$retweet->execute(array(
			$member_id,
            $uniqueness_id,
            $message
        ));

    // retweet数１に変更
    $retweet_add = $db->prepare('UPDATE posts_option SET retweet=1 WHERE member_id=? AND uniqueness_id=?');
    $retweet_add->execute(array(
        $member_id,
        $uniqueness_id
    ));
}

//retweet取り消し
if (isset($_SESSION['id']) && $_REQUEST['retweet'] == 1) {
    $member_id = $_SESSION['id'];
    $post_id = $_REQUEST['id'];
    $uniqueness_id = $_REQUEST['uniqueness_id'];

    // メッセージ削除
    $retweet_del = $db->prepare('DELETE FROM posts WHERE id=?');
    $retweet_del->execute(array($post_id));

    // retweet数を０に変更
	$message = $db->prepare('UPDATE posts_option SET retweet=0 WHERE member_id=? AND uniqueness_id=?');
		$message->execute(array(
			$member_id,
            $uniqueness_id
        ));
}

header("Location: index.php?page=$_REQUEST[page]");
exit();
?>
