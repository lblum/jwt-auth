<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TestController extends Controller
{
    /**
     * @Route("/", name="root")
     */
    public function noHome()
    {
        // Solo en ambiente de desarrollo
        if ( !$this->get('kernel')->isDebug() )
            throw $this->createNotFoundException('no default route exists');

        $proxyList = $this->getParameter("app.proxy_list");            
        return $this->render('test/index.html.twig', [
            'proxyList' => $proxyList,
        ]);          
    }
}
