<?php
$array = explode(',', $_GET['array']);

// 修正はここから
for ($i = 0; $i < count($array); $i++) {
    for ($j=1; $j < count($array); $j++) { 
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
