<?php

header('Content-Type: application/json');

include_once('funcionesBD.php');

$formulario = json_decode(file_get_contents("php://input"));

$items = $formulario->items;
$pagina = $formulario->pagina;
$start = (intval($pagina) - 1) * $items;
$campos = $formulario->criterio;
/*echo "formulario<pre>";
print_r($formulario);
echo "</pre>";*/

//busqueda conocida
if ( intval($campos->nueva) == 1 ){
	//si es nueva pero ya ingresada ....
	$SQLExisteEnBusquedas = "SELECT id FROM busquedas WHERE frase LIKE '".$campos->frase."' ";
	insertarTablaArray_v2($busqueda_existente, $SQLExisteEnBusquedas, 'busqueda_existente');

	if ( count( $busqueda_existente[busqueda_existente] ) > 0 ){
		$SQLIncrementarVeces = "UPDATE  busquedas SET  veces = veces+1 WHERE  id = ".$busqueda_existente[busqueda_existente][0][id]; //echo "[[".$SQLExisteEnBusquedas."]]";
		ejecutarQuery($SQLIncrementarVeces);
	}else{
		//si es totalmente nueva en la base de datos de busquedas
		$SQLAgregarBusqueda = "INSERT INTO busquedas (frase, sl_idioma, veces) VALUES ('".$campos->frase."', 1, 1)"; //echo " ++ NUEVA: {{$SQLAgregarBusqueda}}";
		ejecutarQuery($SQLAgregarBusqueda);
	}
}else{
	$SQLVecesBusqueda = "UPDATE busquedas SET veces = veces+1 WHERE id = ".$campos->id; //echo " -- VIEJA: {{$SQLVecesBusqueda}}";
	ejecutarQuery($SQLVecesBusqueda);
}


$criterio = $campos->frase;

$SQLServiciosCriterio = "	SELECT us.* , CONVERT( GROUP_CONCAT( e.id,  ':-:', e.nombre ) USING latin1 ) AS etiquetas, u.usuario
							FROM usuarios_servicios us
							LEFT JOIN usuarios u ON u.id = us.id_usuario
							LEFT JOIN servicios_etiquetas se ON se.id_servicio = us.id
							LEFT JOIN etiquetas e ON e.id = se.id_etiqueta
							WHERE titulo LIKE '%".$criterio."%' ";
$SQLServiciosCriterioTotal = "	SELECT COUNT(*) AS total FROM ( SELECT COUNT(*) AS total 
								FROM usuarios_servicios us
								LEFT JOIN servicios_etiquetas se ON se.id_servicio = us.id
								WHERE titulo LIKE '%".$criterio."%' ";
//WHERE etiquetas
//primero buscar las etiquetas que se asemejan al criterio
$SQLEtiquetas = "SELECT id FROM etiquetas WHERE nombre LIKE '%".$criterio."%'";
insertarTablaArray_v2($etiquetas, $SQLEtiquetas, 'etiquetas');

////////usuario unicamente al dia en pago de plan a fecha de hoy////////////
$SQLServiciosCriterio .= " AND u.id IN ( SELECT u.id FROM usuarios u LEFT JOIN usuario_plan up ON up.id_usuario = u.id WHERE da_inicio <= '".date("Y-m-d")."' AND da_final >= '".date("Y-m-d")."' AND up.bl_activo = 1 ) ";
$SQLServiciosCriterioTotal .= " AND us.id_usuario IN ( SELECT u.id FROM usuarios u LEFT JOIN usuario_plan up ON up.id_usuario = u.id WHERE da_inicio <= '".date("Y-m-d")."' AND da_final >= '".date("Y-m-d")."' AND up.bl_activo = 1 ) ";
////////////////////////////////////////////////////////////////////////////

if ( count($etiquetas[etiquetas]) != 0 ){
	$SQLServiciosCriterio .= "	OR us.id IN ( SELECT id_servicio FROM servicios_etiquetas WHERE id_etiqueta IN (SELECT id FROM etiquetas WHERE nombre LIKE '%".$criterio."%') ) ";
	$SQLServiciosCriterioTotal .= "	OR us.id IN ( SELECT id_servicio FROM servicios_etiquetas WHERE id_etiqueta IN (SELECT id FROM etiquetas WHERE nombre LIKE '%".$criterio."%') ) ";
}
////////usuario unicamente al dia en pago de plan a fecha de hoy////////////
$SQLServiciosCriterio .= " AND u.id IN ( SELECT u.id FROM usuarios u LEFT JOIN usuario_plan up ON up.id_usuario = u.id WHERE da_inicio <= '".date("Y-m-d")."' AND da_final >= '".date("Y-m-d")."' AND up.bl_activo = 1 ) ";
$SQLServiciosCriterioTotal .= " AND us.id_usuario IN ( SELECT u.id FROM usuarios u LEFT JOIN usuario_plan up ON up.id_usuario = u.id WHERE da_inicio <= '".date("Y-m-d")."' AND da_final >= '".date("Y-m-d")."' AND up.bl_activo = 1 ) ";
////////////////////////////////////////////////////////////////////////////

$SQLServiciosCriterio .= "	GROUP BY us.id
							ORDER BY us.titulo ASC";
$SQLServiciosCriterio .= " 	LIMIT " . $start . ", " . $items. " "; 
insertarTablaArray_v2($datos, $SQLServiciosCriterio, 'resultados'); //echo "<br /><br />{{".$SQLServiciosCriterio."}} ";

$SQLServiciosCriterioTotal .= " GROUP BY se.id_servicio ) T";
insertarTablaArray_v2($datos, $SQLServiciosCriterioTotal, 'resultados_total'); //echo "<br />:::: <br /><br />{".$SQLServiciosCriterioTotal."}";
$datos[resultados_total] = intval($datos[resultados_total][0][total]); 
//$datos[query] = $SQLServiciosCriterio;
//$datos[query_total] = $SQLServiciosCriterioTotal;

//etiquetas
foreach ($datos[resultados] as $key => $value) {
	$etiquetas = array();
	$explode_etiquetas = explode(',',$datos[resultados][$key][etiquetas]);
	foreach ($explode_etiquetas as $etiqueta) {
		$explode_etiqueta = explode(':-:', $etiqueta);
		$etiquetaTemp = array();
		$etiquetaTemp[id] = $explode_etiqueta[0];
		$etiquetaTemp[nombre] = $explode_etiqueta[1];
		array_push($etiquetas, $etiquetaTemp);
	}
	$datos[resultados][$key][etiquetas] = $etiquetas;

	//calificacion
	$calificacion = array();
	for ($i=1; $i <= intval($datos[resultados][$key][calificacion]); $i++) array_push($calificacion, $i);
	$datos[resultados][$key][calificacion] = $calificacion;
}

/*echo "<pre>";
print_r($datos);
echo "</pre>";*/

echo json_encode($datos);
?>