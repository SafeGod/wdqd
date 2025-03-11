<?php

use Ratchet\ConnectionInterface;

/**
 * Notificaciones de la plataforma Gestor
 * Port => 2001
 */
class NotifyGestorControl extends Controlador
{

    public function __construct()
    {
        parent::__construct();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        ini_set('max_execution_time', 2000);
        queryToGet($conn->httpRequest->getUri()->getQuery());
        if (!$this->validateToken($_GET["token"])) {
            $conn->close();
            return;
        }
        //Obtenemos el idUser
        $idUser = desencriptarByEndPoint($_GET["idUser"]);
        if ($idUser !== false) {
            $_POST = array(
                "resourceId" => $conn->resourceId,
                "idUsuario" => $idUser
            );
            //if ($this->modelo("Gestor/Usuario")->setResourceId()) {
            echo "Nueva Conexión: ({$conn->resourceId})\n";
            $this->clients->attach($conn, array(
                "idUserGestor" => $idUser
            ));
            /*} else {
                handler(E_ERROR, "Fallo al almacenar el resourceId del user: {$idUser}", __FILE__, __LINE__);
                $conn->close();
            }*/
        } else {
            $conn->close();
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $info = $this->clients->offsetGet($conn);
        if ($info !== false) {
            $this->clients->detach($conn);
            $_POST = array(
                "resourceId" => null,
                "idUsuario" => $info["idUserGestor"]
            );
            /*if (!$this->modelo("Gestor/Usuario")->setResourceId()) {
                handler(E_ERROR, "Fallo al eliminar el resourceId del user: {$info["idUserGestor"]}", __FILE__, __LINE__);
            }*/
            echo "Perdio conexión: ({$conn->resourceId})\n";
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        if ($this->clients->offsetExists($conn)) {
            handler(E_ERROR, "Error inesperado en la conexión: {$e->getMessage()}", __FILE__, __LINE__);
            $this->onClose($conn);
        }
    }


    public function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = json_decode($msg, true);
        switch ($msg["type"]) {
            case 'alertaSanitaria':
                $html = "Código INVIMA: {$msg["codigoInvima"]}<br>Fecha Alerta: " . getDateInSpanish($msg["fechaAlerta"]);
                $msg = json_encode(array(
                    "icon" => $msg["icon"],
                    "title" => $msg["title"],
                    "html" => $html,
                ));
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send($msg);
                    }
                }
                break;
            case 'distribucion':
                $productos = json_decode($msg["productos"], true);
                $html = "Se ha realizado una distribucion de la bodega: " . $msg["bodegaACargo"] . " a la bodega " . $msg["bodegaTraslado"] . " con los siguientes productos<br>";
                foreach ($productos as $producto) {
                    $productoGestor = $this->modelo("Gestor/Producto")->getByIdInventario($producto["index"]);
                    $html .= "<br> ◼ " . $productoGestor["producto"];
                }
                $alert = json_encode(array(
                    "icon" => $msg["icon"],
                    "title" => $msg["title"],
                    "html" => $html,
                ));
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $info = $this->clients->offsetGet($client);
                        $_POST = array(
                            "idUsuario" => $info["idUserGestor"],
                            "idBodega" => $msg["idBodegaTraslado"],
                        );
                        if ($this->modelo("Gestor/Usuario")->withAccessBodega()) {
                            $client->send($alert);
                        }
                    }
                }
                break;
            case 'existenciaMinima':
                if (key_exists("productos", $msg)) {
                    $productos = json_decode($msg["productos"], true);
                    $html = "El siguiente listado de productos tienen cantidades inferiores a la existencia mínima<br>";
                } else {
                    $_POST = array(
                        "idMovimiento" => $msg["idMovimiento"],
                        "idBodega" => $msg["idBodega"]
                    );
                    $productos = $this->modelo("Gestor/Movimiento")->getInventario();
                    $productos[0]["index"] = $productos[0]["idProducto"];
                    $html = "El siguiente producto tiene cantidad inferior a la existencia mínima<br>";
                }
                $_POST["idBodega"] = $msg["idBodega"];
                $listComprar = $this->modelo("Gestor/Producto")->generateCompra();
                if (count($listComprar) == 0) {
                    return;
                }
                $tr = 0.5;
                $withProductosMenorExistencia = false;
                foreach ($productos as $producto) {
                    $index = array_search($producto["index"], array_column($listComprar, "idProducto"));
                    if ($index === false) continue;
                    $infoProducto = $this->modelo("Gestor/Producto")->getById($producto["index"]);
                    $compra = $listComprar[$index];
                    $NmE = $compra["CPM"] * $tr; //Nivel mínimo de existencia
                    if ($compra["E"] <= $NmE) {
                        $html .= "<br> ◼ " . $infoProducto["nombre"];
                        $withProductosMenorExistencia = true;
                    }
                }
                if ($withProductosMenorExistencia) {
                    $alert = json_encode(array(
                        "icon" => $msg["icon"],
                        "title" => $msg["title"],
                        "html" => $html,
                    ));
                    foreach ($this->clients as $client) {
                        $info = $this->clients->offsetGet($client);
                        $_POST = array(
                            "idUsuario" => $info["idUserGestor"],
                            "idBodega" => $msg["idBodega"],
                        );
                        if ($this->modelo("Gestor/Usuario")->withAccessBodega()) {
                            $client->send($alert);
                        }
                    }
                }

                break;
            default:
                $from->send(
                    json_encode(array(
                        "icon" => "error",
                        "title" => "Tipo invalido",
                        "html" => "El tipo de envio para notificación es invalido"
                    ))
                );
                return;
                break;
        }
    }
}
