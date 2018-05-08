<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;

class ProxyController extends BaseController
{
    /**
     * @Route("/{service}/{route}", name="proxy", defaults={"route" = null}, requirements={"route"=".+"})
     */
    public function proxy($service,$route,Request $req)
    {
        // Chequeo por autorización
        if ( !$this->checkCredentials($req) )
            return new JsonResponse([
                "error" => "auth required"
            ], Response::HTTP_UNAUTHORIZED);
        
        $resp = $this->forwardReq($service,$req);
        return $resp;
    }


    /**
     * Valido la autorización en el header
     *
     * @param Request $request
     * @return boolean
     */
    public function checkCredentials(Request $request)
    {
        // Extraigo el token del header
        $extractor = new AuthorizationHeaderTokenExtractor(
            getenv("AUTH_PREFIX"),
            getenv("AUTH_HEADER")
        );
        $token = $extractor->extract($request);
        // Si no existe -> HTTP_UNAUTHORIZED (401)
        if (!$token) {
            return false;
        }

        // Chequeo la validez del token
        try {
            $data = $this->jwtEncoder->decode($token);
        } catch (JWTDecodeFailureException $e) {
            return false;
        }

        // Si llego acá, todo OK
        return true;
    }
}
