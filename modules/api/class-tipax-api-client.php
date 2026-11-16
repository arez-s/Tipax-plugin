<?php

/**
 * Request Class
 * This class is for managing requests to webservices
 */
class Tipax_Api_Client
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

    private function logApiResponse($request, $method, $rawResponseBody, $decodedBody, $responseStatusCode)
    {
        $requestPath = explode('?', trim((string) $request, '/'), 2)[0];
        $isSuccessfulAddressList = strtoupper((string) $method) === 'GET'
            && strcasecmp($requestPath, 'Addresses') === 0
            && (int) $responseStatusCode >= 200
            && (int) $responseStatusCode < 300;

        if ($isSuccessfulAddressList) {
            $resultCount = null;
            if (is_array($decodedBody)) {
                $resultCount = count($decodedBody);
            } elseif (
                is_object($decodedBody)
                && isset($decodedBody->result)
                && is_array($decodedBody->result)
            ) {
                $resultCount = count($decodedBody->result);
            }

            if ($resultCount !== null) {
                tipax_log(
                    'URL: ' . $this->baseUrl . '/api/OM/v3/' . $request
                    . ' Response Count: ' . $resultCount
                );
                return;
            }
        }

        tipax_log(
            'URL: ' . $this->baseUrl . '/api/OM/v3/' . $request
            . ' Response: ' . $rawResponseBody
        );
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

    private function refreshAuthentication(): bool
    {
        if (class_exists('Tipax_Auth_Service')) {
            $auth = new Tipax_Auth_Service($this);
            $auth->check_api_key(true);
            return true;
        }

        if (function_exists('tipax_require_shipping_method_class') && tipax_require_shipping_method_class()) {
            $tipax_shipping = new WC_Tipax_Shipping_Method();
            $tipax_shipping->checkApiKey(true);
            return true;
        }

        if (class_exists('WC_Tipax_Shipping_Method')) {
            $tipax_shipping = new WC_Tipax_Shipping_Method();
            $tipax_shipping->checkApiKey(true);
            return true;
        }

        tipax_log('Tipax authentication refresh skipped: no compatible auth handler is available.');
        return false;
    }

    private function buildTransportErrorResponse($responseError, $timeout)
    {
        $message = is_wp_error($responseError) ? $responseError->get_error_message() : '';
        $errorType = 'transport';
        $normalized = strtolower((string) $message);

        if (strpos($normalized, 'timed out') !== false || strpos($normalized, 'curl error 28') !== false) {
            $errorType = 'timeout';
        }

        return (object) [
            'status' => 0,
            'message' => $message !== '' ? $message : 'Tipax transport error.',
            'tipax_error_type' => $errorType,
            'tipax_is_timeout' => $errorType === 'timeout',
            'tipax_timeout' => (int) $timeout,
        ];
    }

    private function extractApiErrorMessage($decodedBody, $rawBody = '')
    {
        if (is_string($decodedBody)) {
            return trim($decodedBody);
        }

        if (is_object($decodedBody)) {
            foreach (['Message', 'message', 'errorMessage', 'error'] as $property) {
                if (isset($decodedBody->{$property}) && is_scalar($decodedBody->{$property})) {
                    $message = trim((string) $decodedBody->{$property});
                    if ($message !== '') {
                        return $message;
                    }
                }
            }

            if (!empty($decodedBody->errors) && is_array($decodedBody->errors)) {
                foreach ($decodedBody->errors as $error) {
                    $message = $this->extractApiErrorMessage($error);
                    if ($message !== '') {
                        return $message;
                    }
                }
            }
        }

        if (is_array($decodedBody)) {
            foreach (['Message', 'message', 'errorMessage', 'error'] as $key) {
                if (isset($decodedBody[$key]) && is_scalar($decodedBody[$key])) {
                    $message = trim((string) $decodedBody[$key]);
                    if ($message !== '') {
                        return $message;
                    }
                }
            }

            foreach ($decodedBody as $item) {
                $message = $this->extractApiErrorMessage($item);
                if ($message !== '') {
                    return $message;
                }
            }
        }

        return trim((string) $rawBody);
    }

    private function apiPutRequest($request, $data, $apikey, $timeout, $contentType, $returnTransportError)
    {
        $url = $this->baseUrl . '/api/OM/v3/' . ltrim($request, '/');
        $body = '';
        if ($data !== null && $data !== [] && $data !== '') {
            $encodedBody = wp_json_encode($data);
            $body = is_string($encodedBody) ? $encodedBody : '';
        }

        $options = [
            'headers' => [
                'Content-Type' => $contentType,
                'Content-Length' => (string) strlen($body),
            ],
            'method' => 'PUT',
            'timeout' => $timeout,
            'data_format' => 'body',
            'body' => $body,
        ];

        if ($apikey !== null && $apikey !== '') {
            $options['headers']['Authorization'] = 'Bearer ' . $apikey;
        }

        $response = wp_remote_request($url, $options);
        if (is_wp_error($response)) {
            tipax_log('____________________________________________________________________________________');
            tipax_log('URL: ' . $url . ' Transport Error: ' . $response->get_error_message());
            tipax_log('____________________________________________________________________________________');

            if ($returnTransportError) {
                return $this->buildTransportErrorResponse($response, $timeout);
            }

            return (object) [
                'tipax_request_success' => false,
                'tipax_http_status' => 0,
                'tipax_error_type' => 'transport',
                'message' => $response->get_error_message(),
            ];
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        $rawBody = (string) wp_remote_retrieve_body($response);
        $decodedBody = $rawBody !== '' ? json_decode($rawBody) : null;
        $success = $statusCode >= 200 && $statusCode < 300;
        $message = $this->extractApiErrorMessage($decodedBody);

        if (!$success && $message === '') {
            $message = $rawBody !== '' ? $rawBody : 'Tipax API request failed.';
        }

        tipax_log('____________________________________________________________________________________');
        tipax_log('URL: ' . $url . ' Method: PUT');
        tipax_log('URL: ' . $url . ' Response: ' . $rawBody);
        tipax_log('URL: ' . $url . ' Response Status: ' . $statusCode);
        tipax_log('____________________________________________________________________________________');

        $result = (object) [
            'tipax_request_success' => $success,
            'tipax_http_status' => $statusCode,
            'tipax_response' => $decodedBody !== null ? $decodedBody : $rawBody,
        ];

        if (!$success) {
            $result->tipax_error_type = 'api';
            $result->message = $message;
        } elseif ($message !== '') {
            $result->message = $message;
        }

        return $result;
    }

    /**
     * @param string $request
     * @param string $method
     * @param array $data
     * @param string|null $apikey
     * @return mixed|string
     */

    public function apiRequest(string $request, $data, string $method, string $apikey = null, array $requestOptions = [])
    {
        if (class_exists('Tipax_Hub_Updater', false) && Tipax_Hub_Updater::requires_update()) {
            return Tipax_Hub_Updater::blocked_response();
        }
        $requestOptions = wp_parse_args($requestOptions, [
            'timeout' => 45,
            'return_transport_error' => false,
            'content_type' => 'application/json-patch+json',
        ]);
        $timeout = isset($requestOptions['timeout']) && is_numeric($requestOptions['timeout'])
            ? max(1, (int) $requestOptions['timeout'])
            : 45;
        $contentType = !empty($requestOptions['content_type']) && is_string($requestOptions['content_type'])
            ? $requestOptions['content_type']
            : 'application/json-patch+json';
        $method = strtoupper($method);

        if ($method === 'PUT') {
            return $this->apiPutRequest(
                $request,
                $data,
                $apikey,
                $timeout,
                $contentType,
                !empty($requestOptions['return_transport_error'])
            );
        }

        if($method == "POST"){

            try {
                $body = wp_json_encode( $data );
                if($apikey == null){
                    $options = [
                        'body'        => $body,
                        'headers'     => [
                            'Content-Type' => $contentType,
                        ],
                        'method' => 'POST',
                        'timeout' => $timeout,
                        'data_format' => 'body',
                    ];
                }else{
                    $options = [
                        'body'        => $body,
                        'headers'     => [
                            'Content-Type' => $contentType,
                            'Authorization' => 'Bearer '.$apikey,
                        ],
                        'method' => 'POST',
                        'timeout' => $timeout,
                        'data_format' => 'body',
                    ];

                }

                $response = wp_remote_post($this->baseUrl.'/api/OM/v3/'. $request, $options);

                if (is_wp_error($response)) {
                    tipax_log("____________________________________________________________________________________");
                    tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Transport Error: ".$response->get_error_message());
                    tipax_log("____________________________________________________________________________________");

                    if (!empty($requestOptions['return_transport_error'])) {
                        return $this->buildTransportErrorResponse($response, $timeout);
                    }

                    throw new Exception($response->get_error_message());
                }

                // var_dump($body);die();

                tipax_log("____________________________________________________________________________________");

                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Data: ".$body);
                $rawResponseBody = (string) wp_remote_retrieve_body($response);
                $body = json_decode($rawResponseBody);
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Response: ".$rawResponseBody);
                $bodyStatus = is_object($body) && isset($body->status) ? $body->status : 'Empty';
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Body Status: ".$bodyStatus);

                $responseStatusCode = wp_remote_retrieve_response_code( $response );

                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Response Status: ". $responseStatusCode);
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." is_wp_error(): ". is_wp_error( $response ));
                tipax_log("____________________________________________________________________________________");

                if ( $responseStatusCode < 200 || $responseStatusCode >= 300 ) {
                    if($responseStatusCode == 401){
                        tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Go to UnauthorizedException");
                        throw new UnauthorizedException();
                    }else if($responseStatusCode == 403){
                        tipax_log("Token Must Refresh");
                        throw new RefreshToken();
                    } else if ($responseStatusCode == 400) {
                        tipax_log("Tipax API 400 Bad Request, full body:\n" . $rawResponseBody);
                        $errorMessage = $this->extractApiErrorMessage($body, $rawResponseBody);
                        if ($errorMessage !== '') {
                            tipax_log($errorMessage);
                        }
                    } else {
                        tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." responseStatusCode: ".$responseStatusCode);
                        throw new Exception();
                    }
                }

                return $body;
            }catch (UnauthorizedException $unauthorizedException){
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/: '.$request.' UnauthorizedException');
                delete_option('woocommerce_tipax_shipping_method_accesstoken');
                tipax_log("woocommerce_tipax_shipping_method_accesstoken deleted");
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." ".$unauthorizedException->getMessage());
            }catch (RefreshToken $refreshToken){
                $this->refreshAuthentication();
                return $refreshToken->getMessage();
                tipax_log("Token Refreshed");
            }catch(Exception $e){
                if($e->getCode() == 401 || $e->getCode() == 403){
                    $this->refreshAuthentication();
                    return $e->getMessage();
                }else{
                    return $e->getMessage();
                }
            }
        }else{
            try {
                $body = $data;
                $headers = [
                    'Accept' => 'application/json',
                ];
                if ($apikey !== null && $apikey !== '') {
                    $headers['Authorization'] = 'Bearer ' . $apikey;
                }

                $options = [
                    'headers' => $headers,
                    'method' => 'GET',
                    'timeout' => $timeout,
                ];

                if ($body !== null && $body !== [] && $body !== '') {
                    $options['body'] = $body;
                    $options['data_format'] = 'body';
                    $options['headers']['Content-Type'] = $contentType;
                }


                $response = wp_remote_get($this->baseUrl.'/api/OM/v3/'. $request, $options);

                if (is_wp_error($response)) {
                    tipax_log("____________________________________________________________________________________");
                    tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Transport Error: ".$response->get_error_message());
                    tipax_log("____________________________________________________________________________________");

                    if (!empty($requestOptions['return_transport_error'])) {
                        return $this->buildTransportErrorResponse($response, $timeout);
                    }

                    throw new Exception($response->get_error_message());
                }

                tipax_log("____________________________________________________________________________________");

                $requestBodyLog = is_scalar($body) ? (string) $body : wp_json_encode($body);
                if (!is_string($requestBodyLog)) {
                    $requestBodyLog = gettype($body);
                }
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Data: ".$requestBodyLog);
                $rawResponseBody = (string) wp_remote_retrieve_body($response);
                $body = json_decode($rawResponseBody);
                $responseStatusCode = wp_remote_retrieve_response_code($response);
                $this->logApiResponse($request, $method, $rawResponseBody, $body, $responseStatusCode);
                $bodyStatus = is_object($body) && isset($body->status) ? $body->status : 'Empty';
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Body Status: ".$bodyStatus);

                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Response Status: ". $responseStatusCode);
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." is_wp_error() ". is_wp_error( $response ));
                tipax_log("____________________________________________________________________________________");

                if ( $responseStatusCode < 200 || $responseStatusCode >= 300 ) {
                    if($responseStatusCode == 401){
                        tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." Go to UnauthorizedException");
                        throw new UnauthorizedException();
                    }else{
                        tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." responseStatusCode: ".$responseStatusCode);
                        $message = tipax_extract_tipax_response_message(
                            $body,
                            $rawResponseBody !== ''
                                ? $rawResponseBody
                                : 'Tipax API request failed.'
                        );
                        if (!empty($requestOptions['return_transport_error'])) {
                            return (object) [
                                'status' => $responseStatusCode,
                                'message' => $message,
                                'tipax_error_type' => 'api',
                                'tipax_response' => $body,
                            ];
                        }
                        throw new Exception($message, $responseStatusCode);
                    }
                }

                return $body;
            }
            catch (UnauthorizedException $unauthorizedException){
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/: '.$request.' UnauthorizedException');
                delete_option('woocommerce_tipax_shipping_method_accesstoken');
                tipax_log("URL: ".$this->baseUrl.'/api/OM/v3/'. $request." ".$unauthorizedException->getMessage());
            }
            catch(Exception $e){
                if($e->getCode() == 401 || $e->getCode() == 403){
                    tipax_log("status 3");
                    $this->refreshAuthentication();
                    return $e->getMessage();
                }else{
                    tipax_log("status 4");
                    tipax_log($e->getCode());
                    return $e->getMessage();
                }
            }
        }
    }
}


if (!class_exists('Request')) {
    class Request extends Tipax_Api_Client {}
}
