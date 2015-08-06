<?php

include("../BD.php");

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

class Select {

    var $id;
    var $name;

}

class FormField {

    var $label;
    var $model;
    var $widget; //text, date, textarea, autocomplete, select, radio
    var $required;
    var $listar; //aparece o no la columna al listar los registros
    var $tipo_llave; // foreign, unique
    var $options; //array

}

$data = array();
$formFields = array();

try {
    //$tableName = 'historias';
    $tableName = $_GET['table'];

    if (isset($tableName)) {
        $SQLDescribe = "DESCRIBE " . $tableName;

        foreach ($BD->query($SQLDescribe) as $field) {
            
            $hiddenONo = 0;
            if ( strpos($field['Field'] , "hd_") ===  FALSE ) $hiddenONo = -1;

            if( strrpos($field['Field'], 'fi_type_') !== false || strrpos($field['Field'], 'fi_size_') !== false ){
                //los campos de archivo fi_size y fi_typo no se envian
            }   else if ($field['Field'] != 'id' && $hiddenONo == -1 ) {

                $formField = new FormField();

                $labelReal = explode("_", $field['Field']);

                if ( strrpos($field['Field'], 'fi_name') !== false ){
                    $fileLabel = "";
                    $fileModel = "fi";
                    for( $i = 2; $i < count($labelReal); $i++ ){
                        $fileLabel .= " ".$labelReal[$i];
                        $fileModel .= "_".$labelReal[$i];
                    }

                    $formField->label = $fileLabel;
                    $formField->model = $field['Field'];

                } else {
                    
                    if ( count($labelReal) === 1){ 
                        $formField->label = $labelReal[0];
                    }else if ( count($labelReal) === 2){ 
                        $formField->label = $labelReal[1];
                    } else {
                        for( $i = 1; $i < count($labelReal); $i++ ){
                            $formField->label .= " ".$labelReal[$i];
                        }
                    }
                    $formField->model = $field['Field'];
                }

                //widget
                if ( strrpos($field['Field'], 'sl_') !== false 
                    || strrpos($field['Field'], 'rd_') !== false 
                    || strrpos($field['Field'], 'au_') !== false 
                    || strrpos($field['Field'], 'fi_name') !== false 
                    || strrpos($field['Field'], 'ta_') !== false 
                    || strrpos($field['Field'], 'da_') !== false
                    || strrpos($field['Field'], 'dt_') !== false 
                    || strrpos($field['Field'], 'bl_') !== false) {

                    if (strrpos($field['Field'], 'sl_') !== false)
                        $formField->widget = 'select';
                    else if (strrpos($field['Field'], 'rd_') !== false)
                        $formField->widget = 'radio';
                    else if (strrpos($field['Field'], 'au_') !== false)
                        $formField->widget = 'autocomplete';
                    else if (strrpos($field['Field'], 'ta_') !== false)
                        $formField->widget = 'textarea';
                    else if (strrpos($field['Field'], 'da_') !== false)
                        $formField->widget = 'date';
                    else if (strrpos($field['Field'], 'dt_') !== false)
                        $formField->widget = 'datetime';
                    else if (strrpos($field['Field'], 'fi_name') !== false)
                        $formField->widget = 'file';
                    else if (strrpos($field['Field'], 'bl_') !== false)
                        $formField->widget = 'boolean';

                    //llaves foraneas sencillas SIN SELECT2
                    $SQLforeign_keys = "SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                            FROM INFORMATION_SCHEMA.key_column_usage
                            WHERE TABLE_NAME = '" . $tableName . "' AND `COLUMN_NAME` = '" . $field['Field'] . "' AND CONSTRAINT_SCHEMA LIKE '".$database."'";

                    foreach ($BD->query($SQLforeign_keys) as $column_name) {
                        if (strlen($column_name['REFERENCED_TABLE_NAME']) != 0) {

                            //`TABLE_NAME` ,  `COLUMN_NAME` ,  `CONSTRAINT_NAME` ,  `REFERENCED_TABLE_NAME` ,  `REFERENCED_COLUMN_NAME` 
                            $relatedTable = $column_name['REFERENCED_TABLE_NAME'];

                            //Segundo Campo Tabla Relacionada
                            $SQLrelatedTableFields = "DESCRIBE " . $relatedTable;
                            $fieldNumber = 0;
                            $selectFieldName = "";
                            foreach ($BD->query($SQLrelatedTableFields) as $relatedTableField) {
                                if ($fieldNumber == 1)
                                    $selectFieldName = $relatedTableField['Field'];
                                $fieldNumber++;
                            }

                            //echo "rtn {{".$relatedTable."}}<br/>";
                            $selects = array();
                            $SQLselectFields = "SELECT * FROM " . $relatedTable;
                            foreach ($BD->query($SQLselectFields) as $selectField) {
                                $select = new Select();
                                $select->id = $selectField['id'];
                                $select->name = $selectField[$selectFieldName];
                                array_push($selects, $select);
                            }
                            $formField->options = $selects;
                        }
                    }
                } else if (strrpos($field['Type'], 'decimal') !== false) {
                    $formField->widget = 'text';
                } else if (strrpos($field['Type'], 'int') !== false || strrpos($field['Type'], 'varchar') !== false && strrpos($field['Field'], 'id_') === false) {
                    $formField->widget = 'text';
                /*}else if (strrpos($field['Type'], 'datetime') !== false) {
                    $formField->widget = 'datetime';
                }else if (strrpos($field['Type'], 'date') !== false) {
                    $formField->widget = 'date';*/
                } else if (strrpos($field['Type'], 'text') !== false) {
                    $formField->widget = 'textarea';
                }
                //required
                if ($field['Null'] == 'NO')
                    $formField->required = true;
                else
                    $formField->required = false;

                $formField->label = labelTabla($tableName, $field['Field']);

                //primera en mayúscula

                array_push($formFields, $formField);
            }
        }
        //SELECT 2 o TABLAS RELACIONADAS
        $formFieldForeignKey = new FormField();

        $SQLrelated_tableName = "SELECT TABLE_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.key_column_usage
            WHERE TABLE_NAME LIKE '%" . $tableName . "_%' AND REFERENCED_TABLE_NAME != '" . $tableName . "' AND CONSTRAINT_SCHEMA LIKE '".$database."'";
            //echo "[[$SQLrelated_tableName]]<br /><br />";
        $relatedTableName = "";
        $thirdRelatedTableName = "";
        foreach ($BD->query($SQLrelated_tableName) as $related_table) {
            $relatedTableName = $related_table['TABLE_NAME'];
            $thirdRelatedTableName = $related_table['REFERENCED_TABLE_NAME'];

            //echo "<br /> - Tabla Relacionada: " . $relatedTableName . " --> " . $thirdRelatedTableName . "<br />";
            $formFieldForeignKey->label = ucfirst($thirdRelatedTableName);
            $formFieldForeignKey->model = $relatedTableName;
            $formFieldForeignKey->required = 0;
            $formFieldForeignKey->tipo_llave = "foranea";


            //Segundo Campo Tercera Tabla Relacionada
            $SQLThirdRelatedTableFields = "DESCRIBE " . $thirdRelatedTableName;
            $fieldNumberT = 0;
            $selectFieldNameT = "";
            $orderByField = "";
            foreach ($BD->query($SQLThirdRelatedTableFields) as $relatedTableFieldT) {
                if ($fieldNumberT == 1)
                    $selectFieldNameT = $relatedTableFieldT['Field'];
                $fieldNumberT++;
                if ( $fieldNumberT == 2 ) $orderByField = $relatedTableFieldT['Field'];
            }

            //Num Campos Tabla que relaciona
            $SQLSecondRelatedTableFields = "DESCRIBE " . $relatedTableName;
            $fieldNumberS = 0;
            foreach ($BD->query($SQLSecondRelatedTableFields) as $relatedTableFieldS) {
                $fieldNumberS++;
            }

            if ($fieldNumberS == 3) { // select 2i
                $formFieldForeignKey->widget = "select2";
                //crear Arreglo de opciones del select
                $selectsThird = array();
                $SQLselectThirdFields = "SELECT * FROM " . $thirdRelatedTableName . " ORDER BY ".$orderByField." ASC ";
                
                foreach ($BD->query($SQLselectThirdFields) as $selectFieldThirdTable) {
                    $selectThird = new Select();
                    $selectThird->id = $selectFieldThirdTable['id'];
                    $selectThird->name = $selectFieldThirdTable[$selectFieldNameT];
                    array_push($selectsThird, $selectThird);
                }
                $formFieldForeignKey->options = $selectsThird;

                array_push($formFields, $formFieldForeignKey);
                $formFieldForeignKey = new FormField(); 

            } else if ($fieldNumberS > 3) { // tabla Completa Relacionada          
                $formFieldForeignKey->widget = "tablaTotal";
            }
        }

        $data[fields] = $formFields;

        /*echo "<pre>";
        print_r($data);
        echo "</pre>";*/

        echo json_encode($data);
    } else
        echo "Please Select a Valid Table Name";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>