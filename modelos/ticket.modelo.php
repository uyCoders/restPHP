<?php

require_once "conexion.php";

class ModeloRegTicket{

	/*=============================================
	Valida usuario valido
	=============================================*/

	static public function index($datos){
		try {

			date_default_timezone_set("America/Mexico_City");
			$dia=date("Y-m-d");
			$fecha=date("Y-m-d H:i:s");
			$fechajuego="";
			$ip=$_SERVER['REMOTE_ADDR'];
			$valido="valido";
			$puntos=0;
			$modificado="";
			$diferencia="";
			$observaciones="";
	
			$stmt = Conexion::conectar()->prepare("INSERT INTO `tbl_regticket`(`cUser`, `cToken`, `cTicket`, `cFoto`, `cMonto`, `cTienda`, `cPuntos`, `cTiempo`, `cDia`, `cFecha`, `cIp`, `cFechaJuego`, `cMontocorregido`, `cValido`, `cModificado`, `cObservaciones`, `cDiferencia`)
		VALUES (:tcorreo, :ttoken, :tticket, :foto, :monto, :tienda, :puntos, :tiempo, :tdia, :tfecha, :tip, :fechajuego, :montoc, :valido, :modificado, :observaciones, :diferencia)");
			$stmt -> bindParam(":tcorreo", $datos['user'], PDO::PARAM_STR);
			$stmt -> bindParam(":ttoken", $datos['token'], PDO::PARAM_STR);
			$stmt -> bindParam(":tticket", $datos['ticket'], PDO::PARAM_STR);
			$stmt -> bindParam(":foto", $datos['foto'], PDO::PARAM_STR);
			$stmt -> bindParam(":monto", $datos['monto'], PDO::PARAM_STR);
			$stmt -> bindParam(":montoc", $datos['monto'], PDO::PARAM_STR);
			$stmt -> bindParam(":tienda", $datos['tienda'], PDO::PARAM_STR);
			$stmt -> bindParam(":puntos", $puntos, PDO::PARAM_INT);
			$stmt -> bindParam(":tiempo", $fechajuego, PDO::PARAM_STR);
			$stmt -> bindParam(":valido", $valido, PDO::PARAM_STR);
			$stmt -> bindParam(":modificado", $modificado, PDO::PARAM_STR);
			$stmt -> bindParam(":observaciones", $observaciones, PDO::PARAM_STR);
			$stmt -> bindParam(":diferencia", $diferencia, PDO::PARAM_STR);
		
			$stmt -> bindParam(":tdia", $dia, PDO::PARAM_STR);
			$stmt -> bindParam(":tfecha", $fecha, PDO::PARAM_STR);
			$stmt -> bindParam(":tip", $ip, PDO::PARAM_STR);
			$stmt -> bindParam(":fechajuego", $fechajuego, PDO::PARAM_STR);
			
	
			if($stmt->execute()) {
				return Conexion::conectar()->lastInsertId();
				//return "ok";
			}
	
			$stmt -> close();
			$stmt -= null;

		} catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }

	}

  /*=============================================
	Valida si el ticket ya existe
	=============================================*/

	static public function existeTicket($ticket){

		$stmt = Conexion::conectar()->prepare("SELECT nId FROM tbl_regticket WHERE cTicket=:ticket");

		$stmt -> bindParam(":ticket", $ticket, PDO::PARAM_STR);
		$stmt -> execute();

		return $stmt->fetch() ? true : false;

		$stmt -> close();
		$stmt -= null;
	}


	  /*=============================================
		Historial
		=============================================*/

	static public function listaHistorial($token, $offset = 0){

        $stmt = Conexion::conectar()->prepare("SELECT `cTicket`, `cFecha`, `cDia`, `cPuntos`, `cTiempo` FROM tbl_regticket WHERE cToken=:rtoken ORDER BY nId DESC LIMIT 200 OFFSET :offset ");


        $stmt -> bindParam(":rtoken", $token, PDO::PARAM_STR);
		$stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt -> execute();
/*******fetchall sirve para traer todas las filas*************/
        return $stmt -> fetchall(PDO::FETCH_ASSOC);

        $stmt -> close();
        $stmt -= null;
	}


