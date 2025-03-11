<?php
error_reporting(0);
session_start();
require_once "Errores/log.php";

$_SERVER = array(
    //"SERVER_NAME" => "localhost",
    "SERVER_NAME" => "websocket.sgi.com.co",
    "SERVER_PORT" => 80
);

require_once "Config/configurar.php";
require_once "Config/configurar.user.php";
require_once "Modelo/Conexion_Model.php";
require_once "Libreria/composer/vendor/autoload.php";
require_once "Libreria/Controlador.php";

$isHttp = true;
if (isset($argv)) {
    unset($argv[0]);
    $_GET["url"] = implode("/", $argv);
    require_once "Libreria/Core.WebSocket.php";
    $isHttp = false;
} else {
    require_once "Libreria/Core.php";
    if (!key_exists("redirect", $_SESSION)) {
        $_SESSION["redirect"] = true;
        error(404);
    } else {
        unset($_SESSION["redirect"]);
    }
}

if (!$isHttp) {
    //Generamos el token
    if (file_put_contents(FILE_TOKEN_WEBSOCKET, encriptarByEndPoint(bin2hex(random_bytes(32)))) === false) {
        handler(E_ERROR, "No se pudo generar el token de conexiones.", __FILE__, __LINE__);
        echo "Error al generar token";
        die();
    }
}
$Core = new Core;
