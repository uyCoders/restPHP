<?php


class ControladorTicket{
    /*=============================================
    registra ticket
    =============================================*/
    public function registroTicket($datos){

      try {
      } catch (Exception $e) {
          error_log("Ticket registration error: " . $e->getMessage());
          return errorMsg("Error en el proceso de registro de ticket");
      }
      //valida JWT
      $validaJWT = validateJWT($datos['tokenw']);
      if (!$validaJWT['valid']) {
        return errorMsg($validaJWT['message']);
      }
      
      $datos['token'] = $validaJWT['token'];

      //valida usuario
      $usuario = ModeloLoginusr::validarToken($datos['token']);
    
      if (!$usuario) {
          return errorMsg("Token inválido o usuario no encontrado", 401);
      }
      $datos['user']= $usuario['cCorreo'];

      //ticket
      if (isset($datos["ticket"]) && !preg_match('/^[A-Za-z0-9]{1,50}$/', $datos["ticket"])){
          return errorMsg("Error en el campo de ticket, sólo se permiten letras y números con un máximo de 50 caracteres.");
      }

      //foto de ticket
      if (isset($datos["foto"]) && !preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png)$/', $datos["foto"])) {
          return errorMsg("Error en el nombre de la foto del ticket.");
      }

      //monto
    if (isset($datos["monto"]) && !preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $datos["monto"])) {
        return errorMsg("Error en el campo monto, solo se permiten números con hasta 2 decimales.");
    }

    //tienda
    if (isset($datos["tienda"]) && !preg_match('/^[A-Za-z0-9 ]{3,60}$/', $datos["tienda"])) {
        return errorMsg("Error en el campo tienda, sólo se permiten letras y números, mínimo 3 y máximo 50 caracteres.");
    }

      //ticket duplicado
      if (ModeloRegTicket::existeTicket($datos['ticket'])) {
          return errorMsg("Este ticket ya fue registrado, intenta con uno diferente");
      }

      $datos['foto'] = 'https://dominio.mx/uploadFiles/'.$datos['foto'];

      $registro = ModeloRegTicket::index($datos);

      if ($registro) {
          $json = array(
              "success" => 200,
              "message" => "Ticket registrado exitosamente",
              "ticketId" => $registro
          );
          echo json_encode($json, JSON_UNESCAPED_UNICODE);
            exit();
      }

      return errorMsg("Error al registrar ticket");

    }


    /*=============================================
    Valida juego
    =============================================*/
    public function registroJuego($datos){
      try {

            //valida JWT
            $validaJWT = validateJWT($datos['tokenw']);
            if (!$validaJWT['valid']) {
            return errorMsg($validaJWT['message']);
            }
            $datos['token'] = $validaJWT['token'];

            // Validate token
            $usuario = ModeloLoginusr::validarToken($datos['token']);
            if (!$usuario) {
                return errorMsg("Token inválido o usuario no encontrado", 401);
            }

            //valida ticket
            if (isset($datos["ticket"]) && !preg_match('/^[A-Za-z0-9]{1,50}$/', $datos["ticket"])){
                return errorMsg("Error en el campo de ticket, sólo se permiten letras y números con un máximo de 50 caracteres.");
            }
            $elTicket = ModeloRegTicket::validarTicket($datos['token'], $datos['ticket']);
            if (!$elTicket) {
                return errorMsg("Ticket inválido.", 401);
            }

            // Validate points (must be integer)
            if (!isset($datos['puntos']) || !is_numeric($datos['puntos']) || $datos['puntos'] < 0) {
                return errorMsg("Formato de puntos inválido");
            }

            // Validate time format (MM:SS.DD)
            if (!isset($datos['tiempo']) || !preg_match('/^[0-5][0-9]:[0-5][0-9](\.[0-9]{1,2})?$/', $datos['tiempo'])) {
                return errorMsg("Formato de tiempo inválido.");
            }

            $tiempo = $datos['tiempo'];
            list($minutos, $segundos) = explode(':', $tiempo);

            // Extraer los décimos de segundo (si existen)
            $segundos_completos = (float) $segundos;

            // Calcular tiempo total en segundos
            $total_segundos = ($minutos * 60) + $segundos_completos;

            // Validar que el tiempo no sea menor a 1 segundo
            if ($total_segundos < 1) {
                return errorMsg("Formato de tiempo inválido.");
            }

            if(md5("XXXXXXX".$datos['puntos'].$datos['tiempo']) != $datos['tokenjuego']){
                return errorMsg("Formato de tiempos inválido.");
            }

            // Get available ticket and update
            $resultado = ModeloRegTicket::registrarJuego([
                'user' => $usuario['cCorreo'],
                'token' => $datos['token'],
                'ticket' => $datos['ticket'],
                'puntos' => (int)$datos['puntos'],
                'tiempo' => $datos['tiempo'],
                'id' => $elTicket['nId']
            ]);

            if ($resultado) {

                $json = array(
                    "success" => 200,
                    "message" => "Juego registrado exitosamente"
                );

                echo json_encode($json, JSON_UNESCAPED_UNICODE);
                exit();
            }

            return errorMsg("No hay tickets disponibles para registrar el juego".$resultado);

        } catch (Exception $e) {
            error_log("Game registration error: " . $e->getMessage());
            return errorMsg("Error en el proceso de registro de juego");
        }
    }


    /*=============================================
    Historial
    =============================================*/
    public function historial($datos){
      try {

            $validaJWT = validateJWT($datos['tokenw']);
            if (!$validaJWT['valid']) {
            return errorMsg($validaJWT['message']);
            }
            $datos['token'] = $validaJWT['token'];

            // Validate token
            $usuario = ModeloLoginusr::validarToken($datos['token']);
            if (!$usuario) {
                return errorMsg("Token inválido o usuario no encontrado", 401);
            }


            // Validate points (must be integer)
            $offset = isset($datos["offset"]) ? intval($datos["offset"]) : 0;
            if ( !is_numeric($offset) ) {
                $offset=0;
            }

            $existeHistorial  = ModeloRegTicket::listaHistorial($datos["token"], $offset);

            if ($existeHistorial) {
                $json = array(
                    "success" => 200,
                    "respuesta" => $existeHistorial
                );
                echo json_encode($json, JSON_UNESCAPED_UNICODE);
                exit();
            }

            return errorMsg("No hay un historial valido");

        } catch (Exception $e) {
            error_log("Game registration error: " . $e->getMessage());
            return errorMsg("Error en el proceso de registro de historial");
        }
    }



    /*=============================================
    nuevo token de participacion
    =============================================*/
    public function tokenParticipa($datos){
        try {

            //Buscamos al usuario que coincida con el token
            $validaJWT = validateJWT($datos['tokenw']);
            if (!$validaJWT['valid']) {
            return errorMsg($validaJWT['message']);
            }
            $datos['token'] = $validaJWT['token'];
    
            // Validate token
            $usuario = ModeloLoginusr::validarToken($datos['token']);
            if (!$usuario) {
                return errorMsg("Token inválido o usuario no encontrado", 401);
            }
    
            //genera nuevo token de participacion
            $tokenGenerado = ModeloRegTicket::nuevoTokenPart($datos);
            if($tokenGenerado){
                $json = array("success"=>200, "tokenpart"=>$tokenGenerado['tokenpart'] );
                echo json_encode($json, true);
                exit();
            }else{
                //si no regresa ningun valor
                return errorMsg("Token no generado.");
            }
            
        } catch (Exception $e) {
            error_log("token game registration error: " . $e->getMessage());
            return errorMsg("Error en el proceso de registro de juego");
        }

    }

  }


