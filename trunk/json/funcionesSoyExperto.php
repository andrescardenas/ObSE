<?php

function rutasCompletasImagen( $datos, $ruta, $archivo ){
    foreach ($datos as $key => $value) {
        //cambiar nombre de archivo imagen para mostrar
          $explotExtension = explode("/", $datos[$key][fi_type_.$archivo]); // Image/jpeg
          $extension = $explotExtension[count($explotExtension)-1]; // {Image, jpeg}
          if ( count($explotExtension) > 1 ) {
            $nombre = $ruta.$datos[$key][id].".".$extension;
            $datos[$key][$archivo] = $nombre;
          } else{
            $datos[$key][$archivo] = NULL;
          }

          unset($datos[$key][fi_name_.$archivo]);
          unset($datos[$key][fi_type_.$archivo]);
          unset($datos[$key][fi_size_.$archivo]);

    } 
    return $datos;
}