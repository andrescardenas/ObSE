<?php
header('Content-Type: application/json');

include_once('funcionesBD.php');

$datos = array();

$mensaje = json_decode(file_get_contents("php://input"));

if ( strcmp('valores_iniciales',$mensaje->accion) == 0) {
	$SQLTelefonosUsuario = "SELECT id, numero
							FROM usuarios_telefonos
							WHERE id_usuario = ".$mensaje->id_usuario;
	insertarTablaArray_v2($datos, $SQLTelefonosUsuario, 'telefonos');

	$SQLEmailsUsuario ="SELECT id, email
						FROM usuarios_emails
						WHERE id_usuario = ".$mensaje->id_usuario;
	insertarTablaArray_v2($datos, $SQLEmailsUsuario, 'emails');

	$SQLPaises =   "SELECT id, nombre
					FROM paises 
					ORDER BY nombre ASC";
	insertarTablaArray_v2($datos, $SQLPaises, 'paises');

	$SQLCiudades = "SELECT id, nombre
					FROM ciudades 
					ORDER BY nombre ASC";
	insertarTablaArray_v2($datos, $SQLCiudades, 'ciudades');

	$SQLCaracteristicas = "	SELECT id, nombre 
							FROM caracteristicas
							ORDER BY nombre ASC";
	insertarTablaArray_v2($datos, $SQLCaracteristicas, 'caracteristicas');

    $SQLEtiquetas ="SELECT id, nombre 
                    FROM etiquetas 
                    ORDER BY nombre ASC";
    insertarTablaArray_v2($datos, $SQLEtiquetas, 'etiquetas');
}if ( strcmp('crear',$mensaje->accion) == 0) {
	$SQLCrearServicio = "INSERT INTO usuarios_servicios (id, id_usuario, titulo, descripcion, web) VALUES (NULL, $mensaje->id_usuario, '$mensaje->titulo','$mensaje->descripcion','$mensaje->web')";
    $id_servicio = insertarFila($SQLCrearServicio);
	echo $id_servicio;

	foreach ($mensaje->experiencias as $experiencia) {
		$SQLServicioExperiencia = "INSERT INTO servicios_experiencias (id, id_servicio, lugar, fecha_inicio, fecha_fin, descripcion) VALUES (NULL, $id_servicio,'$experiencia->donde', '".substr($experiencia->inicio,0,10)."','".substr($experiencia->fin,0,10)."','".$experiencia->descripcion."')";
        insertarFila($SQLServicioExperiencia);
	}

    foreach ($mensaje->telefonos as $telefono) {
        $SQLServicioTelefono = "INSERT INTO servicios_telefonos (id, id_servicio, id_telefono) VALUES (NULL, '$id_servicio', '".$telefono."')";
        insertarFila($SQLServicioTelefono);
    }

    foreach ($mensaje->emails as $email) {
        $SQLServicioEmails = "INSERT INTO servicios_emails (id, id_servicio, id_email) VALUES (NULL, '$id_servicio', '".$email."')";
        insertarFila($SQLServicioEmails);
    }

    foreach ($mensaje->ubicaciones as $ubicacion) {
        $SQLServicioUbicacion = "INSERT INTO servicios_ubicaciones (id, id_servicio, id_ciudad, barrio, codigo_zip, direccion) VALUES (NULL, $id_servicio,'$ubicacion->ciudad', '$ubicacion->barrio', '$ubicacion->codigozip','$ubicacion->direccion')";
        insertarFila($SQLServicioUbicacion);
    }

    foreach ($mensaje->horarios as $horario) {
        $dias_semana = "";
        $numDias = count($horario->dias)-1;
        foreach ($horario->dias as $k => $v) { 
            if ( $k < $numDias ) $dias_semana .= $v.",";
            else $dias_semana .= $v;
        }
        $inicio = substr($horario->inicio, 11, 6).'00';
        $fin = substr($horario->fin, 11, 6).'00';
        $SQLInsertHorario = "INSERT INTO servicios_horarios (id, id_servicio, dia_semana, inicio, fin) VALUES (NULL, $id_servicio, '$dias_semana', '$inicio', '$fin');";
        insertarFila($SQLInsertHorario);
    }

    foreach ($mensaje->caracteristicas as $caracteristica) {
        $SQLInsertCaracteristica = "INSERT INTO servicios_caracteristicas (id, id_servicio, id_caracteristica) VALUES (NULL, $id_servicio, '$caracteristica')";
        insertarFila($SQLInsertCaracteristica);
    }

    foreach ($mensaje->etiquetas as $etiqueta) {
        $SQLInsertEtiquetas = "INSERT INTO servicios_etiquetas (id, id_servicio, id_etiqueta) VALUES (NULL, $id_servicio, '$etiqueta')";
        insertarFila($SQLInsertEtiquetas);
    }
}if ( strcmp('eliminar',$mensaje->accion) == 0) {
    $id_servicio = $mensaje->id_servicio;
    //echo "eliminando servicio id: ".$mensaje->id;

    $SQLDeleteEtiquetas = "DELETE FROM servicios_etiquetas WHERE id_servicio = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
    $SQLDeleteEtiquetas = "DELETE FROM servicios_caracteristicas WHERE id_servicio = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
    $SQLDeleteEtiquetas = "DELETE FROM servicios_horarios WHERE id_servicio = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
    $SQLDeleteEtiquetas = "DELETE FROM servicios_ubicaciones WHERE id_servicio = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
    $SQLDeleteEtiquetas = "DELETE FROM servicios_emails WHERE id_servicio = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
    $SQLDeleteEtiquetas = "DELETE FROM servicios_telefonos WHERE id_servicio = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
    $SQLDeleteEtiquetas = "DELETE FROM servicios_experiencias WHERE id_servicio = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
    $SQLDeleteEtiquetas = "DELETE FROM usuarios_servicios WHERE id = $id_servicio"; ejecutarQuery($SQLDeleteEtiquetas);
}if ( strcmp('visita',$mensaje->accion) == 0) {

    //agregar visita
    $SQLAgregarVisita = "UPDATE usuarios_servicios SET visitas = visitas+1 WHERE id = ".$mensaje->id_servicio;
    ejecutarQuery($SQLAgregarVisita);

    $SQLServicio = "SELECT  us.titulo, us.descripcion, us.web, us.visitas, 
                            CONVERT( GROUP_CONCAT(DISTINCT(c.nombre)) USING latin1) AS caracteristicas, 
                            CONVERT( GROUP_CONCAT(DISTINCT(ue.email)) USING latin1) AS emails, 
                            CONVERT( GROUP_CONCAT(DISTINCT(ut.numero)) USING latin1) AS telefonos, 
                            CONVERT( GROUP_CONCAT(DISTINCT(e.id), ':-:', e.nombre) USING latin1) AS etiquetas, 
                            CONVERT( GROUP_CONCAT(DISTINCT(s_exp.lugar), ' (', s_exp.fecha_inicio, ' - ', s_exp.fecha_fin, '):-:', s_exp.descripcion SEPARATOR '-::-') USING latin1) AS experiencias, 
                            CONVERT( GROUP_CONCAT(DISTINCT(s_hor.id), ':-:', s_hor.dia_semana, ':-:', s_hor.inicio, ':-:', s_hor.fin SEPARATOR '-::- ') USING latin1) AS horarios, 
                            CONVERT( GROUP_CONCAT(DISTINCT(s_ubi.barrio), ', zip: ', s_ubi.codigo_zip SEPARATOR '-::-') USING latin1) AS ubicaciones
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
                    WHERE us.id = ".$mensaje->id_servicio; 
    insertarTablaArray_v2($servicio, $SQLServicio, 'servicio');
    $datos[servicio] = $servicio[servicio][0];

    //etiquetas
    $etiquetas = array();
    $explode_etiquetas = explode(',',$datos[servicio][etiquetas]);
    foreach ($explode_etiquetas as $etiqueta) {
        $explode_etiqueta = explode(':-:', $etiqueta);
        $etiquetaTemp = array();
        $etiquetaTemp[id] = $explode_etiqueta[0];
        $etiquetaTemp[nombre] = $explode_etiqueta[1];
        array_push($etiquetas, $etiquetaTemp);
    }
    $datos[servicio][etiquetas] = $etiquetas;

    //experiencias
    $_experiencias = array();
    $explode_experiencias = explode("-::-", $datos[servicio][experiencias]);
    foreach ($explode_experiencias as $experiencias) {
        $explode_experiencia = explode(":-:", $experiencias);
        $_experiencia = array();
        $_experiencia[titulo] = $explode_experiencia[0];
        $_experiencia[descripcion] = $explode_experiencia[1];
        array_push($_experiencias, $_experiencia);
    }
    $datos[servicio][experiencias] = $_experiencias;

    
    //ubicaciones
    $_ubicaciones = array();
    $explode_ubicaciones = explode("-::-",$datos[servicio][ubicaciones]);
    $datos[servicio][ubicaciones] = $explode_ubicaciones;

    //horarios
    $horarios = array();
    $explode_horarios = explode("-::-",$datos[servicio][horarios]);
    $dias = array(1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo');
    foreach ($explode_horarios as $horario) {
        $explode_horario = explode(":-:", $horario);
        $_horario = array();
        $_horario[dias] = array();
        $explode_dias = explode(",", $explode_horario[1]); 
        sort($explode_dias);
        foreach ($explode_dias as $dia) array_push($_horario[dias], $dias[$dia]);
        //ordenar los dias
        $_horario[horas] =  " desde ".$explode_horario[2]." hasta ".$explode_horario[3];
        array_push($horarios, $_horario);
    }
    $datos[servicio][horarios] = $horarios;

    //comentarios
    $SQLComentarios = " SELECT sc.fecha, sc.comentario, sc.valoracion, u.id, u.usuario, u.fi_name_imagen, u.fi_type_imagen, u.fi_size_imagen 
                        FROM servicios_comentarios sc 
                        LEFT JOIN usuarios u ON u.id = sc.hd_autor
                        WHERE sc.id_servicio = ".$mensaje->id_servicio." 
                        ORDER BY sc.fecha DESC 
                        LIMIT 5"; 
    insertarTablaArray_v2($datos, $SQLComentarios, 'comentarios');

    //promedio calificacion
    $SQLCalificacion = "SELECT SUM(valoracion)/COUNT(*) as calificacion FROM servicios_comentarios WHERE id_servicio = ".$mensaje->id_servicio;
    insertarTablaArray_v2($calificacion, $SQLCalificacion, 'calificacion');
    $datos[calificacion_valor] = $calificacion[calificacion][0][calificacion];
    $datos[calificacion] = array();
    for ($i=0; $i < round($calificacion[calificacion][0][calificacion]); $i++) array_push($datos[calificacion], $i);

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


