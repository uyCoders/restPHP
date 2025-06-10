<?php
define('JWT_SECRET', 'promoMG25');
define('ALLOWED_ORIGINS', ['http://localhost:4200', 'http://localhost']);
define('MAX_LOGIN_ATTEMPTS', 10);  //5
define('LOGIN_TIMEOUT', 15 * 60); // 15 minutos
define('DB_HOST', 'localhost');

//recaptcha
define('RECAPTCHA_SECRETA', 'XXXXXXXXXXXXXXXXX');


//produccion
define('DB_NAME', 'XXXXXXXXX');
define('DB_USER', 'XXXXXXXXX');
define('DB_PASS', 'XXXXXXXXX');


// HTTP/2
function errorMsg($texto, $code = 400) {
    // Limpiar cualquier salida previa
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Establecer código de respuesta en HTTP/2
    header("HTTP/2 $code");

    // Definir el tipo de contenido como JSON
    header('Content-Type: application/json; charset=utf-8');

    // Construir la respuesta JSON
    $json = array(
        "success" => false,
        "code" => $code,
        "error_msg" => $texto,
        "timestamp" => date('Y-m-d H:i:s')
    );

    // Enviar la respuesta y detener la ejecución
    echo json_encode($json, JSON_UNESCAPED_UNICODE);
    exit();
}