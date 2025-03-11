<?php

use Ratchet\ConnectionInterface;

/**
 * 
 * Port => 2002
 */
class PortalControl extends Controlador
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
        echo "Conexión establecida\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        queryToGet($conn->httpRequest->getUri()->getQuery());
        if (!$this->validateToken($_GET["token"])) {
            $conn->close();
            return;
        }
        if ($_GET["type"] == "customer") {
            //Obtenemos el idCliente
            $idClientePortal = desencriptarByEndPoint($_GET["id"]);
            $idEmpresaPortal = desencriptarByEndPoint($_GET["idEmpresa"]);
            $razonSocial = desencriptarByEndPoint($_GET["razonSocial"]);
            if ($idClientePortal !== false && $razonSocial !== false && $idEmpresaPortal !== false) {
                $_POST = array(
                    "idEmpresa" => $idEmpresaPortal
                );
                $idComercial = -1;
                $comercial = $this->modelo("Gestor/Invitacion")->getByIdEmpresa();
                if ($comercial !== null) {
                    $idComercial = $comercial[0]["idComercial"];
                } else {
                    $comercial = $this->modelo("Gestor/Comercial")->disponible();
                    if (count($comercial) > 0) {
                        $idComercial = $comercial["idComercial"];
                    }
                }
                if ($idComercial !== -1) {
                    echo "Nueva Conexión Portal";
                    $this->clients->attach($conn, array(
                        "idClientePortal" => $idClientePortal,
                        "idComercial" => $idComercial,
                        "razonSocial" => $razonSocial,
                        "type" => $_GET["type"]
                    ));
                    //Validamos si el comercial esta conectado
                    foreach ($this->clients as $client) {
                        $info = $this->clients->offsetGet($client);
                        if ($info["type"] == "businessManager" && $info["idComercial"] == $idComercial) {
                            $html = "El cliente: {$razonSocial} a solicitado la creación de una cotización por primera vez";
                            $msg = json_encode(array(
                                "icon" => "info",
                                "title" => "Solicitud de cotización por primera vez",
                                "html" => $html,
                                "type" => "swal.fire"
                            ));
                            $client->send($msg);

                            $conn->send(json_encode(array(
                                "type" => "linea"
                            )));
                            $this->sendListClientesCotizacionPrimeraVez();
                            return;
                        }
                    }
                    $conn->send(json_encode(array(
                        "type" => "fueraLinea"
                    )));
                } else {
                    handler(E_ERROR, "Fallo al conectarse con el idCliente: {$idClientePortal}", __FILE__, __LINE__);
                    $conn->close();
                }
            } else {
                $conn->close();
            }
        } else if ($_GET["type"] == "businessManager") {
            $idUser = desencriptarByEndPoint($_GET["id"]);
            $idComercial = desencriptarByEndPoint($_GET["idComercial"]);
            if ($idUser !== false  && $idComercial !== false) {
                echo "Nueva Conexión Gestor";
                $this->clients->attach($conn, array(
                    "idUsuario" => $idUser,
                    "idComercial" => $idComercial,
                    "type" => $_GET["type"]
                ));
                //Validamos si el cliente esta conectado
                foreach ($this->clients as $client) {
                    $info = $this->clients->offsetGet($client);
                    if ($info["type"] == "customer" && $info["idComercial"] == $idComercial) {
                        $client->send(json_encode(array(
                            "type" => "linea"
                        )));
                    }
                }
                $this->sendListClientesCotizacionPrimeraVez();
            } else {
                $conn->close();
            }
        } else {
            $conn->close();
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $info = $this->clients->offsetGet($conn);
        if ($info !== false) {
            $this->clients->detach($conn);
            if ($info["type"] == "businessManager") {
                foreach ($this->clients as $client) {
                    $infoClient = $this->clients->offsetGet($client);
                    if ($infoClient["type"] == "customer" && $infoClient["idComercial"] == $info["idComercial"]) {
                        $client->send(json_encode(array(
                            "type" => "fueraLinea"
                        )));
                    }
                }
            } else if ($info["type"] == "customer") {
                $this->sendListClientesCotizacionPrimeraVez();
            }
            echo "Perdio conexión: ({$conn->resourceId})\n";
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
    }


    public function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = json_decode($msg, true);
        switch ($msg["type"]) {
            case 'activarConectar':
                $info = $this->clients->offsetGet($from);
                foreach ($this->clients as $client) {
                    $infoClient = $this->clients->offsetGet($client);
                    if ($infoClient["type"] == "customer") {
                        if ($infoClient["idClientePortal"] == $msg["idCliente"]) {
                            if ($info["idComercial"] == $infoClient["idComercial"]) {
                                //Le habilitamos la creación de la cotización al cliente
                                $client->send(json_encode(array(
                                    "type" => "activarCotizacion"
                                )));
                                break;
                            } else {
                                $msg = json_encode(array(
                                    "icon" => "info",
                                    "title" => "Error al obtener la conexión del cliente",
                                    "html" => "No estas autorizado en realizar la conexión del cliente",
                                    "type" => "swal.fire"
                                ));
                                $from->send($msg);
                            }
                        }
                    }
                }
                break;
            case 'seguimientoCotizacion':
                $info = $this->clients->offsetGet($from);
                foreach ($this->clients as $client) {
                    $infoClient = $this->clients->offsetGet($client);
                    if ($infoClient["type"] == "businessManager" && $infoClient["idComercial"] == $info["idComercial"]) {
                        $client->send(json_encode($msg));
                        break;
                    }
                }
            case 'seguimientoCotizacionFinalizado':
                $info = $this->clients->offsetGet($from);
                foreach ($this->clients as $client) {
                    $infoClient = $this->clients->offsetGet($client);
                    if ($infoClient["type"] == "businessManager" && $infoClient["idComercial"] == $info["idComercial"]) {
                        $msg = json_encode(array(
                            "icon" => "info",
                            "title" => "Acompañamiento finalizado",
                            "html" => "No dudes en llamarnos",
                            "type" => "seguimientoCotizacionFinalizado"
                        ));
                        $client->send($msg);
                        break;
                    }
                }
                break;
        }
    }

    private function sendListClientesCotizacionPrimeraVez()
    {
        $comerciales = array();
        $clientes = array(); //Listado de turnos
        $comercialesLinea = array();
        //Relacionamos los clientes por el comercial
        foreach ($this->clients as $client) {
            $info = $this->clients->offsetGet($client);
            if ($info["type"] == "customer") {
                if (!key_exists($info["idComercial"], $comerciales)) {
                    $comerciales[$info["idComercial"]] = array(
                        "clientes" => array(),
                        "type" => "listClientesCotizacionPrimeraVez"
                    );
                }
                $comerciales[$info["idComercial"]]["clientes"][] = array(
                    "idCliente" => $info["idClientePortal"],
                    "razonSocial" => $info["razonSocial"]
                );
                //Preparamos el listado de los turnos
                if (!key_exists($info["idComercial"], $clientes)) {
                    $clientes[$info["idComercial"]] = array();
                }
                if (!key_exists($info["idClientePortal"], $clientes[$info["idComercial"]])) {
                    $clientes[$info["idComercial"]][$info["idClientePortal"]] = array(
                        "type" => "numeroTurno"
                    );
                }
                $clientes[$info["idComercial"]][$info["idClientePortal"]]["turno"] = count($clientes[$info["idComercial"]]);
            }else if($info["type"] == "businessManager"){
                $comercialesLinea[] = $info["idComercial"];
            }
        }
        //Verificamos si el comercial esta en linea
        foreach ($comerciales as $idComercial => $data) {
            if(array_search($idComercial, $comercialesLinea) === false){
                $newIdComercial = $comercialesLinea[count($comercialesLinea) - 1];
                if(key_exists($newIdComercial, $comerciales)){
                    $comerciales[$newIdComercial] = array_merge_recursive($comerciales[$newIdComercial], $data);
                    $clientes[$newIdComercial] = array_merge_recursive($clientes[$newIdComercial], $clientes[$idComercial]);
                    $this->clients->offsetGet($client);
                }else{
                    $comerciales[$newIdComercial] = $data;
                    $clientes[$newIdComercial] = $clientes[$idComercial];
                }
                foreach ($this->clients as $client) {
                    $info = $this->clients->offsetGet($client);
                    if($info["idComercial"] == $idComercial){
                        $info["idComercial"] = $newIdComercial;
                        $this->clients->offsetSet($client, $info);
                    }
                }
                unset($comerciales[$idComercial]);
                unset($clientes[$idComercial]);
            }
        }

        foreach ($this->clients as $client) {
            $info = $this->clients->offsetGet($client);
            if (key_exists($info["idComercial"], $comerciales)) {
                if ($info["type"] == "businessManager") {
                    $client->send(json_encode(
                        $comerciales[$info["idComercial"]]
                    ));
                } else if ($info["type"] == "customer") {
                    $client->send(json_encode(
                        $clientes[$info["idComercial"]][$info["idClientePortal"]]
                    ));
                }
            }
        }
    }
}
