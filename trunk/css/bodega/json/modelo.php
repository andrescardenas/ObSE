<?php

include('../BD.php');
include('classes/modelo.class.php');

//LOGS: INSERT INTO `db155458_dejusticia`.`_logs` (`id`, `id_user`, `time`, `table_name`, `id_registry`, `action`) VALUES ('', '2', 'CURRENT_TIMESTAMP', '2', '2', '2');

function getCurrentURL(){
    $currentURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
    $currentURL .= $_SERVER["SERVER_NAME"];
 
    if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
    {
        $currentURL .= ":".$_SERVER["SERVER_PORT"];
    } 
 
        $currentURL .= $_SERVER["REQUEST_URI"];
    return $currentURL;
}

$objetoModelo = new Modelo();
$objetoModelo->abrirLog();

$mensajeControlador = json_decode(file_get_contents("php://input"));

$accion = $mensajeControlador->accion;
$id_user = $mensajeControlador->id_user;
$objetoModelo->escribirLog(" Accion ..." . $accion . ".....usuario[$id_user] ");

if (strcmp("crear", $accion) == 0) {

    $nombreTabla = $mensajeControlador->modelo;
    $formulario = $mensajeControlador->formulario;
    $datos = array();
    $datosJSON = array();

    foreach ($formulario as $key => $value) {
        $posicion = strpos($key, 's2');

        if ($posicion !== -1 && $posicion !== FALSE) { // s2
        } else if (strpos($key, 'file') !== 0) {
            $datosJSON[$key] = $value;
        }
    }

    array_push($datos, $datosJSON);
    $objetoModelo->crear($nombreTabla, $datos);
    //currentId
    $SQLMax_id =" SELECT MAX( id ) AS id FROM ".$nombreTabla;
    $lastID = 0;
    foreach ($BD->query($SQLMax_id) as $last_id) $lastID = $last_id[id];
    //_logs
    $campos_log = array();
    $log[id] = '';
    $log[id_user] = $mensajeControlador->id_user;
    $log[time] = date('Y-m-d H:i:s');;
    $log[table_name] = $nombreTabla;
    $log[id_registry] = $lastID;
    $log[action] = '1'; 
    $log[info] = '';
    foreach ($datos as $key => $value) {
        foreach ($value as $k => $v) {
            $log[info] .= $k.":".$v." - ";
        }
    }
    array_push($campos_log, $log);
    $objetoModelo->crear("_logs", $campos_log);
    //$objetoModelo->escribirLog("<br/>LOG ..." . $mensajeControlador->id_user . "..... <br/>");
    //INSERT INTO `db155458_dejusticia`.`_logs` (`id`, `id_user`, `time`, `table_name`, `id_registry`, `action`) VALUES ('', '2', 'CURRENT_TIMESTAMP', '2', '2', '2');

    foreach ($formulario as $key => $value) {
        $posicion = strpos($key, 's2');
        if ($posicion !== -1 && $posicion !== FALSE) { // s2 
            $thirdTableName = $key;

            $ThirdTableKeys = array();
            $SQLThirdTableKeys = "DESCRIBE " . $thirdTableName;
            foreach ($BD->query($SQLThirdTableKeys) as $ttkeys) {
                array_push($ThirdTableKeys, $ttkeys['Field']);
            }
            foreach ($value as $v) { //{[0->3][1->12][2->22]}
                $selects = array();
                $selectValues = array();
                $selectValues[$ThirdTableKeys[0]] = 0;
                $selectValues[$ThirdTableKeys[1]] = $objetoModelo->ultimoIdTabla($nombreTabla);
                $selectValues[$ThirdTableKeys[2]] = $v;
                array_push($selects, $selectValues);
                $objetoModelo->crear($thirdTableName, $selects);
            }
        }
    }
    echo $objetoModelo->ultimoIdTabla($nombreTabla); 
} else if (strcmp("eliminar", $accion) == 0) {

    $modelo = $mensajeControlador->modelo;
    $ID = $mensajeControlador->id;

    $archivosBorrar = array();
    $SQLcamposModelo = "DESCRIBE " . $modelo;
    foreach ($BD->query($SQLcamposModelo) as $ttkeys) {
        if ( strpos($ttkeys['Field'], "fi_name_") === 0 ){
            array_push($archivosBorrar, $ttkeys['Field']);
            $objetoModelo->escribirLog("*(".$ttkeys['Field']."[".strpos($ttkeys['Field'], "fi_name_")."])");
        }
    }

    $SQLforeign_keys = "SELECT TABLE_NAME, REFERENCED_TABLE_NAME
                        FROM INFORMATION_SCHEMA.key_column_usage
                        WHERE TABLE_NAME LIKE '%" . $modelo . "_%' AND REFERENCED_TABLE_NAME != '" . $modelo . "' GROUP BY TABLE_NAME";

    //tiene archivo o no la tabla relacionada q se va a borrar???
    $hayArchivo = false;
    $campoArchivoTablaRelacionada = "";
    $extensionArchivo = "";
    $i = 1;
    $nombreSegundoCampo = "";

    $thirdTableName = "";
    foreach ($BD->query($SQLforeign_keys) as $column_name) {
        $thirdTableName = $column_name['TABLE_NAME'];

        $ThirdTableKeys = array();
        $SQLThirdTableKeys = "DESCRIBE " . $thirdTableName;
        foreach ($BD->query($SQLThirdTableKeys) as $ttkeys) {
            if ( $i === 2 ) $nombreSegundoCampo = $ttkeys['Field'];

            // cual es la ruta total del archivo (../files/$thirdTableName/archivo)
            if ( strcmp($ttkeys['Field'], "fi_name_") > 2){
                $campoArchivoTablaRelacionada = $ttkeys['Field'];
                $objetoModelo->escribirLog(" si tiene archivo y su ruta es: " . $campoArchivoTablaRelacionada . "EXTENSION(".$campoArchivoTablaRelacionada.")<br />");
                $hayArchivo = true;
            }
            //si encontr archivo en la tabla relacional actual borrarlo
            if ( $hayArchivo ){
                $SQLidsRelacion = "SELECT id,".$campoArchivoTablaRelacionada." from " . $thirdTableName . " WHERE " . $nombreSegundoCampo . " = " . $ID;
                foreach ($BD->query($SQLidsRelacion) as $idRelacion) {
                    $tipoArchivo = explode(".", $idRelacion[$campoArchivoTablaRelacionada]);
                    $objetoModelo->escribirLog("'BORRANDO... --> '../files/'".$thirdTableName."/".$ttkeys['Field'].".".$idRelacion[id].".".$tipoArchivo[1]);
                    unlink('../files/'.$thirdTableName."/".$ttkeys['Field'].".".$idRelacion[id].".".$tipoArchivo[1]);
                }
                $hayArchivo = false;
            }

            array_push($ThirdTableKeys, $ttkeys['Field']);
            $i++;
        }

        $SQLforeign_keys_delete = "DELETE FROM " . $thirdTableName . " WHERE " . $ThirdTableKeys[1] . " = " . $ID;
        $BD->query($SQLforeign_keys_delete);
        //_logs
        $campos_log = array();
        $log[id] = '';
        $log[id_user] = $mensajeControlador->id_user;
        $log[time] = date('Y-m-d H:i:s');;
        $log[table_name] = $thirdTableName;
        $log[id_registry] = $ID;
        $log[action] = '3'; 
        $log[info] = '....';
        array_push($campos_log, $log);
        $objetoModelo->crear("_logs", $campos_log);
        $objetoModelo->escribirLog("<br/>LOG ..." . $mensajeControlador->id_user . "..... <br/>");
        //INSERT INTO `db155458_dejusticia`.`_logs` (`id`, `id_user`, `time`, `table_name`, `id_registry`, `action`) VALUES ('', '2', 'CURRENT_TIMESTAMP', '2', '2', '2');
    }

    $objetoModelo->eliminar($modelo, 'id', $ID);
    //_logs
    $campos_log = array();
    $log[id] = '';
    $log[id_user] = $mensajeControlador->id_user;
    $log[time] = date('Y-m-d H:i:s');;
    $log[table_name] = $modelo;
    $log[id_registry] = $ID;
    $log[action] = '3'; 
    $log[info] = '..'.$ID.'..';
    array_push($campos_log, $log);
    $objetoModelo->crear("_logs", $campos_log);
    $objetoModelo->escribirLog("<br/>LOG ..." . $mensajeControlador->id_user . "..... <br/>");

    //INSERT INTO `db155458_dejusticia`.`_logs` (`id`, `id_user`, `time`, `table_name`, `id_registry`, `action`) VALUES ('', '2', 'CURRENT_TIMESTAMP', '2', '2', '2');
    //$objetoModelo->cerrarLog();

    foreach ($archivosBorrar as $campo) {

        $urlActual = getCurrentURL();
        $explotar = explode("/",$urlActual);
        $UrlSize = count($explotar)-1;
        $fin = strpos($urlActual, $explotar[$UrlSize]);
        $inic = substr($urlActual, 0, $fin); 

        //Eliminando Archivo
        $respuesta = file_get_contents($inic."archivo.php?accion=eliminar&modelo=".$modelo."&campo=".$campo."&id=".$ID);
    }   
} else if (strcmp("editar", $accion) == 0) {

    //$objetoModelo->cerrarLog();

    $nombreTabla = $mensajeControlador->modelo;
    $formulario = $mensajeControlador->formulario;
    $ID = "";

    $datos = array();
    $datosJSON = array();

    foreach ($formulario as $key => $value) { //recorremos formulario
    //$objetoModelo->escribirLog(" 000--- [$key]: $value<br />");

        if (strcmp(gettype($value), "array") === 0) { //SI ES UNA RELACION CON ARRAY
            $otherTableName = $key;
            $arrayTemp = array();
            foreach ($value as $k => $v) {
                array_push($arrayTemp, $v);
            }

            $otherTableKeys = array();
            $SQLotherTableKeys = "DESCRIBE " . $otherTableName;
            foreach ($BD->query($SQLotherTableKeys) as $ttkeys) {
                array_push($otherTableKeys, $ttkeys['Field']);
            } $SQLCleanThirdTable = "DELETE FROM " . $otherTableName . " WHERE " . $otherTableKeys[1] . " = " . $ID;
            $BD->query($SQLCleanThirdTable);

            foreach ($arrayTemp as $newValskey => $newValskeyvalue) {
                $SQLInsertRow = "INSERT INTO  " . $otherTableName . " (" . $otherTableKeys[0] . "," . $otherTableKeys[1] . " ," . $otherTableKeys[2] . ")"
                        . "VALUES (NULL ,  " . $ID . ",  " . $newValskeyvalue . ")";
                $BD->query($SQLInsertRow);
            }

        } else {
            if (strcmp($key, "id") === 0){
                $ID = $value;
            }
            if ( strcmp( gettype($value), "object" ) === 0 ){

                if ( count ( (array) $value)  === 0 ){ 
                }

            } else {
                $datosJSON[$key] = $value;
            }
        }

    }

    array_push($datos, $datosJSON);
    $objetoModelo->editarTodo($nombreTabla, $datos);
    //_logs
    $campos_log = array();
    $log[id] = '';
    $log[id_user] = $mensajeControlador->id_user;
    $log[time] = date('Y-m-d H:i:s');;
    $log[table_name] = $nombreTabla;
    $log[id_registry] = $ID;
    $log[action] = '2'; 
    $log[info] = '....';
    foreach ($datos as $key => $value) {
        foreach ($value as $k => $v) {
            $log[info] .= $k.":".$v." - ";
        }
    }
    array_push($campos_log, $log);
    $objetoModelo->crear("_logs", $campos_log);
    $objetoModelo->escribirLog("<br/>LOG ..." . $mensajeControlador->id_user . "..... <br/>");
    //INSERT INTO `db155458_dejusticia`.`_logs` (`id`, `id_user`, `time`, `table_name`, `id_registry`, `action`) VALUES ('', '2', 'CURRENT_TIMESTAMP', '2', '2', '2');
}

$objetoModelo->cerrarLog();
