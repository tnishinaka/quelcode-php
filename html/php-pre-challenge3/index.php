<?php
$limit = $_GET['target'];
$limit = mb_convert_kana($limit, 'a' , 'UTF-8');
if(!is_numeric($limit) || $limit <= 0 || preg_match('/^([1-9]\d*|0)\.(\d+)?$/', $limit)) {
    http_response_code(400);
}
$dsn = 'mysql:dbname=test;host=mysql';
$dbuser = 'test';
$dbpassword = 'test';
try {
    $db = new PDO($dsn,$dbuser,$dbpassword);
} catch (PDOException $e) {
    echo 'DB接続エラー' . $e->getMessage();
}

// DBの値を配列に格納
$records = $db->query('select value from prechallenge3');
$data = $records->fetchAll();
foreach($data as $key=>$value) {
    $num[] = $value[0];
}

//全組み合わせ取得
function kumiawase($zentai,$kiritori){
    $zentaisu = count($zentai);
    if($kiritori == 1){
        for($i = 0; $i < $zentaisu; $i++){
        $arrs[$i] = array($zentai[$i]);
        }
    } elseif ($kiritori > 1){
        $j=0;
        for($i=0; $i < $zentaisu-$kiritori+1; $i++){
        $ts=kumiawase(array_slice($zentai,$i+1),$kiritori-1);
            foreach($ts as $t){
                array_unshift($t,$zentai[$i]);
                $arrs[$j] = $t;
                $j++;
            }
        }
    }
    return $arrs;
}

//答えを求め最終の変数に格納   
for ($i=1; $i < count($num); $i++) { 
    $kuraberu = kumiawase($num,$i);
    for ($j=0; $j < count($kuraberu); $j++) { 
        if($limit == array_sum($kuraberu[$j])) {
            $ans[] = $kuraberu[$j];
        }
    }
}

//答え出力
if(is_null($ans)) {
    $ans = [[]];
}
echo json_encode($ans);
