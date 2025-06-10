<?php
use SendGrid\Mail\Mail;
use Google\Cloud\RecaptchaEnterprise\V1\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;



class ControladorRegistro{
    /*=============================================
    Valida usuario
    =============================================*/
    public function registroUsr($datos){

        try {

            //valida recaptcha

            $token = $datos['recaptcha'];
            $action = 'login';
            $recaptchaKey = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXX'; // Tu clave de sitio de reCAPTCHA
            $project = 'XXXXXXXXXXX'; // Tu ID de proyecto en Google Cloud

            $client = new RecaptchaEnterpriseServiceClient();
            $projectName = $client->projectName($project);

            // Establece las propiedades del evento para realizar un seguimiento.
            $event = (new Event())->setSiteKey($recaptchaKey)->setToken($token);

            // Crea la solicitud de evaluación.
            $assessment = (new Assessment())->setEvent($event);

            try {
                $response = $client->createAssessment($projectName, $assessment);

                if ($response->getTokenProperties()->getValid() == false) {
                    return errorMsg(InvalidReason::name($response->getTokenProperties()->getInvalidReason()));
                }

                $score = $response->getRiskAnalysis()->getScore();
                
                if ($score < 0.5) {
                    return errorMsg("Error de recaptcha, intentalo denuevo mas tarde.");
                }


            } catch (Exception $e) {
                return errorMsg($e->getMessage());
            }          

            //valida nombres
            if(isset($datos["nombre"]) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9 ]+$/', $datos["nombre"])){
                return errorMsg("Error en el campo nombre, sólo se permiten letras.");
            }

            if(isset($datos["apellido"]) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9 ]+$/', $datos["apellido"])){
                return errorMsg("Error en el campo apellidos, sólo se permiten letras.");
            }

            //valida direccion
            if(isset($datos["direccion"]) && !preg_match('/^[a-zA-Z0-9 ñáéíóúÑÁÉÍÓÚ.,#-]+$/', $datos["direccion"])){
                return errorMsg("Error en el campo dirección, sólo se permiten caracteres válidos para este campo.");
            }

