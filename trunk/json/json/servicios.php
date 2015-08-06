<?php
header('Content-Type: application/json');

include_once('funcionesBD.php');

$datos = array();

$mensaje = json_decode(file_get_contents("php://input"));

$SQLServicios ="SELECT us.id, us.id_usuario, us.titulo, us.descripcion, SUM(sc.valoracion)/COUNT(us.id) AS valoracion, CONVERT( GROUP_CONCAT(DISTINCT(e.id),':-:',e.nombre) USING latin1) AS etiquetas
                FROM usuarios_servicios us
                LEFT JOIN servicios_comentarios sc  ON us.id = sc.id_servicio
                LEFT JOIN servicios_etiquetas se ON se.id_servicio = us.id 
                LEFT JOIN etiquetas e ON e.id = se.id_etiqueta
                WHERE us.id_usuario = ".$mensaje->user->id." 
                GROUP BY us.id";
insertarTablaArray_v2($servicios, $SQLServicios, 'servicios');

foreach ($servicios[servicios] as $key => $value) {
    $explode_etiquetas = explode(",", $servicios[servicios][$key][etiquetas]);
    $servicios[servicios][$key][etiquetas] = array();
    foreach ($explode_etiquetas as $k => $v) {
        $explode_etiqueta = explode(":-:",$explode_etiquetas[$k]);
        $etiquetaTemp = array();
        $etiquetaTemp[id] = $explode_etiqueta[0];
        $etiquetaTemp[nombre] = $explode_etiqueta[1];
        array_push($servicios[servicios][$key][etiquetas], $etiquetaTemp);
    }
}

$datos[servicios] = $servicios[servicios];

echo json_encode($datos);
?>
