<?php

include_once('funcionesBD.php');
include_once ('funcionesSoyExperto.php');

$plan = array();
$plan=listarPlanesActivosPublicos();
echo json_encode($plan);
?>

