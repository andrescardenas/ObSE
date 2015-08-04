<?php
header('Content-Type: application/json');

include_once('funcionesBD.php');
include_once('funcionesSoyExperto.php');

$datos = array();
$formulario = json_decode(file_get_contents("php://input"));


if( strcmp('datos_iniciales', $formulario->accion) == 0 ){
	$user = $formulario->user;

	$SQLUsuario = "	SELECT u.*, p.nombre as pais, c.nombre as ciudad, CONVERT(GROUP_CONCAT(DISTINCT(ut.numero), ':-:', ut.sl_tipotelefono) USING latin1) as telefonos, CONVERT(GROUP_CONCAT( DISTINCT(ue.email)) USING latin1) as emails 
					FROM usuarios u
					LEFT JOIN usuarios_telefonos ut ON ut.id_usuario = u.id
					LEFT JOIN usuarios_emails ue ON ue.id_usuario = u.id
					LEFT JOIN ciudades c ON c.id = u.sl_ciudad
					LEFT JOIN departamentos d ON d.id = c.sl_departamento
					LEFT JOIN paises p ON p.id = d.sl_pais
					WHERE u.id = ".$user->id; 
	insertarTablaArray_v2($usuario, $SQLUsuario,'usuario');

	$usuario[usuario] = rutasCompletasImagen( $usuario[usuario], 'http://soyexperto.net/sitio/files/usuarios/fi_name_imagen.', 'imagen' );
	$datos[usuario] = $usuario[usuario][0];
	//rutasCompletasImagen( $actividades[actividades], 'http://www.dejusticia.org/files/'.$carpetaImagenes.'/fi_name_imagen.', 'imagen' );

	//separando emails
	$explode_emails = explode(",", $datos[usuario][emails]);
	$datos[usuario][emails] = array();
	$posicion = 0;
	foreach ($explode_emails as $email) {
		$emailTemp = array();
		$emailTemp[id] = $posicion;
		$emailTemp[email] = $explode_emails[$posicion];
		array_push($datos[usuario][emails], $emailTemp);
		$posicion++;
	}

	//separando telefonos
	$explode_telefonos = explode(",", $datos[usuario][telefonos]); 
	$datos[usuario][telefonos] = array();
	foreach ($explode_telefonos as $telefonos) {
		$explode_telefono = explode(":-:", $telefonos);
		$telefonoTemp = array();
		$telefonoTemp[numero] = $explode_telefono[0];	
		$telefonoTemp[tipo] = 	$explode_telefono[1];
		array_push($datos[usuario][telefonos], $telefonoTemp);
	}
}else if( strcmp('editar_datos', $formulario->accion) == 0 ){

}

echo json_encode($datos);
?>