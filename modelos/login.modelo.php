<?php

require_once "conexion.php";


class ModeloLoginusr {
    static public function existeUser($correo, $password) {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT nId, cPassword, cToken, login_attempts, last_attempt, cDia, cConfirma  
                FROM tbl_regusr 
                WHERE cCorreo = :correo 
                LIMIT 1
            ");
            
            $stmt->execute([':correo' => $correo]);
            $user = $stmt->fetch();
            
            
            if (!$user) {
                return false;
            }
            
            // Check login attempts
            if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS && 
                time() - strtotime($user['last_attempt']) < LOGIN_TIMEOUT) {
                throw new Exception("Too many login attempts. Please try again later.");
            }
            
            
            //return errorMsg($resultado);
            if (password_verify($password, $user['cPassword'])) {
                // Reset login attempts on successful login
                self::resetLoginAttempts($user['nId']);
                return ['cToken' => $user['cToken'], 'cDia' => $user['cDia'], 'cConfirma' => $user['cConfirma']];
            }
            
            
            // Increment login attempts
            self::incrementLoginAttempts($user['nId']);
            return false;
            
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("An error occurred during login");
        }
    }

    //reset intentos de login
    static public function resetLoginAttempts($userId) {
        $stmt = Conexion::conectar()->prepare("
            UPDATE tbl_regusr 
            SET login_attempts = 0, 
                last_attempt = NULL 
            WHERE nId = :userId
        ");
        return $stmt->execute([':userId' => $userId]);
    }

    //registra intentos de login
    static public function incrementLoginAttempts($userId) {
        $stmt = Conexion::conectar()->prepare("
            UPDATE tbl_regusr 
            SET login_attempts = login_attempts + 1,
                last_attempt = NOW()
            WHERE nId = :userId
        ");
        return $stmt->execute([':userId' => $userId]);
    }

    //valida si existe un token
    static public function validarToken($token) {
        $stmt = Conexion::conectar()->prepare("
            SELECT nId, cCorreo 
            FROM tbl_regusr 
            WHERE cToken = :token 
            LIMIT 1
        ");
        
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    static public function actualizarCodigo($datos) {
        try {
            $db = Conexion::conectar();

            // 1️ Verificar si ya existe un código para ese correo
            $stmt = $db->prepare("
                SELECT cRecupera FROM tbl_regusr 
                WHERE cCorreo = :correo LIMIT 1
            ");
            $stmt->bindParam(":correo", $datos['correo'], PDO::PARAM_STR);
            $stmt->execute();

            $codigoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($codigoExistente && !empty($codigoExistente['cRecupera'])) {
                // Si ya existe un código, lo retornamos
                return $codigoExistente['cRecupera'];
            } else {
                // 2️ Si no existe, lo actualizamos con el nuevo código
                $stmt = $db->prepare("
                    UPDATE tbl_regusr 
                    SET cRecupera = :codigo 
                    WHERE cCorreo = :correo LIMIT 1
                ");
                $stmt->bindParam(":codigo", $datos['codigo'], PDO::PARAM_STR);
                $stmt->bindParam(":correo", $datos['correo'], PDO::PARAM_STR);

                if ($stmt->execute()) {
                    return $datos['codigo']; // Retornamos el nuevo código generado
                } else {
                    return false; // Falló la actualización
                }
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }


    
    static public function actualizarPassword($datos) {
        try {
            $stmt = Conexion::conectar()->prepare("
                UPDATE tbl_regusr 
                SET cPassword = :password,
                cRecupera = ''
                WHERE cRecupera = :codigo LIMIT 1
            ");

            $stmt->bindParam(":codigo", $datos['codigo'], PDO::PARAM_STR);
            $stmt->bindParam(":password", $datos['password'], PDO::PARAM_STR);


            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }


    /*=============================================
	Valida si el codigo de recuperacion ya existe
	=============================================*/

	static public function existeCodigo($codigo) {
        $stmt = Conexion::conectar()->prepare("
            SELECT cCorreo 
            FROM tbl_regusr 
            WHERE cRecupera=:recupera
        ");
        
        $stmt->bindParam(":recupera", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch() ? true : false;

		$stmt -> close();
		$stmt -= null;
    }


    /*=============================================
	Valida token y verifica cuenta
	=============================================*/
    static public function validarYActualizarToken($id) {
    try {
        

        // Actualizar el campo cConfirma
        $updateStmt = Conexion::conectar()->prepare("UPDATE tbl_regusr SET cConfirma = 'ok' WHERE nId = :id LIMIT 1");
        $updateStmt->bindParam(":id", $id, PDO::PARAM_INT);
        $updateStmt->execute();

        return true;
    } catch (PDOException $e) {
        error_log("Error en la base de datos: " . $e->getMessage());
        return false;
    }
}
}

