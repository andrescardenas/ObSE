<?php 

////funciones////
function isSimpleRelation( $array, $title ){
    foreach ($array as $key => $value) {
        if ( strcmp($key,$title) === 0 )
            return true;
    }
    return false;
}

function tipoDato($tableName, $fieldName){
    include("../BD.php");
    $SQLdescribe = "DESCRIBE " . $tableName;
    foreach ($BD->query($SQLdescribe) as $row) {
            if ( strcmp($row['Field'],$fieldName) == 0 ){
                return $row['Type'];
            }
    }
    $BD = null;
}

function labelTabla($tableName, $fieldName){
    include("../BD.php");
    $SQLLabels = "SELECT * FROM _labels WHERE table_name = '".$tableName."'";

    foreach ($BD->query($SQLLabels) as $label) {
        if ( strcmp($label['field'], $fieldName) == 0 ){
            return $label['name'];
        }
    }
    return -1;
    $BD = null;
}
/////////////////

include("../BD.php");

class Table {

    var $total;
    var $columns;
    var $rows;
    var $relations;

}

class Object {

    var $id;
    var $uno;
    var $dos;
    var $tres;
    var $cuatro;
    var $cinco;
    var $count;

}

$envio = json_decode(file_get_contents("php://input")); 

try {

    $SQLisWhereAdd = 0;
    //$tableName = $_GET['table'];
    $tableName = $envio->table;

    //Describe MainTable
    $SQLdescribe = "DESCRIBE " . $tableName;
    $mainTableFields = array();
    foreach ($BD->query($SQLdescribe) as $row) array_push($mainTableFields, $row['Field']);

    //si nos llega un id, es decir si ésta es una tabla relacionada (1 -> *)
    if ( isset($envio->id) ){
        $tableNameArray = split("_", $tableName);
        $sufix = $tableNameArray[1];
        $singular_ParentName = substr($sufix, 0,(strlen($sufix)-2));
    }

    if (isset($tableName)) {

        $items = $envio->items;
        $start = (($envio->page)-1)*$items;

        $SQLSelect = "SELECT ".$tableName.".* FROM " . $tableName;
        $SQLCount = "SELECT COUNT(*) FROM " . $tableName;

        //si nos llega un id, es decir si ésta es una tabla relacionada (1 -> *)
        if ( isset($envio->id) ){
            $SQLSelect .= " WHERE hd_".$sufix." = ".$envio->id."";
            $SQLCount .= " WHERE hd_".$sufix." = ".$envio->id."";

        }else if( isset($envio->formulario) ){
            $s2Where = "";//ésto se usa para agregar al final del query principal, las clausuas WHERE solo para los s2
            $formulario = $envio->formulario;
            if ( count($formulario) > 0 ){
                
                //contando espacios en formulario de búsqueda
                $numPosicionesS2 = 0;
                $numPosicionesNoS2 = 0;
                foreach ($formulario as $key => $value) {
                    if ( strcmp($key, "s2_") > 1 ){ //contador de relaciones s2
                        $numPosicionesS2++;
                    } else { // contador del resto NO s2
                        $numPosicionesNoS2++;
                    }
                }

                $positionNoS2 = 0;
                $positionS2 = 0;
                foreach ($formulario as $key => $value) {

                    $fieldName = $key;

                    if ( strcmp($fieldName, "s2_") > 1 ){ //búsqueda relaciones s2

                        //DESCRIBE S2 TABLE
                        $SQLdescribeS2 = "DESCRIBE ".$fieldName;
                        $s2Table = array();
                        foreach ($BD->query($SQLdescribeS2) as $row) array_push($s2Table, $row['Field']);
                        $count = 0;

                        foreach ($value as $v) {
                            if ( $SQLisWhereAdd == 0){
                                $SQLSelect .= " WHERE ";
                                $SQLCount .= " WHERE ";
                                $SQLisWhereAdd = 1;
                            }

                            $SQLSelect .= " id IN (SELECT ".$s2Table[1]." FROM ".$fieldName." WHERE ".$s2Table[2]." = ".$v.") ";
                            $SQLCount .= " id IN (SELECT ".$s2Table[1]." FROM ".$fieldName." WHERE ".$s2Table[2]." = ".$v.") ";
                            if ( $count < count($value)-1 ){ 
                                $SQLSelect .= " AND ";
                                $SQLCount .= " AND ";
                            }
                            $count++;
                        }

                        if ( $positionS2 < $numPosicionesS2-1 || $numPosicionesNoS2 > 0){
                            $SQLSelect .= " AND ";
                            $SQLCount .= " AND ";
                        }

                        $positionS2++;
                    }else{ //búsqueda no relaciones s2

                        if ( $SQLisWhereAdd == 0 ){
                            $SQLSelect .= " WHERE ";
                            $SQLCount .= " WHERE ";
                            $SQLisWhereAdd = 1;
                        }

                        $tipoDato = tipoDato($tableName, $fieldName);
                    
                        if ( strcmp($tipoDato, "int") > 1 || strcmp($tipoDato, "tinyint") > 1){
                            $SQLSelect .= $tableName.".".$key." = ".$value."";
                            $SQLCount .= $tableName.".".$key." = ".$value."";
                        }else if ( strcmp($tipoDato, "text") > 1 || strcmp($tipoDato, "varchar") > 1){
                            $SQLSelect .= "UPPER(".$tableName.".".$key.") LIKE UPPER('%".$value."%')  collate utf8_general_ci";
                            $SQLCount .= "UPPER(".$tableName.".".$key.") LIKE UPPER('%".$value."%')  collate utf8_general_ci";
                        }
                        
                        if ( $positionNoS2 < $numPosicionesNoS2-1 ){
                            $SQLSelect .= " AND ";
                            $SQLCount .= " AND ";
                        }
                        $positionNoS2++;
                    }
                }
            }
        }
        $SQLSelect .= " ORDER BY ".$tableName.".id DESC LIMIT ".$start.", ".$items;

        //echo "{{{".$SQLSelect."}}}}";
        ///////////////

        $SQLtableActiveFields = "SELECT active_fields FROM _configuration WHERE table_name = '" . $tableName . "'";
        $tableActiveFields = $BD->query($SQLtableActiveFields);

        $totalRegistros = 0;
        $SQLTotalTableRows = $SQLCount;

        foreach ($BD->query($SQLTotalTableRows) as $key => $value) {
            foreach( $value as $total ){
                $totalRegistros = $total;
            }
        }

        //Cuales son campos activos para la vista Ej: 1,3,4
        $activeFields = array();
        array_push($activeFields, 0);

        /* Comprobar el número de filas que coinciden con la sentencia SELECT */
        if ($tableActiveFields->fetchColumn() > 0) {
            foreach ($BD->query($SQLtableActiveFields) as $activeField) {
                $activeFieldsTemp = explode(",", $activeField['active_fields']);
                for ($i = 0; $i < sizeof($activeFieldsTemp); $i++)
                    array_push($activeFields, $activeFieldsTemp[$i]);
            }
        } else array_push($activeFields, 1);

        //Crear el Objecto para enviar en el JSON
        $table = new Table();

        //Llenando el campo de columns en Table
        $column = 0;
        $columns = array();

        //Traer los nombres de los campos e insertarlos en la instancia 
        foreach ($mainTableFields as $row) {
            if (in_array($column, $activeFields))
                array_push($columns, $row);
            $column++;
        }

        $table->total = $totalRegistros;
        $table->columns = $columns;


        //TABLAS A CARGAR PARA RELACION DE SL, RD, AU
        $SQLSimpleRelationTables = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
                                    FROM INFORMATION_SCHEMA.key_column_usage
                                    WHERE TABLE_NAME LIKE '".$tableName."' 
                                    AND CONSTRAINT_NAME NOT LIKE 'PRIMARY' 
                                    AND CONSTRAINT_SCHEMA LIKE '".$database."'";

        $relatedTables = array();
        foreach ($BD->query($SQLSimpleRelationTables) as $relationTable) {
            $tempTableArray = array();
            //echo "Table: ".$relationTable['COLUMN_NAME'].".".$relationTable['REFERENCED_TABLE_NAME']."<br />";

            $SQLTableInfo = "SELECT * FROM ".$relationTable['REFERENCED_TABLE_NAME'];
            foreach ($BD->query($SQLTableInfo) as $relationTableInfo) $tempTableArray[$relationTableInfo['id']] = $relationTableInfo['nombre'];
            $relatedTables[$relationTable['COLUMN_NAME']] = $tempTableArray;
        }

        if ( strcmp($tableName, "guillo") != 0 ){//Llenando el campo de rows en Table
                $rows = array();
                foreach ($BD->query($SQLSelect) as $result) {
                    $object = new Object();
                    $object->id = $result['id'];
                    if (in_array('1', $activeFields)){
                        //echo "1";
                        
                        //si hay relaciones simples
                        if ( isSimpleRelation( $relatedTables, $table->columns[1] ) ){
        
                            $optionsArray = $relatedTables[$table->columns[1]];
                            $object->uno = $optionsArray[$result[$table->columns[1]]];
        
                        }else $object->uno = $result[$table->columns[1]];
                    }
                    if (in_array('2', $activeFields)){
                        //echo "2";
        
                        //si hay relaciones simples
                        if ( isSimpleRelation( $relatedTables, $table->columns[2] ) ){
        
                            $optionsArray = $relatedTables[$table->columns[2]];
                            $object->dos = $optionsArray[$result[$table->columns[2]]];
        
                        }else $object->dos = $result[$table->columns[2]];
                    }
                    if (in_array('3', $activeFields)){
                        //echo "3";
        
                        //si hay relaciones simples
                        if ( isSimpleRelation( $relatedTables, $table->columns[3] ) ){
        
                            $optionsArray = $relatedTables[$table->columns[3]];
                            $object->tres = $optionsArray[$result[$table->columns[3]]];
        
                        } else $object->tres = $result[$table->columns[3]];
                    }
                    if (in_array('4', $activeFields)){
                        //echo "4";
        
                        //si hay relaciones simples
                        if ( isSimpleRelation( $relatedTables, $table->columns[4] ) ){
        
                            $optionsArray = $relatedTables[$table->columns[4]];
                            $object->cuatro = $optionsArray[$result[$table->columns[4]]];
        
                        } else  $object->cuatro = $result[$table->columns[4]];
                    }
                    if (in_array('5', $activeFields)){
                        //echo "5";
        
                        //si hay relaciones simples
                        if ( isSimpleRelation( $relatedTables, $table->columns[5] ) ){
        
                            $optionsArray = $relatedTables[$table->columns[5]];
                            $object->cinco = $optionsArray[$result[$table->columns[5]]];
        
                        } else  $object->cinco = $result[$table->columns[5]];
                    }
                    $object->count = count($result)/2;
                    array_push($rows, $object);
                }
                $table->rows = $rows;
            } else {
                $posiciones = array('uno','dos','tres','cuatro','cinco');
                $posicionActual = 0;
                $rows = array();
                $countrows = array();
                foreach ($BD->query($SQLSelect) as $result) {
                    $object = new Object();
                    $object->id = $result['id'];
                    if ( $result[1] && in_array(1, $activeFields)) {
                        $object->$posiciones[$posicionActual] = $result[1];
                        $posicionActual++;
                    }
                    if ( $result[2] && in_array(2, $activeFields)) {
                        $object->$posiciones[$posicionActual] = $result[2];
                        $posicionActual++;
                    }
                    if ( $result[3] && in_array(3, $activeFields)) {
                        $object->$posiciones[$posicionActual] = $result[3];
                        $posicionActual++;
                    }
                    if ( $result[4] && in_array(4, $activeFields)) {
                        $object->$posiciones[$posicionActual] = $result[4];
                        $posicionActual++;
                    }
                    if ( $result[5] && in_array(5, $activeFields)) {
                        $object->$posiciones[$posicionActual] = $result[5];
                        $posicionActual++;
                    }
                    $object->count = count($result)/2;
                    array_push($rows, $object);
                    
                }
                $table->rows = $rows;
            } 

        //relaciones
        $SQLforeign_tables = "SELECT TABLE_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.key_column_usage
            WHERE TABLE_NAME LIKE 'r2_" . $tableName . "_%' AND CONSTRAINT_SCHEMA LIKE '".$database."' GROUP BY TABLE_NAME";

        $relations = array();
        foreach ($BD->query($SQLforeign_tables) as $column_name) array_push($relations, $column_name['TABLE_NAME']);
            //array_push($relations, $column_name['TABLE_NAME']);

        $table->relations = $relations;

        //Traer los Nombres de los Campos según la tabla _labels
        foreach ($columns as $key => $value) {
            $labelFinal = labelTabla($tableName, $columns[$key]);
            if ( $labelFinal != -1 ) $columns[$key] = $labelFinal;
        }
        $table->columns = $columns;
        ////////// 
        
        echo json_encode($table);
    } else
        echo "Please Select a Valid Table Name";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
$BD = null;


?>
