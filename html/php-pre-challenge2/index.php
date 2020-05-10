<?php
$array = explode(',', $_GET['array']);

// 修正はここから
$length = count($array);
for ($i = 0; $i < $length; $i++) {
    $limit = $length - $i;
    for ($j=1; $j < $limit; $j++) { 
        if($array[$j-1] > $array[$j]) {
            $temp = $array[$j];
            $array[$j] = $array[$j-1];
            $array[$j-1] = $temp;
        }
    }
}
// 修正はここまで
echo "<pre>";
print_r($array);
echo "</pre>";
