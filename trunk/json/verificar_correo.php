<?php
header('Content-Type: application/json');

include_once('funcionesBD.php');

//correo es REAL??
$formulario = json_decode(file_get_contents("php://input"));
// Get remote file contents, preferring faster cURL if available
function remote_get_contents($url){
    if (function_exists('curl_get_contents') AND function_exists('curl_init'))
    {
        return curl_get_contents($url);
    }
    else
    {
        // A litte slower, but (usually) gets the job done
        return file_get_contents($url);
    }
}
function curl_get_contents($url){
    // Initiate the curl session
    $ch = curl_init();

    // Set the URL
    curl_setopt($ch, CURLOPT_URL, $url);

    // Removes the headers from the output
    curl_setopt($ch, CURLOPT_HEADER, 0);

    // Return the output instead of displaying it directly
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Execute the curl session
    $output = curl_exec($ch);

    // Close the curl session
    curl_close($ch);

    // Return the output as a variable
    return $output;
}
$username	= 'soyexperto';
$password	= 'soyexperto';
$email		= $formulario->email;
$api_url	= 'http://api.verify-email.org/api.php?';
$url		= $api_url . 'usr=' . $username . '&pwd=' . $password . '&check=' . $email;
//$object		= json_decode(remote_get_contents($url)); // the response is received in JSON format; here we use the function remote_get_contents($url) to detect in witch way to get the remote content
$verificacion[respuesta] = "ok";//("".$object->verify_status?'ok':'error');
$datos[estado] = array();
if ( $verificacion[respuesta] == 'ok' ){
    $datos[no_es_real] = false;
}else{
    $datos[no_es_real] = true;
}

//existe en base de datos
$SQLExiste = " SELECT * FROM usuarios WHERE usuario = '".$formulario->email."'";
insertarTablaArray_v2($existe, $SQLExiste, 'existe');

$datos[sql_existe] = $SQLExiste;

if ( count($existe[existe]) > 0 ){
    $datos[existe_bd] = true;
}else{
    $datos[existe_bd] = false;
}

echo json_encode($datos);