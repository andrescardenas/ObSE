<?php

//echo "<html><head><meta name='tipo_contenido'  content='text/html;' http-equiv='content-type' charset='utf-8'></head>";

include("../BD.php");

$tableName = $_GET['modelo'];
$ID = $_GET['id'];

$EditingRow = $BD->query("SELECT * FROM " . $tableName . " WHERE id =" . $ID);

$thisRow = array();
$i = 1;
foreach ($EditingRow as $row) {
    foreach ($row as $key => $value) {
        if ($i % 2)
            $thisRow[$key] = $value;
        $i = $i + 1;
    }
}

//SELECT 2 o TABLAS RELACIONADAS
$SQLrelated_tableName = "SELECT TABLE_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.key_column_usage
            WHERE TABLE_NAME LIKE '%" . $tableName . "_%' AND REFERENCED_TABLE_NAME != '" . $tableName . "' AND CONSTRAINT_SCHEMA LIKE '".$database."'";

$relatedTableName = "";
foreach ($BD->query($SQLrelated_tableName) as $related_table) {
    
    $referencedTableName = $related_table['REFERENCED_TABLE_NAME'];
    $referencedTableTablefields = array();
    foreach ($BD->query(" DESCRIBE ".$referencedTableName) as $resA) {
        array_push($referencedTableTablefields, $resA['Field']);
    }
    
    $referenceTableName = $related_table['TABLE_NAME'];
    $referenceTablefields = array();
    foreach ($BD->query(" DESCRIBE ".$referenceTableName) as $resB) {
        array_push($referenceTablefields, $resB['Field']);
    }
    
    $data = array();
    $SQLSecondRelatedTable = "SELECT " . $referenceTablefields[2] . " FROM " . $referenceTableName . " WHERE " . $referenceTablefields[1] . " = " . $ID;
    foreach ($BD->query($SQLSecondRelatedTable) as $relations) {
        $fieldName = $referenceTablefields[2];
        array_push($data, $relations[$fieldName]);
    }
    $thisRow[$referenceTableName] = $data;
}

echo json_encode($thisRow);