            //valida estado
            if(isset($datos["estado"]) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $datos["estado"])){
                return errorMsg("Error en el campo estado, sólo se permiten letras.");
            }

            // Validar código postal
            if (isset($datos["cpregistro"]) && !preg_match('/^\d{5}$/', $datos["cpregistro"])){
                return errorMsg("Error en el campo código postal, debe contener exactamente 5 números.");
            }
            
            //valida correo
            if(isset($datos["correo"]) && !preg_match('/^[a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/', $datos["correo"])){
                return errorMsg("Error en el campo correo, coloca un correo válido.");
            }


            // Password strength validation
            if (strlen($datos["password"]) < 8 || 
                !preg_match('/[A-Z]/', $datos["password"]) || 
                !preg_match('/[a-z]/', $datos["password"]) || 
                !preg_match('/[0-9]/', $datos["password"]) ||
                !preg_match('/[\W_]/', $datos["password"]) ) {  // Al menos un carácter especial
                return errorMsg("La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.");
            }

            //valida telefono
            if(isset($datos["telefono"]) && !preg_match('/^[0-9]+$/', $datos["telefono"])){
                return errorMsg("Error en el campo teléfono, sólo se permiten números.");
            }

            //valida telefono
            if(isset($datos["edad"]) && !preg_match('/^[0-9]+$/', $datos["edad"])){
                return errorMsg("Error en el campo edad, sólo se permiten números.");
            }
            
            if(isset($datos["tengovisa"]) && !preg_match('/^[a-zA-Z]+$/', $datos["tengovisa"])){
                return errorMsg("Error en el campo tengo visa, sólo se permiten letras.");
            }

            if(is_object($datos["pasaporte"]) || $datos['pasaporte'] =="[object Object]"){
                $datos["pasaporte"]="";
            }

             if(is_object($datos["visa"]) || $datos['visa'] =="[object Object]"){
                $datos["visa"]="";
            }


            if($datos["tengovisa"] == 'true'){
 
                if(!isset($datos["pasaporte"]) ){
                    return errorMsg("La fecha del pasaporte debe ser válida y posterior a agosto del año en curso.");
                }
    
                // visa
                if(!isset($datos["visa"]) ){
                    return errorMsg("La fecha de la visa debe ser válida y posterior a agosto del año en curso.");
                }
    
                // pasaporte
                if(isset($datos["pasaporte"]) && !$this->validateDate($datos['pasaporte'])){
                    return errorMsg("La fecha del pasaporte debe ser válida y posterior a agosto del año en curso.");
                }
    
                // visa
                if(isset($datos["visa"]) && !$this->validateDate($datos['visa'])){
                    return errorMsg("La fecha de la visa debe ser válida y posterior a agosto del año en curso.");
                }

            }else{   //[object Object]
                if ($datos["pasaporte"] !== '' && !$this->validateDate($datos["pasaporte"])) {
                    return errorMsg("La fecha del pasaporte debe ser válida y posterior a agosto del año en curso");
                }

                if ($datos["visa"] !== '' && !$this->validateDate($datos["visa"])) {
                    return errorMsg("La fecha de la visa debe ser válida y posterior a agosto del año en curso");
                }

            }

            // Verificar si el correo ya existe
            if (ModeloRegusr::existeCorreo($datos['correo'])) {
                return errorMsg("El correo ya está registrado");
            }
            
            // Guardar password original para el login
            $passwordOriginal = $datos['password'];
            
            // Hash password y generar token
            $datos['password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            $datos['token'] = md5($datos['correo']."+".$passwordOriginal);
            
            // Registrar usuario
            $registro = ModeloRegusr::index($datos);

            if ($registro) {
                // Hacer login automático
                $loginData = [
                    'correo' => $datos['correo'],
                    'password' => $passwordOriginal
                ];

                $enviado = $this->enviarCorreoRegistro($datos['correo'], $datos['token']);
                
                //$login = new ControladorLogin();
                //return $login->loginUsr($loginData);

                $json = array(
                    "success" => 200
                );
                
                echo json_encode($json, JSON_UNESCAPED_UNICODE);
                exit();
            }

            return errorMsg("Error al registrar usuario".$registro);

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return errorMsg("Error en el proceso de registro");
        }

    }


    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!($d && $d->format('Y-m-d') === $date)) {
            return false;
        }
        
        $minDate = new DateTime(date('Y') . '-08-01'); // August 1st of current year
        return $d >= $minDate;
    }


    private function enviarCorreoRegistro($correo, $token) {

        try {
            $to = $correo;

            $email = new Mail(); 
            $email->setFrom("no-reply@mail.mx", "correo");
            $email->setSubject("Registro correcto");
            $email->addTo($to);
            
            // Contenido en texto plano
            $email->addContent("text/plain", "Bienvenido al sitio.");

            // Contenido en HTML
            $htmlContent = '
            <html>
            <head>
                <title>¡Gracias por registrarte!</title>
            </head>
            <body>
                <h2>Bienvenido al sitio.</h2>
                <p>Prepárate para vivir una experiencia inolvidable.</p>    
            </body>
            </html>
            ';

            $htmlContent2 = '
            <html>
            <head>
                <title>¡Gracias por registrarte!</title>
            </head>
            <body>
                <h2>Bienvenido al sitio.</h2>
                <p>Prepárate para vivir una experiencia inolvidable.</p>
                <p>Confirma tu correo  <a href="https://dominio.mx/confirma/'.$token.'" data-saferedirecturl="https://dominio.mx/confirma/'.$token.'">aquí</a> </p>
    
            </body>
            </html>
            ';
            $email->addContent("text/html", $htmlContent2);

            $config = include('/home/config.php');
            $apiKey = $config['SENDGRID_API_KEY'];
            $sendgrid = new \SendGrid($apiKey);


            try {
                $response = $sendgrid->send($email);
                return $response->statusCode(); // Devuelve el código de respuesta HTTP (200, 202, etc.)
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







}
