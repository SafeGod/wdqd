<?php
//Configuración del usuario
date_default_timezone_set("America/Bogota");
//Configuración de acceso a la base de datos
//Local
/*define("HOST", "127.0.0.1");
define("USERNAME", "remoto");
define("PASSWORD", "123456");
define("DATABASE", "framework_test");
define("LOCALDIR", "websocket.sgi.com.co/");*/

//HOST
define("HOST", "localhost");
define("USERNAME", "remoto");
define("PASSWORD", "123456");
define("DATABASE", "framework_test");
define("LOCALDIR", "");

define("LANG", "es");
is_ssl(true);
define("CLAVE_RECAPTCHA_V3", "");

//Nombre del sitio
define("NOMBRESITIO", "");
//define("LOGO", (RUTA_URL . ""));
define("LOGO", (""));
//define("LOGOSVG", (RUTA_URL . ""));
define("LOGOSVG", (""));
define("EMAIL", "");
//Versión del sitio
define("VERSION", "0.1.4");
//Aqui puedes agregar las configuraciones de la plataforma/proyecto

define("SERVERNAME", "127.0.0.1");
define("KEY", "uudugyijfmuiakitckjwksusffidqnqf");

function encriptarByEndPoint($texto)
{
    return openssl_encrypt($texto, "AES-128-CBC", "tulesvwfrddjvhybjvlezbbxtyoafgtk", false, "1234567812345678");
}

function desencriptarByEndPoint($texto)
{
    return openssl_decrypt($texto, "AES-128-CBC", "tulesvwfrddjvhybjvlezbbxtyoafgtk", false, "1234567812345678");
}

function queryToGet($query){
    parse_str($query, $_GET);
}

define("FILE_TOKEN_WEBSOCKET", RUTA_APP . "Archivos/.hufkRwH9_WRqHq");
?>
