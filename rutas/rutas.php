<?php


class Router {
    private $routes = [];
    private $vigencia;

    public function __construct() {
        $this->checkVigencia();
    }

    private function checkVigencia() {
        date_default_timezone_set('America/Mexico_City');
        $hoy = date("Y-m-d");
        $inicioPromo = "2025-02-01";
        $finPromo = "2025-12-31";
        
        $this->vigencia = $this->checkInRange($inicioPromo, $finPromo, $hoy);
    }

    private function checkInRange($start_date, $end_date, $evaluame) {
        return (strtotime($evaluame) >= strtotime($start_date)) && 
               (strtotime($evaluame) <= strtotime($end_date));
    }

    public function handleRequest() {
        if (!$this->vigencia) {
            return errorMsg("Vigencia del 01 de enero al 31 de diciembre de 2025.");
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = array_filter(explode('/', $uri));
        
        // Validate route structure
        if (count($segments) != 2) {
            return errorMsg("No encontrado.");
        }

        $endpoint = end($segments);

        switch ($endpoint) {
            case 'login':
                $this->handleLogin();
                break;
            case 'registro':
                $this->handleRegistro();
                break;
            case 'regticket':
                $this->handleRegistroTicket();
                break;
            case 'regJuego':
                $this->handleRegistroJuego();
                break;
            case 'historial':
                $this->handleRegistroHistorial();
                break;
            case 'codigoRecupera':
                $this->handleCodigoRecupera();
                break;
            case 'recupera':
                $this->handleRecupera();
                break;
            case 'confirma':
                $this->handleConfirma();
                break;
            case 'tokenParticipa':
                $this->handleTokenParticipa();
                break;
            default:
                errorMsg("Servicio no encontrado.");
        }
    }

    /*LOGIN*/
    private function handleLogin() {
        $data = json_decode(file_get_contents('php://input'), true);


        if (!isset($data["correo"]) || !isset($data["password"])) {
            return errorMsg("Falta información.");
        }

        $login = new ControladorLogin();
        $login->loginUsr($data);
    }

    /* REGISTRO */
    private function handleRegistro() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["nombre"]) || !isset($data["apellido"]) || !isset($data["direccion"]) || !isset($data["estado"]) || !isset($data["cpregistro"])
        || !isset($data["correo"]) || !isset($data["password"]) || !isset($data["telefono"]) || !isset($data["edad"]) || !isset($data["tengovisa"]) || !isset($data["recaptcha"]) ){
            return errorMsg("Falta información.");
        }

        $login = new ControladorRegistro();
        $login->registroUsr($data);
    }

    /* REGISTRO TICKET */
    private function handleRegistroTicket() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["tokenw"]) || !isset($data["ticket"]) || !isset($data["monto"]) || !isset($data["tienda"]) || !isset($data["foto"]) ){
            return errorMsg("Falta información.");
        }

        $regTicket = new ControladorTicket();
        $regTicket->registroTicket($data);
    }

    /* REGISTRO JUEGO */
    private function handleRegistroJuego() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["tokenw"]) || !isset($data["tiempo"]) || !isset($data["puntos"]) || !isset($data["ticket"]) || !isset($data["tokenjuego"]) ){
            return errorMsg("Falta información.");
        }

        $regTicket = new ControladorTicket();
        $regTicket->registroJuego($data);
    }

    /* HISTORIAL */
    private function handleRegistroHistorial() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["tokenw"]) ){
            return errorMsg("Falta información.");
        }

        $regTicket = new ControladorTicket();
        $regTicket->historial($data);
    }

    /* CODIGO RECUPERA */
    private function handleCodigoRecupera() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["correo"]) ){
            return errorMsg("Falta información.");
        }

        $regTicket = new ControladorLogin();
        $regTicket->codigoRecupera($data);
    }

    /* RECUPERA */
    private function handleRecupera() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["codigo"]) || !isset($data["password"])){
            return errorMsg("Falta información.");
        }

        $regTicket = new ControladorLogin();
        $regTicket->nuevaContrasena($data);
    }

    /* CONFIRMA CUENTA */
    private function handleConfirma() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["token"]) ){
            return errorMsg("Falta información.");
        }

        $regTicket = new ControladorLogin();
        $regTicket->confirmaCuenta($data);
    }

    /* CODIGO PARTICIPA */
    private function handleTokenParticipa() {
        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data["tokenw"]) || !isset($data["ticket"])){
            return errorMsg("Falta información.");
        }

        $regTicket = new ControladorTicket();
        $regTicket->tokenParticipa($data);
    }

}

// Initialize and handle the request
$router = new Router();
$router->handleRequest();


