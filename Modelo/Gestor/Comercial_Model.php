<?php
require_once RUTA_APP . "Modelo/Gestor/CURL_Abstract.php";

class ComercialMode extends CURL
{
    public function disponible()
    {
        $data = array();
        try {
            $response = $this->curl(
                "Comercial/Disponible"
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