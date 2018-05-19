<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LoginController extends BaseController
{
    /**
     * @Route("/{service}/login", name="login", defaults={"service" = null ,"route" = null}, requirements={"route"=".+"})
     */
    public function index($service,Request $req)
    {
        $resp = $this->forwardReq($service,$req);
        if ( $resp->getStatusCode() == Response::HTTP_OK ) 
        {
            $jsonData = json_decode($resp->getContent(),true);
            $ttl = 3600;
            $retData = [
                "payload" => $jsonData,
                "exp"     => time() + $ttl,
                "aud"     => $service
            ];
            $token = $this->get("lexik_jwt_authentication.encoder")
                     ->encode($retData);
            $retData["token"] = $token;
            return $this->json($retData);
        }
        return $resp;
    }
}
