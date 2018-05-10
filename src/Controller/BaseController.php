<?php

namespace App\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class BaseController extends Controller
{
    protected $jwtEncoder;

    public function __construct(JWTEncoderInterface $jwtEncoder)
    {
        $this->jwtEncoder = $jwtEncoder;
    }
    
    /**
     * Elimina el prefijo de servicio
     *
     * @return String
     */
    protected function getDestURI()
    {        
        $uri = $this->req->getRequestUri();
        $uri = preg_replace("/^\/" . $this->service . "/","",$uri,1);
        return $uri;        
    }

    /**
     * Función forwardReq
     *
     * Es el corazón del proxy. Reenvía lo recibido en el request (body, url y headers) al destino.
     * Responde con lo recibido.
     * 
     * @param String $service
     * @param Request $req
     * @return JsonResponse
     */
    protected function forwardReq(String $service,Request $req)
    {
        // Los parámetros de conexión
        $this->service = $service;
        $this->req = $req;
        $this->loadParams();

        // Saco los prefijos sobrantes
        $route = $this->getDestURI();
        
        $pos = strpos($route,"/login");
        if ( $pos === false ) {
            // Es una conexión común
            $forwardUrl = $this->forward . $route;
        } else {
            // Es un login
            $forwardUrl = $this->login;
            // extraigo el /login para preservar los parámetros
            $forwardUrl .= substr($route,strlen("/login"));
        }

        $clientConfig = $this->params;
        $clientConfig["body"] = $this->req->getContent();
        $clientConfig["headers"] = $this->headers;
    
        $client = new Client($clientConfig);
        try {
            // El envío propiamente dicho
            $resp = $client->request($req->getMethod() , $forwardUrl );
            
            $body = $resp->getBody();
            $statusCode = $resp->getStatusCode();
            $headers = $this->correctHeaders($resp->getHeaders());
    
            // Si es que hay respuesta OK
            $retVal = $this->json(
                $body,
                $statusCode,
                $headers
            );
    
            $retVal->setContent($body);
    
            return $retVal;
        } catch(ClientException $ex) {
            // Excepción HTTP
            $resp = $ex->getResponse();

            $retVal = $this->json([
                    "status" => $resp->getStatusCode(),
                    "reason" => $resp->getReasonPhrase()
                ],
                $resp->getStatusCode(),
                $this->correctHeaders($resp->getHeaders())
            );

            return $retVal;
        } catch(\Exception $ex) {
            // Cualquier otra excepción
            $retVal = $this->json([
                    "status" => Response::HTTP_INTERNAL_SERVER_ERROR,
                    "reason" => $ex->getMessage()
                ],
                500
            );
            return $retVal;
        }
    }


    /**
     * Elimino los headers problemáticos
     *
     * @param Array $headersIn
     * @return Array
     */
    private function correctHeaders($headersIn)
    {
        $headersToDelete = [
            "Transfer-Encoding",
        ];
        $headersOut = [];
        foreach($headersIn as $key=>$val) {
            if ( array_search($key,$headersToDelete) === false) 
            $headersOut[$key] = $val;
        }
        return $headersOut;
    }

    /**
     * El servicio (aplicación)
     *
     * @var String
     */
    protected $service;
    /**
     * El request recibido
     *
     * @var Request
     */
    protected $req;
    /**
     * URL para el autenticar
     *
     * @var String
     */
    protected $login;
    /**
     * URL para el resto del servicio
     *
     * @var String
     */
    protected $forward;
    /**
     * Headers a enviar
     *
     * @var Array
     */
    protected $headers;
    /**
     * Parámetros extra
     *
     * @var Array
     */
    protected $params;

    /**
     * Preparo la configuración de la conexión
     *
     * @return void
     */
    protected function loadParams() {
        // Cargo todo la configuracion
        $proxyList = $this->getParameter("app.proxy_list");
        if ( array_key_exists($this->service,$proxyList) === false )
            throw $this->createNotFoundException("Servicio $this->service no encontrado (¿falta configuración?)");
        $conf = $this->getParameter("app.proxy_list")[$this->service];

        $this->login = $conf["login-url"];
        $this->forward = $conf["forward-url"];

        // Preparo los headers a enviar
        $this->headers = [];
        foreach($this->req->headers->all() as $key=>$val)
            $this->headers[strtolower($key)] = $val[0];

        // Agrego los headers adicionales
        if ( array_key_exists ("headers-extra",$conf) !== false 
             && $conf["headers-extra"] != null ) {
            foreach($conf["headers-extra"] as $key=>$val ) {
                $this->headers[strtolower($key)] = $header;
            }
        }
       
        // Parámetros adicionales
        if ( array_key_exists ("params",$conf) !== false 
             && $conf["params"] != null ) {
            $this->params = $conf["params"];
        }

    }
}
