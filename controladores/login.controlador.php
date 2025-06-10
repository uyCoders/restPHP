<?php
use Firebase\JWT\JWT;
use SendGrid\Mail\Mail;


class ControladorLogin {

    
    public function loginUsr($datos) {
        try {
            // Input sanitization
            $correo = filter_var($datos["correo"], FILTER_SANITIZE_EMAIL);
            $password = htmlspecialchars($datos["password"], ENT_QUOTES, 'UTF-8');

            // Validate email format
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return errorMsg("Error en el campo correo, coloca un correo válido.");
            }

            // Password strength validation
            if (strlen($password) < 8 || 
                !preg_match('/[A-Z]/', $password) || 
                !preg_match('/[a-z]/', $password) || 
                !preg_match('/[0-9]/', $password)) {
                return errorMsg("La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.");
            }

            // Check for SQL injection patterns
            if (preg_match('/[\'";\-]/', $password)) {
                return errorMsg("Caracteres no permitidos en la contraseña.");
            }

            // Rate limiting check
            //RateLimit::check();

            // Attempt login
            $existeUsr = ModeloLoginusr::existeUser($correo, $password);
     

            //valida usuario por fecha y activo
            $fechaCorte = new DateTime("2025-03-07"); // Fecha base para validación
            $fechaRegistro = new DateTime($existeUsr['cDia']);   
            
            
            if ($existeUsr) {

                // usuario valido pero no confirmado
                if ($fechaRegistro >= $fechaCorte && $existeUsr['cConfirma'] == "") {
                    return errorMsg("Por favor, confirma tu correo electrónico para activar tu participación.");
                }
                
                // Generate JWT token
                $token = $this->generateJWT($existeUsr['cToken']);
                
                // Log successful login
                //$this->logLogin($correo, true);

                $json = array(
                    "success" => 200,
                    "jwtoken" => $token
                );
                
                echo json_encode($json, JSON_UNESCAPED_UNICODE);
                exit();
            }

            //$hash = password_hash($password, PASSWORD_DEFAULT);
            // Log failed login attempt
            //$this->logLogin($correo, false);
            
            return errorMsg("Credenciales inválidas.");

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return errorMsg("Error en el proceso de login, llegaste al límite de intentos.");
        }
    }

    

    private function generateJWT($userToken) {
        $payload = [
            'token' => $userToken,
            'iat' => time(),
            'exp' => time() + (60 * 60) // 1 hour expiration
        ];
        
        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    private function logLogin($correo, $success) {
        // Definir la estructura de directorios
        $baseLogDir = __DIR__ . '/../logs';
        $authLogDir = $baseLogDir . '/auth';
        
        // Crear directorios si no existen
        if (!file_exists($baseLogDir)) {
            mkdir($baseLogDir, 0755, true);
        }
        if (!file_exists($authLogDir)) {
            mkdir($authLogDir, 0755, true);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "$timestamp | $ip | $correo | " . ($success ? 'SUCCESS' : 'FAILED') . "\n";
        
        // Guardar en el nuevo directorio
        file_put_contents($authLogDir . '/login.log', $logEntry, FILE_APPEND);
    }



    public function codigoRecupera($datos){
        try {
            // Validate email
            if (!isset($datos['correo']) || !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
                return errorMsg("Correo inválido");
            }

            //valida si existe el correo
            if (!ModeloRegusr::existeCorreo($datos['correo'])) {
                return errorMsg("No se encontró el correo.");
            }

            // Generate new password
            $newCodigo = $this->generateRandomPassword();
            
            // Update password in database
            $codigo = ModeloLoginusr::actualizarCodigo([
                'correo' => $datos['correo'],
                'codigo' => $newCodigo
            ]);


            if ($codigo) {
                // Send email
                $enviado = $this->enviarCorreo($datos['correo'], $codigo);
                
                $error= "";
                //archivo seguro claves
                
                if ($enviado<400) {

                    $json = array(
                        "success" => 200,
                        "message" => "Se ha enviado un correo con un código para recuperar tu contraseña."
                    );
                    echo json_encode($json, JSON_UNESCAPED_UNICODE);
                    exit();

                }else{
                    return errorMsg("Error al enviar el correo, intentalo mas tarde.".$enviado);
                }
            }

            return errorMsg("No se encontró el correo registrado.");

        } catch (Exception $e) {
            error_log("Password recovery error: " . $e->getMessage());
            return errorMsg("Error en el proceso de recuperación");
        }
        
    }

    private function generateRandomPassword() {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
    }
    
    private function enviarCorreo($correo, $newCodigo) {
        try {

            

            $to = $correo;

            $email = new Mail(); 
            $email->setFrom("no-reply@mail.mx", "Correo");
            $email->setSubject("Código para recuperación de contraseña");
            $email->addTo($to);
            
            // Contenido en texto plano
            $email->addContent("text/plain", "Tu nuevo código es: $newCodigo. Ingresa este código en la sección de login.");

            // Contenido en HTML
            $htmlContent = "
            <html>
            <head>
                <title>Nueva Código</title>
            </head>
            <body>
                <h2>Recuperación de contraseña</h2>
                <p>Tu nuevo código es: <strong>$newCodigo</strong></p>
                <p>Ingresa este código en la sección de login.</p>
            </body>
            </html>
            ";
            $email->addContent("text/html", $htmlContent);



            $config = include('/home/config.php');
            $apiKey = $config['SENDGRID_API_KEY'];
            $sendgrid = new \SendGrid($apiKey);


            try {
                $response = $sendgrid->send($email);

                return $response->statusCode(); // Devuelve el código de respuesta HTTP (200, 202, etc.)
                //return $apiKey;
            } catch (Exception $e) {
                echo 'Error al enviar el correo: '. $e->getMessage();
                return false;
            }

            


        } catch (Exception $e) {
            // Capturar y mostrar errores
            error_log("Error al enviar correo: " . $e->getMessage());
            return false;
        }
    }



    public function nuevaContrasena($datos){
        try {
            // Validate codigo
            if(isset($datos["codigo"]) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9]{8}$/', $datos["codigo"])){
                return errorMsg("Error en el código de recuperacion.");
            }

            //verifica si existe el codigo
            if (!ModeloLoginusr::existeCodigo($datos['codigo'])) {
                return errorMsg("No se encontró el código.");
            }


            //valida password
            $password = htmlspecialchars($datos["password"], ENT_QUOTES, 'UTF-8');

            // Password strength validation
            if (strlen($password) < 8 || 
                !preg_match('/[A-Z]/', $password) || 
                !preg_match('/[a-z]/', $password) || 
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[\W_]/', $password) ) {  // Al menos un carácter especial
                return errorMsg("La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.");
                
            }

            // Check for SQL injection patterns
            if (preg_match('/[\'";\-]/', $password)) {
                return errorMsg("Caracteres no permitidos en la contraseña.");
            }

            // Guardar password original para el login
            $passwordOriginal = $password;
            
            // Hash password y generar token
            $datos['password'] = password_hash($password, PASSWORD_DEFAULT);

            

            // Update password in database
            $resultado = ModeloLoginusr::actualizarPassword([
                'codigo' => $datos['codigo'],
                'password' => $datos['password']
            ]);

            if ($resultado) {

                    $json = array(
                        "success" => 200,
                        "message" => "Contraseña actualizada con éxito.",
                    );
                    
                    echo json_encode($json, JSON_UNESCAPED_UNICODE);
                    exit();

            }

            return errorMsg("No se encontró el código o ya fue utilizado.");

        } catch (Exception $e) {
            error_log("Password recovery error: " . $e->getMessage());
            return errorMsg("Error en el proceso de recuperación");
        }
        
    }


    public function confirmaCuenta($datos){
        try {


            $usuario = ModeloLoginusr::validarToken($datos['token']);
            if (!$usuario) {
                return errorMsg("Token inválido o usuario no encontrado", 401);
            }

            $resultado = ModeloLoginusr::validarYActualizarToken($usuario['nId']);

            if ($resultado) {
                $json = array(
                    "success" => 200,
                    "message" => "Token validado exitosamente."
                );
            } else {
                return errorMsg("El token no existe o ya fue validado.");
            }

            echo json_encode($json, JSON_UNESCAPED_UNICODE);
            exit();
            


        } catch (Exception $e) {
            error_log("Password recovery error: " . $e->getMessage());
            return errorMsg("Error en el proceso de confirmacion");
        }
        
    }

}