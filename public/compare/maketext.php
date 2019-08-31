<?php

define("DB1","tmc");
define("DB2","v2");

$con1 = mysqli_connect("localhost","root");
mysqli_select_db($con1,DB1);

$con2 = mysqli_connect("localhost","root");
mysqli_select_db($con2,DB2);

$excluded_fields = ['donor_photo','lfinger','rfinger'];

// $tables = [
//     "bloodproc" => "bloodproc_no",
//     "bloodtest" => "bloodtest_no",
//     "blood_label" => "label_no",
//     "blood_typing" => "bloodtyping_no",
//     "bts_blood_request" => "seqno",
//     "bts_blood_request_dtls" => "id",
//     "bts_other_source" => "seqno",
//     "bts_patient" => "seqno",
//     "bts_physician" => "seqno",
//     "bts_reactions" => "request_dtl_id",
//     // "component" => "donation_id",
//     "donation_schedules" => "sched_id",
//     // "donor" => "seqno",
// ];
define("FOLDER","1");
define("SOURCE","t1.");
define("FACILITY_CD","13109");

// mkdir("tmc/donor/1");
// mkdir("tmc/donor/2");

// $conflicts = getConflicts('bloodproc','bloodproc_no',"where t1.facility_cd = '".FACILITY_CD."'");
$conflicts = getConflicts('donor','seqno',"where 1=1");

foreach($conflicts as $i => $r){
    $file = fopen("tmc/donor/".FOLDER."/".$i.".txt","w");
    // $file = fopen("v1/bloodproc/2/".$i.".txt","w");
    fwrite($file,json_encode(utf8ize($r)));
    fclose($file);
}

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
    $query = "SELECT GROUP_CONCAT(COLUMN_NAME) as 'columns' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '".DB1."'";
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

function getConflicts($name,$key,$filter){
    global $con1;

    $query = "SELECT ".fieldsRemoveExclusion($name,SOURCE)." FROM ".DB1.".$name t1 INNER JOIN ".DB2.".$name t2 ON t1.$key = t2.$key $filter";
    // $query = "SELECT ".fieldsRemoveExclusion($name,"t2.")." FROM ".DB1.".$name t1 INNER JOIN ".DB2.".$name t2 ON t1.$key = t2.$key $filter";
    // $query = "SELECT count(*) FROM ".DB1.".$name t1 INNER JOIN ".DB2.".$name t2 ON t1.$key = t2.$key";
    // exit($query);

    $run = mysqli_query($con1,$query);

    $data = [];

    while($row = mysqli_fetch_assoc($run)){
        $data[$row[$key]] = $row;
    }

    return $data;
}

function getCounterparts($ids,$name,$key){

    global $con2;

    $query = "SELECT ".fieldsRemoveExclusion($name,"")." FROM $name WHERE $key IN (?)";

    $ids_str = implode($ids,"','");
    $ids_str = "'".$ids_str."'";

    $query = str_replace("?",$ids_str,$query);

    $run = mysqli_query($con2,$query);

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