<?php
header('Content-Type: application/json');

include_once('funcionesBD.php');

$datos = array();

$mensaje = json_decode(file_get_contents("php://input"));

$SQLPaises = "SELECT id, nombre FROM paises ORDER BY nombre ASC";
insertarTablaArray_v2($datos, $SQLPaises, 'paises');
$SQLCiudades = "SELECT * FROM ciudades ORDER BY nombre ASC";
insertarTablaArray_v2($datos, $SQLCiudades, 'ciudades');
$SQLTiposIdentificacion = "SELECT * FROM tiposidentificacion ORDER BY nombre ASC";
insertarTablaArray_v2($datos, $SQLTiposIdentificacion, 'tipos_identificacion');
$SQLTiposTelefono = "SELECT * FROM tipostelefono ORDER BY nombre ASC";
insertarTablaArray_v2($datos, $SQLTiposTelefono, 'tipos_telefono');

if ( strcmp('contrato',$mensaje->accion) == 0) {
	$SQLContrato = "SELECT tc.nombre as titulo , c.* 
					FROM contratos c
					LEFT JOIN tiposcontratos tc ON tc.id = c.sl_tipo
					WHERE c.id = ".$mensaje->id_contrato; 
	insertarTablaArray_v2($contrato,$SQLContrato,'contrato');
	$datos[contrato] = $contrato[contrato][0];
}else if ( strcmp('referentes',$mensaje->accion) == 0) {
	
	$SQLReferentesUsuario = "	SELECT u.usuario, CONCAT(u.primer_nombre,' ', u.primer_apellido) as nombre_completo 
								FROM invitaciones inv
								LEFT JOIN usuarios u ON u.id = inv.id_usuario
								WHERE inv.correo_invitado LIKE '".$mensaje->referido."'";
	insertarTablaArray_v2($referentes, $SQLReferentesUsuario, 'referentes'); 
	$datos[referentes] = $referentes[referentes];

	$SQLInfoReferente = " SELECT CONCAT(u.primer_nombre,' ', u.primer_apellido) as nombre_completo, u.usuario FROM usuarios u WHERE u.usuario LIKE '".$mensaje->referente."'";
	insertarTablaArray_v2($info_referente, $SQLInfoReferente, 'info_referente'); 
	$datos[info_referente] = $info_referente[info_referente][0];
}else if ( strcmp('registro_rapido',$mensaje->accion) == 0) {
	$SQLExisteUsuario = "SELECT * FROM usuarios WHERE usuario = '$mensaje->usuario'";
	insertarTablaArray_v2($existe_usuario, $SQLExisteUsuario, 'existe_usuario');

	$SQLExisteIdentificacion = "SELECT * FROM usuarios WHERE identificacion = '$mensaje->identificacion'";
	insertarTablaArray_v2($existe_identificacion, $SQLExisteIdentificacion, 'existe_identificacion');
	
	
	if( count($existe_identificacion[existe_identificacion]) != 0 ){
		$datos = array('status'=> 'error_cedula', 'mensaje'=>'Este numero de identificación ya existe!');
	}else if ( count($existe_usuario[existe_usuario]) != 0 ) {
		$datos = array('status'=> 'error_usuario', 'mensaje'=>'Este nombre de usuario ya existe!');
	}else{
		/*$SQLInsertarUsuario = "INSERT INTO usuarios (id, usuario, primer_nombre, primer_apellido, sl_tipoidentificacion, identificacion, da_fechanacimiento, clave, recordatorio, sl_ciudad, bl_acepto) 
							VALUES (NULL, '$mensaje->usuario', '$mensaje->primer_nombre', '$mensaje->primer_apellido', $mensaje->tipo_identificacion, '$mensaje->identificacion', '$mensaje->fecha', '$mensaje->clave1', '$mensaje->recordatorio', $mensaje->ciudad, 1)";
		$id_usuario = insertarFila($SQLInsertarUsuario);*/

		$SQLInsertarUsuario = "INSERT INTO usuarios (id, usuario, referido_por, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, clave, recordatorio, bl_acepto) 
							VALUES (NULL, '$mensaje->usuario', '$mensaje->referido_por','$mensaje->primer_nombre', '$mensaje->segundo_nombre', '$mensaje->primer_apellido', '$mensaje->segundo_apellido', '$mensaje->clave1', '$mensaje->recordatorio', 1)";
		$id_usuario = insertarFila($SQLInsertarUsuario); //echo "{".$SQLInsertarUsuario."}";

		$SQLInsertarTelefono = "INSERT INTO usuarios_telefonos (id, id_usuario, sl_tipotelefono, numero) VALUES (NULL, $id_usuario, 1, '123 1212')"; 
		ejecutarQuery($SQLInsertarTelefono);

		$SQLInsertarEmail = "INSERT INTO usuarios_emails (id, id_usuario, email) VALUES (null, $id_usuario, '$mensaje->email')"; 
		ejecutarQuery($SQLInsertarEmail);

		$datos = array('status'=> 'OK', 'mensaje'=>'Usuario Registrado Correctamente ['.$id_usuario.']', 'user' => array( 'id' => $id_usuario, 'nombre' => $mensaje->primer_nombre.' '.$mensaje->primer_apellido, 'usuario' => $mensaje->usuario) );
	}
}


/*
Model: {
  "telefono": {
    "tipo": "2",
    "numero": "3006595458"
  },
  "email": "gui@gmail.com",
  "referido_por": "deivit"
}*/

echo json_encode($datos);
?>
