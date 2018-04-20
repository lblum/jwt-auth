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
     * @Route("/", name="root")
     */
    public function noHome()
    {
        throw $this->createNotFoundException('no default route exists');
    }

    /**
     * Elimina los prefijos sobrantes
     *
     * @param String $service
     * @param String $uri
     * @return String
     */
    protected function getDestURI($service,$uri)
    {        
        $uri = preg_replace("/^\/login/","",$uri,1);
        $uri = preg_replace("/^\/proxy/","",$uri,1);
        $uri = preg_replace("/^\/$service/","",$uri,1);
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
        // Traduzco el servicio a la nueva URl
        $proxyList = $this->getParameter("app.proxy_list");
        if ( array_search($service,array_keys($proxyList),true) === false )
            throw $this->createNotFoundException("Servicio $service no encontrado (¿falta configuración?)");

        // Saco los prefijos sobrantes
        $route = $this->getDestURI($service,$req->getRequestUri());

        // Preparo los headers a enviar
        // TODO: es necesario enviar el header de Authorization?        
        $headers = [];
        foreach($req->headers->all() as $key=>$val)
            $headers[] = "$key:" . $val[0];
        $client = new Client([
            "body"    => $req->getContent(),
            "headers" => $headers
        ]);
        try {
            // El envío propiamente dicho
            $resp = $client->request($req->getMethod(), $proxyList[$service] . $route);
            
            $body = $resp->getBody();
            $statusCode = $resp->getStatusCode();
            $headers = $resp->getHeaders();
    
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
                $resp->getHeaders()
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

}
