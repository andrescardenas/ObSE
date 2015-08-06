<?php

include_once('funcionesBD.php');

$SQLBusquedas = "SELECT * FROM busquedas WHERE bl_activo = 1 ORDER BY length(frase) ASC";
insertarTablaArray_v2($datos, $SQLBusquedas, 'busquedas');

echo json_encode($datos);
?>