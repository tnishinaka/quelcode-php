<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();

	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();
} else {
	// ログインしていない
	header('Location: login.php'); exit();
}

// もし新規登録されたユーザーがきた場合はposts_optionテーブルに必要なデータを挿入する
$investigation = $db->prepare('SELECT * FROM posts_option WHERE member_id=?');
$investigation->bindParam(1,$_SESSION['id'] , PDO::PARAM_INT);
$investigation->execute();
foreach ($investigation as $row) {
	$exist[] = $row;
}

//新規登録と判断しposts_optionテーブルに必要データを挿入
if(is_null($exist)) {
$getId = $db->prepare('SELECT DISTINCT post_id, uniqueness_id FROM posts_option');
$getId->execute();
	foreach ($getId as $row) {
		$toOption = $db->prepare('INSERT INTO posts_option SET member_id=?, post_id=?, uniqueness_id=?, created=NOW()');
		$toOption->execute(array(
			$_SESSION['id'],
			$row['post_id'],
			$row['uniqueness_id']
		));
	}
}

// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

$posts = $db->prepare('SELECT m.name, m.picture, p.* , po.post_id,po.uniqueness_id,po.favorite,po.retweet  ,poc.fc,poc.rt FROM members m INNER JOIN posts p on m.id=p.member_id LEFT JOIN (SELECT * FROM posts_option WHERE member_id=?) po ON p.uniqueness_id = po.uniqueness_id 
LEFT JOIN (SELECT SUM(favorite) fc , SUM(retweet) rt,uniqueness_id FROM posts_option GROUP by uniqueness_id) poc ON po.uniqueness_id = poc.uniqueness_id   ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1,$_SESSION['id'] , PDO::PARAM_INT);
$posts->bindParam(2, $start, PDO::PARAM_INT);
$posts->execute();

// 返信の場合
if (isset($_REQUEST['res'])) {
	$response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
	$response->execute(array($_REQUEST['res']));

	$table = $response->fetch();
	$message = '@' . $table['name'] . ' ' . $table['message'];
}

// 新規投稿
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, created=NOW()');
		$message->execute(array(
			$member['id'],
			$_POST['message'],
			$_POST['reply_post_id']
		));

		//最新のposts_id取得
		$sql='SELECT LAST_INSERT_ID()';
		$stmt=$db->prepare($sql);
		$stmt->execute();
		$rec=$stmt->fetch(PDO::FETCH_ASSOC);
		$max=intval($rec['LAST_INSERT_ID()']);

		// 最新の投稿に一意性のあるidを振り分け
		$uniq = $db->prepare('UPDATE posts SET uniqueness_id=? where id=?');
		$uniq->bindParam(1,$max,PDO::PARAM_INT);
		$uniq->bindParam(2,$max,PDO::PARAM_INT);
		$uniq->execute();

		//fav retweet 管理専用のテーブルに会員idごとに格納
		$option = $db->query('SELECT id FROM members');
		while($row = $option->fetch()) {
		$nums[] = intval($row['id']);
		}

		for ($i=0; $i < count($nums); $i++) { 
		$postOption = $db->prepare('INSERT INTO posts_option SET member_id=?, post_id=?, uniqueness_id=?, created=NOW()');
			$postOption->execute(array(
				$nums[$i]
				,$max
				,$max
			));
		}
		header('Location: index.php'); exit();
	}
}

// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value) {
	return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>' , $value);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css">
	<link rel="stylesheet" href="style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
  	<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
    <form action="" method="post">
      <dl>
        <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
        <dd>
          <textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
          <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
        </dd>
      </dl>
      <div>
        <p>
          <input type="submit" value="投稿する" />
        </p>
      </div>
    </form>

<?php
foreach ($posts as $post):
?>
    <div class="msg">
    <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
    <p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]</p>
	<p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>

	<!-- お気に入り機能 -->
	<!-- お気に入り済み -->
	<?php if(intval($post['favorite']) === 1) :?>
	<a href="favorite.php?id=<?php echo h($post['id']); ?>
	<?php echo '&uniqueness_id=' ?>
	<?php echo h($post['uniqueness_id']); ?>
	<?php echo '&page=' ?>
	<?php echo $page; ?>
	<?php echo '&favorite=' ?>
	<?php echo h($post['favorite']); ?>
	"><i class="fas fa-heart" style="color:red;"></i></a>
	<?php else: ?> 
	<!-- お気に入りされていない場合 -->
	<a href="favorite.php?id=<?php echo h($post['id']); ?>
	<?php echo '&uniqueness_id=' ?>
	<?php echo h($post['uniqueness_id']); ?>
	<?php echo '&favorite=' ?>
	<?php echo h($post['favorite']); ?>
	<?php echo '&page=' ?>
	<?php echo $page; ?>
	"><i class="fas fa-heart"></i></a>
	<?php endif ?>
	<!-- お気に入り回数 -->
	<?php echo h($post['fc']); ?>

	<!-- リツイート機能 -->
	<!-- リツイート済み -->
	<?php if(intval($post['retweet']) === 1) :?>
	<a href="retweet.php?id=<?php echo h($post['id']); ?>
	<?php echo '&uniqueness_id=' ?>
	<?php echo h($post['uniqueness_id']); ?>
	<?php echo '&retweet=' ?>
	<?php echo h($post['retweet']); ?>
	<?php echo '&page=' ?>
	<?php echo $page; ?>
	"><i class="fas fa-retweet" style="color:blue;"></i></a>
	<?php else: ?>
	<!-- 初リツイート -->
	<a href="retweet.php?id=<?php echo h($post['id']); ?>
	<?php echo '&uniqueness_id=' ?>
	<?php echo h($post['uniqueness_id']); ?>
	<?php echo '&retweet=' ?>
	<?php echo h($post['retweet']); ?>
	<?php echo '&message=' ?>
	<?php echo h($post['message']); ?>
	<?php echo '&page=' ?>
	<?php echo $page; ?>
	"><i class="fas fa-retweet"></i></a>
	<?php endif ?>
	<!-- リツイート回数 -->
	<?php echo h($post['rt']); ?>

<?php
if ($post['reply_post_id'] > 0):
?>
<a href="view.php?id=<?php echo
h($post['reply_post_id']); ?>">
返信元のメッセージ</a>
<?php
endif;
?>
<?php
if ($_SESSION['id'] == $post['member_id']):
?>
[<a href="delete.php?id=<?php echo h($post['id']); ?>
<?php echo '&uniqueness_id=' ?>
<?php echo h($post['uniqueness_id']); ?>
<?php echo '&retweet=' ?>
<?php echo h($post['retweet']); ?>
<?php echo '&favorite=' ?>
<?php echo h($post['favorite']); ?>"
style="color: #F33;">削除</a>]
<?php
endif;
?>
    </p>
    </div>
<?php
endforeach;
?>

<ul class="paging">
<?php
if ($page > 1) {
?>
<li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
<?php
} else {
?>
<li>前のページへ</li>
<?php
}
?>
<?php
if ($page < $maxPage) {
?>
<li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
<?php
} else {
?>
<li>次のページへ</li>
<?php
}
?>
</ul>
  </div>
</div>
</body>
</html>
