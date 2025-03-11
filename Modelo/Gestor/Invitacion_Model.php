<?php
require_once RUTA_APP . "Modelo/Gestor/CURL_Abstract.php";

class InvitacionMode extends CURL
{
    public function getByIdEmpresa()
    {
        $data = array();
        try {
            $response = $this->curl(
                "Invitaciones/getInvitacionByIdEmpresa",
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