<?php

require_once "conexion.php";

class ModeloRegusr{

	/*=============================================
	Valida usuario valido
	=============================================*/

	static public function index($datos){
		try {

			date_default_timezone_set("America/Mexico_City");
			$dia=date("Y-m-d");
			$fecha=date("Y-m-d H:i:s");
			$terminos="SI";
			$avisos="SI";
			$ip=$_SERVER['REMOTE_ADDR'];
			$confirma="";
			$recupera="";
			$logatt = 0;
	
	
			$stmt = Conexion::conectar()->prepare("INSERT INTO `tbl_regusr`(`cNombre`,`cApellidos`, `cDireccion`, `cEstado`, `cCorreo`, `cToken`, `cPassword`, `cTelefono`, `cEdad`, `cBases`,`cTerminos`, `cDia`, `cFecha`, `cConfirma`, `cIp`, `cPasaporte`, `cVisa`, `login_attempts`, `last_attempt`, `cTengovisa`, `cRecupera`, `cCp`)
		VALUES (:rnombre, :rapellido, :rdireccion,:restado,:rcorreo,:rtoken,:rpassword,:rtelefono,:redad,:rterminos,:ravisos,:rdia,:rfecha, :confirma,:rip, :pasaporte, :visa, :logatt, :lastatt, :tengovisa, :recupera, :cp)");
			$stmt -> bindParam(":rnombre", $datos['nombre'], PDO::PARAM_STR);
			$stmt -> bindParam(":rapellido", $datos['apellido'], PDO::PARAM_STR);
			$stmt -> bindParam(":rdireccion", $datos['direccion'], PDO::PARAM_STR);
			$stmt -> bindParam(":restado", $datos['estado'], PDO::PARAM_STR);
			$stmt -> bindParam(":rcorreo", $datos['correo'], PDO::PARAM_STR);
			$stmt -> bindParam(":rtoken", $datos['token'], PDO::PARAM_STR);
			$stmt -> bindParam(":rpassword", $datos['password'], PDO::PARAM_STR);
			$stmt -> bindParam(":rtelefono", $datos['telefono'], PDO::PARAM_STR);
			$stmt -> bindParam(":tengovisa", $datos['tengovisa'], PDO::PARAM_STR);
			$stmt -> bindParam(":redad", $datos['edad'], PDO::PARAM_STR);
			$stmt -> bindParam(":pasaporte", $datos['pasaporte'], PDO::PARAM_STR);
			$stmt -> bindParam(":visa", $datos['visa'], PDO::PARAM_STR);
			$stmt -> bindParam(":cp", $datos['cpregistro'], PDO::PARAM_STR);
			$stmt -> bindParam(":rterminos", $terminos, PDO::PARAM_STR);
			$stmt -> bindParam(":ravisos", $avisos, PDO::PARAM_STR);
			$stmt -> bindParam(":rdia", $dia, PDO::PARAM_STR);
			$stmt -> bindParam(":rfecha", $fecha, PDO::PARAM_STR);
			$stmt -> bindParam(":rip", $ip, PDO::PARAM_STR);
			$stmt -> bindParam(":confirma", $confirma, PDO::PARAM_STR);
			$stmt -> bindParam(":lastatt", $confirma, PDO::PARAM_STR);
			$stmt -> bindParam(":logatt", $logatt, PDO::PARAM_INT);
			$stmt -> bindParam(":recupera", $recupera, PDO::PARAM_STR);
	
			//$stmt -> execute();
			//$stmt->debugDumpParams();
	
			return $stmt->execute();
	
			$stmt -> close();
			$stmt -= null;

		} catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }


	}

  /*=============================================
	Valida si el usuario ya existe
	=============================================*/

	static public function existeCorreo($correo) {
        $stmt = Conexion::conectar()->prepare("
            SELECT cCorreo 
            FROM tbl_regusr 
            WHERE cCorreo = :correo
        ");
        
        $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch() ? true : false;

		$stmt -> close();
		$stmt -= null;
    }


}