	/*=============================================
	Historial suma
	=============================================*/

	static public function listaHistorialSuma($token){

        $stmt = Conexion::conectar()->prepare("SELECT SUM(`cPuntos`) puntos FROM tbl_regticket WHERE cToken=:rtoken ");
        $stmt -> bindParam(":rtoken", $token, PDO::PARAM_STR);
        $stmt -> execute();
        return $stmt -> fetch(PDO::FETCH_ASSOC);

        $stmt -> close();
        $stmt -= null;
	}


	/*=============================================
	Registroi juego
	=============================================*/

	static public function registrarJuego($datos){

        try {

			date_default_timezone_set("America/Mexico_City");
			$fecha = (string)DateTime::createFromFormat('U.u', microtime(true))->format('Y-m-d H:i:s.u');

			//trae fecha de la tabla
			$stmt = Conexion::conectar()->prepare("
                SELECT cFecha FROM tbl_tokens 
                WHERE cToken = :token AND cTicket=:ticket ORDER BY nId DESC LIMIT 1
            ");
            $stmt->bindParam(":token", $datos['token'], PDO::PARAM_STR);
			$stmt->bindParam(":ticket", $datos['ticket'], PDO::PARAM_STR);
            $stmt->execute();

            $fechaIni = $stmt->fetch(PDO::FETCH_ASSOC);

			//Diferencia de tiempo
			$tiempoDiferencia = obtenerDiferenciaFechas($fechaIni['cFecha'], $fecha);

            // actualiza ticket
            $stmt = Conexion::conectar()->prepare("
                UPDATE tbl_regticket 
                SET cPuntos = :puntos,
                    cTiempo = :tiempo,
                    cFechaJuego = :fecha,
					cDiferencia = :diferencia
                WHERE cToken = :token AND nId = :ticket_id LIMIT 1
            ");

            $stmt->bindParam(":puntos", $datos['puntos'], PDO::PARAM_INT);
            $stmt->bindParam(":tiempo", $datos['tiempo'], PDO::PARAM_STR);
			$stmt->bindParam(":token", $datos['token'], PDO::PARAM_STR);
			$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
			$stmt->bindParam(":diferencia", $tiempoDiferencia, PDO::PARAM_STR);
            $stmt->bindParam(":ticket_id", $datos['id'], PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
	}


	//valida si existe un token
    static public function validarTicket($token, $ticket) {
        $stmt = Conexion::conectar()->prepare("
            SELECT nId 
            FROM tbl_regticket 
            WHERE cToken = :token AND cTicket =:ticket AND cTiempo =''
            LIMIT 1
        ");
        
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
		$stmt->bindParam(":ticket", $ticket, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }




	/*=============================================
	Registro token juego
	=============================================*/

	static public function nuevoTokenPart($datos){

        try {

			date_default_timezone_set("America/Mexico_City");
			$dia=date("Y-m-d");
			$fecha = (string)DateTime::createFromFormat('U.u', microtime(true))->format('Y-m-d H:i:s.u');

			

            $tokenPart = md5($datos['ticket'].$fecha);
			$stmt = Conexion::conectar()->prepare("INSERT INTO `tbl_tokens` (`cTicket`, `cToken`, `cFecha`)
			VALUES (:ticket, :ttoken, :tfecha)");
			$stmt -> bindParam(":ticket", $datos['ticket'], PDO::PARAM_STR);
			$stmt -> bindParam(":ttoken", $datos['token'], PDO::PARAM_STR);
			$stmt -> bindParam(":tfecha", $fecha, PDO::PARAM_STR);

			if($stmt -> execute()){
				return array("tokenpart" => $tokenPart);
			}else{
				print_r(Conexion::conectar()->errorInfo());
			}

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
	}




}
