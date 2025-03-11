<?php
require_once RUTA_APP . "Modelo/Gestor/CURL_Abstract.php";

class ProductoMode extends CURL
{
    public function getByIdInventario($idInventario)
    {
        $data = array();
        try {
            $response = $this->curl(
                "Producto/getByIdInventario",
                array(
                    "idInventario" => $idInventario
                )
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

    public function getById($idProducto)
    {
        $data = array();
        try {
            $response = $this->curl(
                "Producto/getById",
                array(
                    "idProducto" => $idProducto
                )
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

    public function generateCompra()
    {
        $data = array();
        try {
            $response = $this->curl(
                "Bot/GenerateCompra",
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