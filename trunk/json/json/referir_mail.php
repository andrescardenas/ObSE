<?php
header('Content-Type: application/json');

include_once('funcionesBD.php');

$datos = array();

$mensaje = json_decode(file_get_contents("php://input"));

/*echo "<pre>";
print_r($mensaje);
echo "</pre>";*/

$SQLPlanActual = "	SELECT up.id_plan, up.bl_activo, up.da_inicio, up.da_final, p.nombre, p.descripcion  
					FROM usuario_plan up
					LEFT JOIN planes p ON p.id = up.id_plan
					WHERE up.id_usuario = ".$mensaje->usuario->id." AND up.da_inicio <= '".date('Y-m-d')."' AND up.da_final >= '".date('Y-m-d')."'";
insertarTablaArray_v2($datos, $SQLPlanActual, 'plan'); //echo "{{".$SQLPlanActual."}}";
$datos[plan] = $datos[plan][0];

$SQLSumaMovimientosPositivos = "SELECT SUM(monto) AS monto 
								FROM usuario_plan_movimientos upm
								WHERE upm.id_usuario_plan = ".$datos[plan][id_plan]." AND id_tipo = 1";
insertarTablaArray_v2($datos, $SQLSumaMovimientosPositivos, 'monto');
$datos[monto] = $datos[monto][0];

echo json_encode($datos);
?>
