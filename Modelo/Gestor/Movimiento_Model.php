<?php
require_once RUTA_APP . "Modelo/Gestor/CURL_Abstract.php";

class MovimientoMode extends CURL
{

    public function getInventario()
    {
        $data = array();
        try {
            $response = $this->curl(
                "Movimiento/getInventario",
                $_POST
            );
            if($response === false){
                return $data;
            }
            if(!$response["exito"]){
                $this->mensaje = $response["mensaje"];
            }else{
                $data = $response["data"];
            }
        } catch (PDOException $ex) {
            $this->mensaje = $ex->getMessage();
        }
        return $data;
    }
}