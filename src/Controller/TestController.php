<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TestController extends Controller
{
    /**
     * @Route("/test/{op}", name="test", defaults={"op" = null}, requirements={"op"=".+"})
     */
    public function test($op,Request $req)
    {
        // Solo en ambiente de desarrollo
        if ( !$this->get('kernel')->isDebug() )
            throw $this->createNotFoundException("Not found");

        // Busco el archivo con el Json a devolver
        try {
            $fName = __DIR__ . "/../Json-Test/${op}.json";
            $jsonData = json_decode(file_get_contents($fName));

            return $this->json($jsonData->payload,$jsonData->status);
        } catch(\Exception $ex){
            return $this->json([
                "status" => 500,
                "reason" => $ex->getMessage()
                ],
                500
            );
        }
    }
}
