<?php

//////////////////////////////
function generate_hash($password, $cost=11){

        $salt=substr(base64_encode(openssl_random_pseudo_bytes(17)),0,22);

        $salt=str_replace("+",".",$salt);

        $param='$'.implode('$',array(
                "2y", //select the most secure version of blowfish (>=PHP 5.3.7)
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

include("../BD.php");

$fields = json_decode(file_get_contents("php://input"));
/*
class User{
	var $correo;
	var $clave;
}

$fields = new User();

$fields->correo = $_GET[correo];
$fields->clave = $_GET[clave];
*/

$SQLusers = "SELECT u.id, u.name as name, u.user as user, u.password as password 
			FROM _users u 
			WHERE u.user = '".$fields->correo."'";

//creando arreglo con results
$results = array();
$resultNumber = 0;
foreach ($BD->query($SQLusers) as $user) {
	$resultNumber++;
	$resultadoTemp = array();
	$resultadoTemp[id] = $user[id];
	$resultadoTemp[name] = $user[name];
	$resultadoTemp[user] = $user[user];
	$resultadoTemp[password] = $user[password];
	array_push($results, $resultadoTemp);
}

$datos = array();
$datos[id] = $results[0][id];
$datos[name] = $results[0][name];
if( $resultNumber == 0 ){//no existe el usuario
	$datos[status] = "ER";
	$datos[message] = "User doesnt exist";
} else if ( strcmp($results[0][password], $fields->clave) == 0 ){ 
	$datos[status] = "OK";
	$datos[message] = "";
} else if( $resultNumber == 1 ){
	$datos[status] = "ER";
	$datos[message] = "User Exist but Password is incorrect";
}  else {
	$datos[status] = "ER";
	$datos[message] = "NO IDEA";
}

echo json_encode($datos);