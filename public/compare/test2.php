<?php
// header("Access-Control-Allow-Origin: *");
define("DB1","pbc");

$con1 = mysqli_connect("localhost","root");
mysqli_select_db($con1,DB1);

$excluded_fields = ['donor_photo','lfinger','rfinger'];

$tables = [
    "donor" => "seqno",
];

$response = [];

foreach($tables as $name => $key){

    $conflicts = getConflicts($name,$key);

    $counterparts = getCounterparts(array_keys($conflicts),$name,$key);

    $issues = getIssues($conflicts,$counterparts);

    $response[$name] = $issues;

    // echo "<pre>";
    
    // exit(print_r($issues));
}
// exit(print_r($response));

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

echo json_encode(utf8ize($response));
exit;

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return utf8_encode($d);
    }
    return $d;
}

function fieldsRemoveExclusion($table,$prefix){
    global $excluded_fields;
    global $con1;
    // $query = "SELECT REPLACE(GROUP_CONCAT(COLUMN_NAME), '".implode($excluded_fields,',')."', '') as 'columns' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = 'tmc'";
    $query = "SELECT GROUP_CONCAT(COLUMN_NAME) as 'columns' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$table."' AND TABLE_SCHEMA = '".DB1."'";
    // exit($query);
    $run = mysqli_query($con1,$query) or exit(mysqli_error($con1));
    $result = mysqli_fetch_object($run)->columns;
    $fields = explode(",",$result);
    $filtered = [];
    foreach($excluded_fields as $f){
        foreach($fields as $f2){
            if($f!= $f2){
                $filtered[count($filtered)] = $prefix.$f2;
            }
        }
    }
    $newset = implode($filtered,",");
    return $newset;
}

function getConflicts($name,$key){
    global $con1;

    $query = "SELECT ".fieldsRemoveExclusion($name,"t1.")." FROM ".DB1.".".$name." t1 INNER JOIN ".DB1.".".$name."2 t2 ON t1.$key = t2.$key ORDER BY seqno LIMIT 100";
    // $query = "SELECT count(*) FROM ".DB1.".$name t1 INNER JOIN ".DB2.".$name t2 ON t1.$key = t2.$key";
    // exit($query);

    $run = mysqli_query($con1,$query) or exit(mysqli_error($con1));

    $data = [];

    while($row = mysqli_fetch_assoc($run)){
        $data[$row[$key]] = $row;
    }

    return $data;
}

function getCounterparts($ids,$name,$key){

    global $con1;

    $query = "SELECT ".fieldsRemoveExclusion($name,"")." FROM ".$name."2 WHERE $key IN (?)";

    $ids_str = implode($ids,"','");
    $ids_str = "'".$ids_str."'";

    $query = str_replace("?",$ids_str,$query);

    $run = mysqli_query($con1,$query);

    $data = [];

    while($row = mysqli_fetch_assoc($run)){
        $data[$row[$key]] = $row;
    }

    return $data;
}

function getIssues($conflicts,$counterparts){

    $issues = [];

    foreach($conflicts as $key => $left){
        $right = $counterparts[$key];
        $compare = getComparison($left,$right);
        if(count($compare['differences'])){
            $issues[$key] = $compare;
        }
    }

    return $issues;
}

function getComparison($left,$right){
    $keys = array_keys($left);

    $differences = [];

    foreach($keys as $key){
        if($left[$key] != $right[$key]){
            $differences[$key] = [$left[$key],$right[$key]];
        }
    }

    $findings = [
        'left' => $left, 'right' => $right, 'differences' => $differences
    ];

    return $findings;
}