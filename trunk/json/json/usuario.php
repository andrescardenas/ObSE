<?php
header('Content-Type: application/json');

//////////////////////////////
function generate_hash($password, $cost=11){

        $salt=substr(base64_encode(openssl_random_pseudo_bytes(17)),0,22);

        $salt=str_replace("+",".",$salt);

        $param='$'.implode('$',array( "2y", //select the most secure version of blowfish (>=PHP 5.3.7)
                str_pad($cost,2,"0",STR_PAD_LEFT), //add the cost in two digits
                $salt //add the salt
        ));
       
        //now do the actual hashing
        return crypt($password,$param);
}

function validate_pw($password, $hash){
	return crypt($password, $hash)==$hash;
}
//////////////////////////////

include("funcionesBD.php");

//recibir por POST
$formulario = json_decode(file_get_contents("php://input"));
/*
class User{
	var $usuario;
	var $clave;
}

$fields = new User();

$fields->usuario = $_GET[usuario];
$fields->clave = $_GET[clave];
*/

$SQLUsuario = "	SELECT id, CONCAT(primer_nombre, ' ', primer_apellido) AS nombre, usuario, clave
				FROM usuarios
				WHERE usuario LIKE '".$formulario->usuario."'";
insertarTablaArray_v2($usuario, $SQLUsuario, 'usuario');

//creando arreglo con results
/*$results = array();
$resultNumber = 0;
foreach ($BD->query($SQLUsuario) as $usuario) {
	$resultNumber++;
	$resultadoTemp = array();
	$resultadoTemp[id] = $usuario[id];
	$resultadoTemp[nombre] = $usuario[nombre];
	$resultadoTemp[usuario] = $usuario[usuario];
	$resultadoTemp[clave] = $usuario[clave];
	array_push($results, $resultadoTemp);
}*/

$datos = array();
$datos[id] = $usuario[usuario][0][id];
$datos[nombre] = $usuario[usuario][0][nombre];
$datos[usuario] = $usuario[usuario][0][usuario];

if( count($usuario[usuario]) == 0 ){//no existe el usuario
	$datos[status] = "ER";
	$datos[message] = "Usuario no existe!";
} else if ( strcmp($usuario[usuario][0][clave], $formulario->clave) == 0 ){ 
	$datos[status] = "OK";
	$datos[message] = "";
} else if( count($usuario[usuario]) == 1 ){
	$datos[status] = "ER";
	$datos[message] = "El usuario existe pero la clave es incorrecta";
}  else {
	$datos[status] = "ER";
	$datos[message] = "NI IDEA";
}

echo json_encode($datos);
?>