<?php

// header('Content-Type: application/json');

include_once ('funcionesBD.php');
include_once ('funcionesSoyExperto.php');

$mensaje = json_decode(file_get_contents("php://input"));

echo "\nResultado de pago: ".aplicaPagos($mensaje->usuario->id, 0, 0, calcularMaximoNivelesPlan(), $id_usuario,.$mensaje->monto , .$mensaje->id_plan);

?>