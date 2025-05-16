<?php

/**
 * Request Class
 * This class is for managing requests to webservices
 */
require_once "Exeptions/UnauthorizedException.php";
require_once "Exeptions/RefreshToken.php";

class Request
{
    protected $baseUrl;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'handleRequest']);
        $this->baseUrl = "https://omapi.tipax.ir";
    }

    /**
     * @throws Exception
     */
    public function handleRequest()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $this->dispatchRequest($requestUri);
    }

    /**
     * @param $requestUri
     * @throws Exception
     */
    private function dispatchRequest($requestUri)
    {
        if (strpos($requestUri, 'dashboard') == false) return;
        $handler = $this->parseUri($requestUri);
        $handlerName = $this->formatHandlerName($handler);
        if (!$this->isHandlerValid($handlerName)){
            throw new Exception('request handler is not valid'.$handlerName);
        }

        $handlerPath = $this->getHandlerFile($handlerName);
        include_once $handlerPath;
        $handlerInstance = new $handlerName;
        $handlerInstance->index();
        exit;
    }

    /**
     * @param $requestUri
     * @return string
     */
    private function parseUri($requestUri) : string
    {
        $uriParts = explode('/', strtok($requestUri, '?'));
        return end($uriParts);
    }

    /**
     * @param $handler
     * @return bool
     */
    private function isHandlerValid($handler) : bool
    {
        $handlerFilePath = $this->getHandlerFile($handler);
        return file_exists($handlerFilePath) && is_readable($handlerFilePath);
    }

    /**
     * @param $handler
     * @return string
     */
    private function getHandlerFile($handler) : string
    {
        return TSM_DIR . DIRECTORY_SEPARATOR .'panel/handlers/'. $handler .'.php';
    }

    /**
     * @param $handler
     * @return string
     */

    private function formatHandlerName($handler) : string
    {
        return ucfirst($handler) . 'Handler';
    }

    /**
     * @param string $request
     * @param string $method
     * @param array $data
     * @param string|null $apikey
     * @return mixed|string
     */

    public function apiRequest(string $request, $data, string $method, string $apikey = null)
    {
        if($method == "POST"){
            try {
                $body = wp_json_encode( $data );
                if($apikey == null){
                    $options = [
                        'body'        => $body,
                        'headers'     => [
                            "Content-type" => "application/json-patch+json"
                        ],
                        'method' => 'POST',
                        'timeout' => 45,
                        'data_format' => 'body',
                    ];
                }else{
                    $options = [
                        'body'        => $body,
                        'headers'     => [
                            "Content-type" => "application/json-patch+json",
                            'Authorization' => 'Bearer '.$apikey,
                        ],
                        'method' => 'POST',
                        'timeout' => 45,
                        'data_format' => 'body',
                    ];
                }

                $response = wp_remote_post($this->baseUrl.'/api/OM/v3/'. $request, $options);
                // var_dump($body);die();

                error_log("____________________________________________________________________________________");

                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Data: ".json_encode($body));
                $body = json_decode(wp_remote_retrieve_body($response));
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Response: ".wp_remote_retrieve_body($response));
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Body Status: ".(isset($body->status) ? $body->status : "Empty"));

                $responseStatusCode = wp_remote_retrieve_response_code( $response );

                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Response Status: ". $responseStatusCode);
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." is_wp_error(): ". is_wp_error( $response ));
                error_log("____________________________________________________________________________________");

                if ( is_wp_error( $response ) || ($responseStatusCode < 200 || $responseStatusCode >= 300 ) ) {
                    if($responseStatusCode == 401){
                        error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Go to UnauthorizedException");
                        throw new UnauthorizedException();
                    }else if($responseStatusCode == 403){
                        error_log("Token Must Refresh");
                        throw new RefreshToken();
                    }else{
                        error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." responseStatusCode: ".$responseStatusCode);
                        throw new Exception();
                    }
                }

                return $body;
            }catch (UnauthorizedException $unauthorizedException){
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/: '.$request.' UnauthorizedException');
                delete_option('woocommerce_tipax_shipping_method_accesstoken');
                error_log("woocommerce_tipax_shipping_method_accesstoken deleted");
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." ".$unauthorizedException->getMessage());
            }catch (RefreshToken $refreshToken){
                $tipax_shipping = new WC_Tipax_Shipping_Method();
                $tipax_shipping->checkApiKey(true);
                return $refreshToken->getMessage();
                error_log("Token Refreshed");
            }catch(Exception $e){
                if($e->getCode() == 401 || $e->getCode() == 403){
                    $tipax_shipping = new WC_Tipax_Shipping_Method();
                    $tipax_shipping->checkApiKey(true);
                    return $e->getMessage();
                }else{
                    return $e->getMessage();
                }
            }
        }else{
            try {
                $body = $data;
                if($apikey == null){
                    $options = [
                        'body'        => $body,
                        'headers'     => [
                            "Content-type" => "application/json-patch+json"
                        ],
                        'method' => 'GET',
                        'data_format' => 'body',
                    ];
                }else{
                    $options = [
                        'body'        => $body,
                        'headers'     => [
                            "Content-type" => "application/json-patch+json",
                            'Authorization' => 'Bearer '.$apikey,
                        ],
                        'method' => 'GET',
                        'data_format' => 'body',
                    ];
                }


                $response = wp_remote_get($this->baseUrl.'/api/OM/v3/'. $request, $options);

                error_log("____________________________________________________________________________________");

                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Data: ".json_encode($body));
                $body = json_decode(wp_remote_retrieve_body($response));
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Response: ".wp_remote_retrieve_body($response));
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Body Status: ".(isset($body->status) ? $body->status : "Empty"));

                $responseStatusCode = wp_remote_retrieve_response_code( $response );

                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Response Status: ". $responseStatusCode);
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." is_wp_error() ". is_wp_error( $response ));
                error_log("____________________________________________________________________________________");

                if ( is_wp_error( $response ) || ($responseStatusCode < 200 || $responseStatusCode >= 300 ) ) {
                    if($responseStatusCode == 401){
                        error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Go to UnauthorizedException");
                        throw new UnauthorizedException();
                    }else{
                        error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." responseStatusCode: ".$responseStatusCode);
                        throw new Exception();
                    }
                }

                return $body;
            }
            catch (UnauthorizedException $unauthorizedException){
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/: '.$request.' UnauthorizedException');
                delete_option('woocommerce_tipax_shipping_method_accesstoken');
                error_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." ".$unauthorizedException->getMessage());
            }
            catch(Exception $e){
                if($e->getCode() == 401 || $e->getCode() == 403){
                    error_log("status 3");
                    $tipax_shipping = new WC_Tipax_Shipping_Method();
                    $tipax_shipping->checkApiKey(true);
                    return $e->getMessage();
                }else{
                    error_log("status 4");
                    error_log($e->getCode());
                    return $e->getMessage();
                }
            }
        }
    }
}