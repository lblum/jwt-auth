<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LoginController extends BaseController
{
    /**
     * @Route("/login/{service}/{route}", name="login", defaults={"service" = null ,"route" = null}, requirements={"route"=".+"})
     */
    public function index($service,$route,Request $req)
    {
        $resp = $this->forwardReq($service,$req);
        if ( $resp->getStatusCode() == Response::HTTP_OK ) 
        {
            $jsonData = json_decode($resp->getContent(),true);
            $ttl = 3600;
            $retData = [
                "payload" => $jsonData,
                "exp"     => time() + $ttl
            ];
            $token = $this->get("lexik_jwt_authentication.encoder")
                     ->encode($retData);
            $retData["token"] = $token;
            return $this->json($retData);
        }
        return $resp;
    }
}
