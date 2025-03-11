<?php
require_once RUTA_APP . "Modelo/Model_Abstract.php";

abstract class CURL extends Model
{

    public function __construct()
    {
        parent::__construct();
    }

    private $key = "SmZ8nFzfx2";
    private $domain = (LOCALDIR == "" ? "https://gestorplatform.com.co/" : "https://localhost/gestorplatform.com.co/");

    protected function curl($urlRealtive, $body = array(), $typeReturn = "JSON", $customRequest = "POST")
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->domain . $urlRealtive,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $customRequest,
                CURLOPT_POSTFIELDS => array_merge($body, array('key' => $this->key)),
            ));
            if(LOCALDIR != ""){
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            }
            $response = curl_exec($curl);

            if ($response == null) {
                handler(E_ERROR, curl_error($curl), __FILE__, __LINE__);
                throw new Exception("🌐 Fallo al tener conectividad con nuestro servidor 🌐", 1);
            }

            switch ($typeReturn) {
                case 'JSON':
                    $response = json_decode($response, true);
                    break;

                case 'BASE64':
                    $response = base64_encode($response);
                    break;

                default:
                    handler(E_ERROR, "Type Return desconocido: {$typeReturn}", __FILE__, __LINE__);
                    die();
                    break;
            }

            curl_close($curl);
        } catch (Exception $ex) {
            $this->mensaje = $ex->getMessage();
            return false;
        }
        return $response;
    }
}
