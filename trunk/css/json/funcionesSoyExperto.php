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

function stringToPass($password){ //https://crackstation.net/hashing-security.htm#phpsourcecode
    require_once('dessert/dessert.php');
    return create_hash($password);
}

function validatePass( $new_pass, $pass ){ //"string", "hash" -- https://crackstation.net/hashing-security.htm#phpsourcecode
    require_once('dessert/dessert.php');
    return validate_password($new_pass, $pass);
}

function aplicaPagos($id_usuario, $nivel_actual, $nivel_aplicado, $nivel_maximo, $id_usuario_genera, $monto_plan, $id_plan)
  {
    /*Asegura la salida de la recursividad por eficiencia*/
    $id_usuario_padre=calcularPadre($id_usuario);
    $id_plan_vigente = calcularIdPlanVigente($id_usuario);
    if ($nivel_actual > $nivel_maximo)
    {
      return "Pago exitoso";
    }

    /*Asegura la salida del grafo cuando llega al tope*/
    elseif ($id_usuario == $id_usuario_padre)
    {
      generarIngreso($id_usuario,$id_plan_vigente,$nivel_actual,$monto_plan,$id_usuario_genera);
      return "Pago exitoso";
    }
    /*En caso de falla se puede hacer el llamado recursivo para aplicar los pagos faltantes*/
    elseif ($nivel_actual < $nivel_aplicado)
    {
      /*Retorno agregado*/
      return aplicaPagos($id_usuario_padre, $nivel_actual + 1, $nivel_aplicado, $nivel_maximo, $id_usuario_genera, $monto_plan, $id_plan);
    }

  /*Hace el proceso inicial del pago del plan*/
  elseif ($nivel_actual == 0)
    {
     /* TODO: Niv 0: Se debe hacer:activar los pagos no acreditados en extenporaniedad, tener cuidado que se debe actualizar el dato según el plan comprado por el hijo";*/
     
     $saldo=calcularSaldoUsuario($id_usuario);
     if($id_plan_vigente == null)
     {
        if ($saldo >= $monto_plan)
        {
          $SQLActivarPlan = "INSERT INTO usuario_plan (
                                                      id,
                                                      id_usuario,
                                                      dominio,
                                                      id_plan,
                                                      bl_activo,
                                                      da_inicio,
                                                      da_final)
                                                    VALUES
                                                      (NULL,
                                                      ".$id_usuario.",
                                                       NULL,
                                                       ".$id_plan.",
                                                       '1',
                                                       NOW(),
                                                       DATE_ADD( NOW( ) , INTERVAL (select dias_recurrencia from planes where id=".$id_plan.") DAY))";
          ejecutarQuery($SQLActivarPlan);
          $id_usuario_genera = calcularIdUsuarioPlan($id_usuario);
          insertarUsuarioMovimiento($id_usuario, $id_usuario_genera, $monto_plan, null, 4, 1);
          /*Retorno agregado*/
          return aplicaPagos($id_usuario_padre, $nivel_actual + 1, $nivel_aplicado + 1, $nivel_maximo, $id_usuario_genera, $monto_plan, $id_plan);
        }
        else
        {
          return "Fondos_Insuficientes";
        }
      }
      else
      {
          echo "Id Usuario Plan: ".$id_plan_vigente;
          return "Plan_vigente";
      }
    }
    
  /*Proceso de recursividad de pagos*/
    else
    {
      generarIngreso($id_usuario,$id_plan_vigente,$nivel_actual,$monto_plan,$id_usuario_genera);
      /*Retorno agregado*/
      return aplicaPagos($id_usuario_padre, $nivel_actual + 1, $nivel_aplicado + 1, $nivel_maximo, $id_usuario_genera, $monto_plan, $id_plan);
    }
  }

function generarIngreso($id_usuario,$id_plan_vigente,$nivel_actual,$monto_plan,$plan_genera)
{
  if ($id_plan_vigente != null)
      {
        $ganancia = calcularMontoPorNivel($id_plan_vigente, $nivel_actual, $monto_plan);
        if ($ganancia != null)
        {
          insertarUsuarioMovimiento($id_usuario, $plan_genera, $ganancia, null, 2, 1);
        }
        else
        {
          /*TODO: Se debe la inserción si no tiene un plan vigente se debe buscar el max porcentaje por valor y meterlo no acreditado*/
          echo "<pre>El usuario: ".$id_usuario." con el plan :".$id_plan_vigente." no tiene porcentaje para el nivel: ".$nivel_actual."</pre>";
        }
      }
      else
      {
        /*TODO: Se debe la inserción si no tiene un plan vigente se debe buscar el max porcentaje por valor y meterlo no acreditado*/
        echo "<pre>El usuario: ".$id_usuario." no tiene plan asociado</pre>";
      }
}

