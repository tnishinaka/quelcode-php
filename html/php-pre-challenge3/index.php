<?php
$limit = $_GET['target'];
$limit = mb_convert_kana($limit, 'a' , 'UTF-8');
if(!is_numeric($limit) || $limit <= 0 || preg_match('/^([1-9]\d*|0)\.(\d+)?$/', $limit)) {
    http_response_code(400);
    echo json_encode('invalid limit :' . $limit);
    exit();
}
$limit = intval($limit);

$dsn = 'mysql:dbname=test;host=mysql';
$dbuser = 'test';
$dbpassword = 'test';
try {
    $db = new PDO($dsn,$dbuser,$dbpassword);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode('DB接続エラー' . $e->getMessage());
    exit();
}

// DBの値を配列に格納
$records = $db->prepare('select value from prechallenge3 where value <= ?  order by value');
$records->bindParam(1,$limit,PDO::PARAM_INT);
$records->execute();
foreach($records as $loop){
    $nums[] = intval($loop['value']);
}

//全組み合わせ取得
function combination($total,$part){
    $totalNum = count($total);
    if($part == 1){
        for($i = 0; $i < $totalNum; $i++){
        $arrs[$i] = array($total[$i]);
        }
    } elseif ($part > 1){
        $j=0;
        for($i=0; $i < $totalNum-$part+1; $i++){
        $ts=combination(array_slice($total,$i+1),$part-1);
            foreach($ts as $t){
                array_unshift($t,$total[$i]);
                $arrs[$j] = $t;
                $j++;
            }
        }
    }
    return $arrs;
}

//答えを求め最終の変数に格納   
for ($i=0; $i < count($nums); $i++) { 
    $compare = combination($nums,$i+1);
    for ($j=0; $j < count($compare); $j++) { 
        if($limit === array_sum($compare[$j])) {
            $ans[] = $compare[$j];
        }
    }
}

//答え出力
if(is_null($ans)) {
    $ans = [[]];
}
echo json_encode($ans,JSON_NUMERIC_CHECK);
