<?php
header('Content-Type: application/json');

include_once('funcionesBD.php');

$datos = array();

$mensaje = json_decode(file_get_contents("php://input"));

if ( strcmp('valores_iniciales',$mensaje->accion) == 0) {
    $SQLPlan = "SELECT * FROM usuario_plan WHERE id_usuario = ".$mensaje->id_usuario;
    insertarTablaArray_v2($plan, $SQLPlan, 'plan');

    $datos[plan] = $plan[plan][0];
}

echo json_encode($datos);


/*

SELECT us.titulo, us.descripcion, us.web, us.visitas, CONVERT( GROUP_CONCAT(DISTINCT(c.nombre)) USING latin1) AS caracteristicas, CONVERT( GROUP_CONCAT(DISTINCT(ue.email)) USING latin1) AS emails, CONVERT( GROUP_CONCAT(DISTINCT(ut.numero)) USING latin1) AS telefonos, CONVERT( GROUP_CONCAT(DISTINCT(e.id), ':-:', e.nombre) USING latin1) AS etiquetas, CONVERT( GROUP_CONCAT(DISTINCT(s_exp.id), ':-:', s_exp.lugar, ':-:', s_exp.fecha_inicio, ':-:', s_exp.fecha_fin, ':-:', s_exp.descripcion SEPARATOR '-::-') USING latin1) AS experiencias, CONVERT( GROUP_CONCAT(DISTINCT(s_hor.id), ':-:', s_hor.dia_semana, ':-:', s_hor.inicio, ':-:', s_hor.fin SEPARATOR '-::-') USING latin1) AS horarios, CONVERT( GROUP_CONCAT(DISTINCT(s_ubi.id), ':-:',s_ubi.barrio, ':-:',s_ubi.codigo_zip, ':-:',s_ubi.direccion SEPARATOR '-::-') USING latin1) AS ubicaciones
FROM usuarios_servicios us
LEFT JOIN servicios_caracteristicas sc ON sc.id_servicio = us.id
LEFT JOIN caracteristicas c ON c.id = sc.id_caracteristica
LEFT JOIN servicios_emails se ON se.id_servicio = us.id
LEFT JOIN usuarios_emails ue ON ue.id = se.id_email
LEFT JOIN servicios_telefonos st ON st.id_servicio = us.id
LEFT JOIN usuarios_telefonos ut ON ut.id = st.id_telefono
LEFT JOIN servicios_etiquetas s_eti ON s_eti.id_servicio = us.id
LEFT JOIN etiquetas e ON e.id = s_eti.id_etiqueta
LEFT JOIN servicios_experiencias s_exp ON s_exp.id_servicio = us.id
LEFT JOIN servicios_horarios s_hor ON s_hor.id_servicio = us.id
LEFT JOIN servicios_ubicaciones s_ubi ON s_ubi.id_servicio = us.id
LEFT JOIN ciudades ciu ON ciu.id = s_ubi.id_ciudad
WHERE us.id = 3

 */


