<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'");

// Configuracion y dependencias
require_once "config/config.php";
require_once "vendor/autoload.php";
require_once "middleware/RateLimit.php";

// Controllers
require_once "controladores/login.controlador.php";
require_once "controladores/registro.controlador.php";
require_once "controladores/ticket.controlador.php";
require_once "controladores/rutas.controlador.php";

// Models
require_once "modelos/login.modelo.php";
require_once "modelos/registro.modelo.php";
require_once "modelos/ticket.modelo.php";

if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = rtrim($_SERVER['HTTP_ORIGIN'], '/'); // Eliminar slash final si lo tiene
    
    if (in_array($origin, ALLOWED_ORIGINS, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    } else {
        // Si la petición es de un origen no permitido, devolver un error
        http_response_code(403);
        die('Acceso denegado origin');
    }
}

//genera token para cualquier solicitud 
$arrayRutas = explode("/", $_SERVER['REQUEST_URI']);
if(count(array_filter($arrayRutas)) == 2 && $_SERVER["REQUEST_METHOD"] == "GET"){
    /******SERVICIO REGISTRO USUARIO************/
    if(array_filter($arrayRutas)[2] == "csrf-token"){

        // Generar token CSRF si no existe
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        header('Content-Type: application/json');
        echo json_encode(["csrf_token" => $_SESSION['csrf_token']]);
        exit;
    }
}

/*********************CSRF***************** */
// Obtener el token del encabezado HTTP
$headers = getallheaders();
$csrf_token = $headers['X-Csrf-Token'] ?? '';

// Validar CSRF Token
if (!isset($_SESSION['csrf_token']) || strval($_SESSION['csrf_token']) !== strval($csrf_token)) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(["error" => "Solicitud inválida"]);
    exit;
}

//encabezados HTTP conflictivos
if (!empty($_SERVER['HTTP_TRANSFER_ENCODING']) && !empty($_SERVER['HTTP_CONTENT_LENGTH'])) {
    http_response_code(400);
    die(json_encode(["error" => "Solicitud inválida"]));
}



/***********************HEADER JWT********************* */
if (!isset($headers['Authorization'])) {
    http_response_code(400);
    die(json_encode(["error" => "Solicitud inválida basic"]));
}

if (preg_match('/Basic\s+(\S+)/', $headers['Authorization'], $matches)) {
    $decodedCredentials = base64_decode($matches[1]);
    list($authType, $jwt) = explode(':', $decodedCredentials, 2);

    // Validar que el esquema sea 'jwt'
    if ($authType == 'jwt') {
        $jwtValidation = validateJWT($jwt);
        if (!$jwtValidation['valid']) {
            http_response_code(401);
            echo json_encode(["error" => $jwtValidation['message']]);
            exit;
        }
    }
}



// Manejo de preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validar la cabecera Referer como una medida adicional
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $allowedHosts = ['localhost:4200', 'localhost'];

    if (!in_array($referer, $allowedHosts, true)) {
        http_response_code(403);
        die('Acceso denegado');
    }
}


// Initialize router
$rutas = new ControladorRutas();
$rutas->index();