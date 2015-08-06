<?php
//header('Content-Type: application/json');

include_once('funcionesBD.php');

$datos = array();

$mensaje = json_decode(file_get_contents("php://input"));

/*echo "<pre>";
print_r($mensaje);
echo "</pre>";*/

$SQLMaximoNivel = "	SELECT max(nivel) as niveles					FROM r2_planes_niveles";
insertarTablaArray_v2($niveles, $SQLMaximoNivel, 'niveles');
$max = $niveles[niveles][0][niveles];

$hijoActual = $mensaje->usuario->usuario;

for ($i=0; $i < $max; $i++) { 
	//echo "<hr><br />[nivel actual: $i] hijo actual: $hijoActual";
	$SQLNivelesPadre = "SELECT padre.usuario as padre_usuario, up.bl_activo, up.id as usuario_plan, r2pn.*
						FROM usuarios u
						LEFT JOIN usuarios padre ON padre.usuario = u.referido_por
						LEFT JOIN usuario_plan up ON up.id_usuario = padre.id
						LEFT JOIN r2_planes_niveles r2pn ON r2pn.hd_planes = up.id_plan
						WHERE u.usuario = '".$hijoActual."' AND up.id_usuario = padre.id AND up.da_inicio <= '".date("Y-m-d")."' AND up.da_final >= '".date("Y-m-d")."' 
						ORDER BY r2pn.nivel ASC";
	insertarTablaArray_v2($niveles_padre, $SQLNivelesPadre, 'niveles_padre');
	/*echo "<hr><br />[nivel actual: $i] padre actual: ".$niveles_padre[niveles_padre][0][padre_usuario];

	echo "<br />(".intval(isset($niveles_padre[niveles_padre][$i])).")niveles_padre<pre>";
	print_r($niveles_padre[niveles_padre][$i]);
	echo "</pre>";*/
	if ( intval(isset($niveles_padre[niveles_padre][$i])) == 1 ){
		$monto_pago = (floatval($niveles_padre[niveles_padre][$i][porcentaje])*floatval($mensaje->monto))/100;
		if ( intval($niveles_padre[niveles_padre][$i][bl_activo]) == 1){
			//echo " <br />---> [ACTIVO] ---> pagar a este padre un porcentaje de ".$niveles_padre[niveles_padre][$i][porcentaje]." sobre el valor de ".$mensaje->monto." = ".$monto_pago;
			$SQLPagar = "INSERT INTO usuario_plan_movimientos (id, id_usuario_plan, ref_pago, dt_fecha, id_tipo, monto, sl_moneda, num_aprobacion, bl_acreditar) 
			VALUES (NULL, ".$niveles_padre[niveles_padre][$i][usuario_plan].", '1', '".date("Y-m-d H:i:s")."', 2, ".$monto_pago.", 1, '1', 1)"; echo "<br />............{{".$SQLPagar."}}";
			ejecutarQuery($SQLPagar);
		}else{
			//echo " <br />---> [INACTIVO] ---> pagar a este padre un porcentaje de ".$niveles_padre[niveles_padre][$i][porcentaje]." sobre el valor de ".$mensaje->monto." = ".$monto_pago;
			$SQLPagar = "INSERT INTO usuario_plan_movimientos (id, id_usuario_plan, ref_pago, dt_fecha, id_tipo, monto, sl_moneda, num_aprobacion, bl_acreditar) 
			VALUES (NULL, ".$niveles_padre[niveles_padre][$i][usuario_plan].", '1', '".date("Y-m-d H:i:s")."', 1, ".$monto_pago.", 1, '1', 0)"; echo "<br />............{{".$SQLPagar."}}";
			ejecutarQuery($SQLPagar);
		}
	}else {
		//echo "<br /><br />NO PAGA ESTE NIVEL!!!!<br /><br />";
	}

	$hijoActual = $niveles_padre[niveles_padre][0][padre_usuario];

	if ( strcmp($hijoActual,'guillomal373') == 0 ){
		$i = $max;
	}
}

//echo json_encode($datos);
?>
