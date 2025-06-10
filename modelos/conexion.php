<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Conexion {
    private static $instance = null;
    
    private function __construct() {}
    
    public static function conectar() {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("Connection error: " . $e->getMessage());
                throw new Exception("Database connection error");
            }
        }
        return self::$instance;
    }
}




function obtenerDiferenciaFechas($fechaInicio, $fechaFin) {
    // Verificar que las variables sean cadenas de texto
    if (!is_string($fechaInicio) || !is_string($fechaFin)) {
        return "Error: Las fechas deben ser cadenas de texto.";
    }

    // Convertir las fechas en objetos DateTime con microsegundos
    $inicio = DateTime::createFromFormat('Y-m-d H:i:s.u', $fechaInicio);
    $fin = DateTime::createFromFormat('Y-m-d H:i:s.u', $fechaFin);

    // Si las fechas no tienen microsegundos, intentar con otro formato
    if (!$inicio) {
        $inicio = DateTime::createFromFormat('Y-m-d H:i:s', $fechaInicio);
    }
    if (!$fin) {
        $fin = DateTime::createFromFormat('Y-m-d H:i:s', $fechaFin);
    }

    // Verificar si la conversión fue exitosa
    if (!$inicio || !$fin) {
        return "Error: Formato de fecha inválido.";
    }

    // Obtener timestamps en segundos con microsegundos
    $segundosInicio = (float) $inicio->format('U.u');
    $segundosFin = (float) $fin->format('U.u');

    // Calcular la diferencia total en segundos
    $diferenciaSegundos = $segundosFin - $segundosInicio;

    // Calcular horas, minutos y segundos de forma precisa
    $horas = intdiv(intval(round($diferenciaSegundos)), 3600);
    $minutos = intdiv(intval(round($diferenciaSegundos)) % 3600, 60);
    $segundos = intval(round($diferenciaSegundos)) % 60;
    $decimas = round(fmod($diferenciaSegundos, 1) * 1000); // Milisegundos

    // Formatear la salida como HH:MM.SS.DDD
    return sprintf("%02d:%02d:%02d.%03d", $horas, $minutos, $segundos, $decimas * 1000);
}

//valida JWT para toda la partricipacion 

function validateJWT($jwt) {
    try {
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        
        // Verify if token is not expired
        if ($decoded->exp < time()) {
            return [
                'valid' => false,
                'message' => 'Tu sesion ya expiro.'
            ];
        }

        return [
            'valid' => true,
            'token' => $decoded->token,
            'data' => $decoded
        ];
    } catch (Exception $e) {
        return [
            'valid' => false,
            'message' => 'Sesion invalida.'
        ];
    }
}


?>
