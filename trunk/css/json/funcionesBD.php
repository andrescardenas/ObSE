<?php

function insertarTablaArray_v2( &$destino, $SQL, $nombrePosicion){

	include("../bodega/BD.php");
	$temps = array();

    $posicion = 0;	
    	foreach ($BD->query($SQL) as $fila) {
		    $temp = array();
		    //insertando datos tarea SIN responsables
		    $i = 0;
		    foreach ($fila as $key => $value) {
		        if (!($i % 2)) {
		            $temp[$key] = $value;
		        }
		        $i++;
		    }

		    array_push($temps, $temp);
		    $posicion++;
		}

		$destino[$nombrePosicion] = $temps;

		$BD = null;
}

function ejecutarQuery( $SQL ){
	include("../bodega/BD.php");
	$BD->query($SQL);
	$BD = null;
}

function insertarFila( $SQL ){
	$id = -1;
	include("../bodega/BD.php");
	$BD->query($SQL);
	$id = $BD->lastInsertId();
	$BD = null;
	return $id;
}