<?php

namespace App\Tests;

use GuzzleHttp\Client;
use App\Test\JsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class QQTest extends JsonApiTestCase
{
    public function testCreateToken()
    {
        $root = getenv("LOCALHOST");
        $resp = $this->client->request("POST", $root . "/login/api-test/login");
        $this->assertTrue($resp->getStatusCode()==Response::HTTP_OK);
    }

    public function testAccesData() 
    {
        $root = getenv("LOCALHOST");
        $resp = $this->client->request("POST", $root . "/login/api-test/login");
        $this->assertTrue($resp->getStatusCode()==Response::HTTP_OK);

        $body = json_decode($resp->getBody());
        $tokHeader [ getenv("AUTH_HEADER") ] = getenv("AUTH_PREFIX") . $body->token;

        $client = new Client([
            "body"    => "",
            "headers" => $tokHeader
        ]);
        $resp = $client->request("POST", $root . "/proxy/api-test/data");
        
        /*$resp = $this->client->request(
            "POST", 
            $root . "/proxy/api-test/data",
            [
                $tokHeader
            ]
        );
        */
        echo "status-code " . $resp->getStatusCode();

    }
}