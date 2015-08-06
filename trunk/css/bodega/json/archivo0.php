<?php
//ESTE ARCHIVO PERMITE GUARDAR EDITAR Y ELIMINAR ARCHIVOS POR FUERA DE LA CARPETA DE BODEGA

////
$data = "archivo.php........";
$file = "data.html";
$fp = fopen($file, "a") or die("Couldn't open $file for login!");
fwrite($fp, $data) or die("Couldn't open new page!");
fclose($fp);
////


echo "-".($_GET['modelo']."-".$_GET['campo']."-.".$_GET['id'])."-";
$data .= "<br />-----------------archivO.php------------------------";
$data .= "<br />" . date('m/d/Y h:i:s a', time()) . " ";
$accionGET = $_GET['accion'];
$data .= $accionGET;


$datos = $_POST['myObj']; // decode JSON to associative array
$Json = str_replace('\\',"",$datos);
$objectJson = json_decode($Json);

$modelo = $objectJson->modelo;
$campo = $objectJson->campo;
$id = $objectJson->id;
$accion = $objectJson->accion;

$data .= "-------------00000-------------(".$accion.")Modelo: ".$modelo.",Campo: ".$campo.",ID: ".$id."<br/>";

if ( strcmp("crear", $accion) === 0 || strcmp("crear", $accionGET) === 0) {

  $data .= "<br /><br />Creando...";

  $estructura = '../../files';
  //creando carpeta file
  if(!mkdir($estructura, 0777, true)){ 
    $data .= "ERROR: Fallo al crear carpetas...<br/>";
  } else 
    $data .= "!! Carpeta creada...<br/>";

  chmod($estructura, 0777);

  //creando carpeta sino existe
  $estructura .= '/'.$modelo;

  if(!mkdir($estructura, 0777, true))
  { 
    $data .= "ERROR: Fallo al crear carpetas...<br/>";
  } else 
    $data .= "!! Carpeta creada...<br/>";

  chmod($estructura, 0777);

  $temp = explode(".", $_FILES["file"]["name"]);

  if ($_FILES["file"]["error"] > 0) {
      $data .= "Return Code: " . $estructura . $_FILES["file"]["error"] . "<br>";
    } else {
      $type = split("/", $_FILES["file"]["type"]);
      $extension = $type[1];
      $data .= "Upload: " . $estructura . "/"  . $campo . "." . $id . "." . $extension . "<br>";
      $data .= "Type: " . $_FILES["file"]["type"] . "(".$extension.")<br>";
      $data .= "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
      $data .= "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";

      if (file_exists($estructura . "/" . $_FILES["file"]["name"]))
        {
          $data .= $_FILES["file"]["name"] . " already exists. ";
        }
      else
        {
          move_uploaded_file($_FILES["file"]["tmp_name"], $estructura . "/" . $campo . "." . $id . "." . $extension);
          $data .= "Stored in: " . $estructura . "/" . $campo . "." . $id . "." . $extension;
        }
    }

} else if (strcmp("eliminar", $accion) === 0 || strcmp("eliminar", $accionGET) === 0) {
  $data .= "[][]eliminando...[][]";
  
  //buscar archivo en listado
  $url='../../files/'.$_GET['modelo'].'/';
  $dir = opendir($url);

  $archivoAEliminar = $_GET['campo'].".".$_GET['id'];

  //List files in images directory
  while (($file = readdir($dir)) !== false)
  {
    if ( strpos($file, $archivoAEliminar) === 0 ){
      //echo "-(".strpos($file, $archivoAEliminar).")-filename: " . $file . "{".$archivoAEliminar."}<br />";
      unlink("../../files/".$_GET['modelo'].'/'.$file);
    }
  }
  closedir($dir);

} else if (strcmp("editar", $accion) === 0 || strcmp("editar", $accionGET) === 0 ) {

  $data .= "EDITANDO";
  //ELIMINANDO
  $data .= "[][]editando...[][]";
  
  //buscar archivo en listado
  $url='../../files/'.$modelo.'/';
  $dir = opendir($url);

  $archivoAEliminar = $campo.".".$id;

  //List files in images directory
  while (($file = readdir($dir)) !== false)
  {
    if ( strpos($file, $archivoAEliminar) === 0 ){
      $data .= "-(".strpos($file, $archivoAEliminar).")-filename: " . $file . "{".$archivoAEliminar."}<br />";
      unlink("../../files/".$modelo.'/'.$file);
    }
    
  }
  closedir($dir);

  //CREANDO
  $data .= "<br /><br />Creando...";

  $estructura = '../../files';
  //creando carpeta file
  if(!mkdir($estructura, 0777, true))
  { 
    $data .= "ERROR: Fallo al crear carpetas...<br/>";
  } else 
    $data .= "!! Carpeta creada...<br/>";

  chmod($estructura, 0777);

  //creando carpeta sino existe
  $estructura .= '/'.$modelo;

  if(!mkdir($estructura, 0777, true))
  { 
    $data .= "ERROR: Fallo al crear carpetas...<br/>";
  } else 
    $data .= "!! Carpeta creada...<br/>";

  chmod($estructura, 0777);

  $temp = explode(".", $_FILES["file"]["name"]);

  if ($_FILES["file"]["error"] > 0) {
      $data .= "Return Code: " . $estructura . $_FILES["file"]["error"] . "<br>";
    } else {
      $type = split("/", $_FILES["file"]["type"]);
      $extension = $type[1];
      $data .= "Upload: " . $estructura . "/"  . $campo . "." . $id . "." . $extension . "<br>";
      $data .= "Type: " . $_FILES["file"]["type"] . "(".$extension.")<br>";
      $data .= "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
      $data .= "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";

      if (file_exists($estructura . "/" . $_FILES["file"]["name"]))
        {
          $data .= $_FILES["file"]["name"] . " already exists. ";
        } else {
          move_uploaded_file($_FILES["file"]["tmp_name"], $estructura . "/" . $campo . "." . $id . "." . $extension);
          $data .= "Stored in: " . $estructura . "/" . $campo . "." . $id . "." . $extension;
        }
    }
}

$file = "data.html";
$fp = fopen($file, "a") or die("Couldn't open $file for login!");
fwrite($fp, $data) or die("Couldn't open new page!");
fclose($fp);

echo "salida archivo.php accion: ".$accionGET;
?>
