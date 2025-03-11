<?php
require_once RUTA_APP . "Modelo/Gestor/CURL_Abstract.php";

class UsuarioMode extends CURL
{

    public function setResourceId()
    {
        $exito = false;
        try {
            $response = $this->curl(
                "Usuario/setResourceId",
                $_POST
            );
            if($response === false){
                return $exito;
            }
            if(!$response["exito"]){
                $this->mensaje = $response["mensaje"];
            }else{
                $exito = $response["exito"];
            }
        } catch (PDOException $ex) {
            $this->mensaje = $ex->getMessage();
        }
        return $exito;
    }

    public function withAccessBodega(){
        $exito = false;
        try {
            $response = $this->curl(
                "Bodega/verifyAccessBodega",
                $_POST
            );
            if($response === false){
                return $exito;
            }
            if(!$response["exito"]){
                $this->mensaje = $response["mensaje"];
            }else{
                $exito = $response["exito"];
            }
        } catch (PDOException $ex) {
            $this->mensaje = $ex->getMessage();
        }
        return $exito;
    }
}