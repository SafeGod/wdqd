<?php

use Ratchet\ConnectionInterface;
/**
 * Clase encargada de gestionar las vistas del modulo de los errores
 */
class ErrorControl extends Controlador
{
    protected $folder = "error";

    
    public function onOpen(ConnectionInterface $conn){}

    public function onClose(ConnectionInterface $conn){}

    public function onError(ConnectionInterface $conn, Exception $e){}


    public function onMessage(ConnectionInterface $from, $msg){}
}
 ?>