function calcularPadre($id_usuario)
{
  $calculoPadre = "SELECT referido_por as vpadres from usuarios where id = ".$id_usuario;
  insertarTablaArray_v2($vpadres, $calculoPadre, 'vpadres');
  $padre = $vpadres[vpadres][0][vpadres];
  return $padre;
}

function calcularIdUsuarioPlan($id_usuario)
{
  $plan = null;
  $calculoPlan = "SELECT id as vplan FROM usuario_plan WHERE da_inicio <= date(now())  and da_final >= date(now()) and id_usuario = ".$id_usuario;
  insertarTablaArray_v2($vplan, $calculoPlan, 'vplan');
  $plan = $vplan[vplan][0][vplan];
  return $plan;
}

function calcularIdPlanVigente($id_usuario)
{
  $plan = null;
  $calculoPlan = "SELECT id_plan as vplan FROM usuario_plan WHERE da_inicio <= date(now())  and da_final >= date(now()) and id_usuario = ".$id_usuario;
  insertarTablaArray_v2($vplan, $calculoPlan, 'vplan');
  $plan = $vplan[vplan][0][vplan];
  return $plan;
}

function calcularMontoPorNivel($id_plan, $nivel, $monto)
{
  $asignoMonto = null;
  $calculoMonto = "SELECT ".$monto."*porcentaje/100 as vmonto FROM r2_planes_niveles WHERE hd_planes = ".$id_plan." and nivel = ".$nivel;
  insertarTablaArray_v2($vmonto, $calculoMonto, 'vmonto');
  $asignoMonto = $vmonto[vmonto][0][vmonto];
  return $asignoMonto;
}

function insertarUsuarioMovimiento($id_usuario, $id_usuario_plan, $monto_plan, $id_aprobacion, $id_transaccion, $acredita)
{
  $SQLPagar = "INSERT INTO usuario_plan_movimientos (
                                id,
                                id_usuario,
                                id_usuario_plan,
                                ref_pago,
                                dt_fecha,
                                id_tipo,
                                monto,
                                sl_moneda,
                                num_aprobacion,
                                bl_acreditar)
                 VALUES
                                (NULL, 
                                  ".$id_usuario.","
                                   .$id_usuario_plan.",
                                '1',
                                NOW(),"
                                .$id_transaccion.","
                                .$monto_plan.",
                                1,
                                truncate(rand()*1000,0),"
                                .$acredita.")"; /*TODO: Se debe agregar id_aprobacion en la penultima fila*/
  ejecutarQuery($SQLPagar);
}

function calcularSaldoUsuario($id_usuario)
{
  $asignoMonto = null;
  $calculoMonto = "SELECT sum(monto*signo) as vmonto
                                 FROM usuario_plan_movimientos upm
                                                 inner join tiposmovimientos tm on (upm.id_tipo=tm.id)
                                 WHERE upm.id_usuario = ".$id_usuario." AND bl_acreditar = 1";
  insertarTablaArray_v2($vmonto, $calculoMonto, 'vmonto');
  $asignoMonto = $vmonto[vmonto][0][vmonto];
  return $asignoMonto;
}

function calcularUltimoPlan($id_usuario)
{
  $asignoMonto = null;
  $calculoMonto = "SELECT id_plan, monto
                 FROM planes
                 INNER JOIN usuario_plan
                    ON ( id_plan = planes.id ) 
                 WHERE id_usuario = ".$id_usuario."
                 AND da_final = ( 
                  SELECT MAX(da_final) 
                  FROM usuario_plan
                  WHERE id_usuario = ".$id_usuario.")";

  insertarTablaArray_v2($vmonto, $calculoMonto, 'vmonto');
  return $vmonto;
}

function calcularMaximoNivelesPlan()
{
  $SQLMaximoNivel = " SELECT max(nivel) as niveles FROM r2_planes_niveles";
  insertarTablaArray_v2($niveles, $SQLMaximoNivel, 'niveles');
  $max = $niveles[niveles][0][niveles];
  return $max;
}

function listarPlanesActivosPublicos()
{
  $SQListaPlan = " SELECT id,nombre,monto,descripcion 
                    FROM planes 
                    WHERE
                    inicio_vigencia<= date(now()) 
                    and IFNULL(fin_vigencia,date(now()))>= date(now())
                    and bl_publico=1;";
  insertarTablaArray_v2($listaPlanes, $SQListaPlan, 'planes');
  return $listaPlanes;
}
