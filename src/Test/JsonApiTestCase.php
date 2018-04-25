<?php

namespace App\Test;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

class JsonApiTestCase extends TestCase
{
    private static $staticClient;

    public static function setUpBeforeClass()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../../.env');
        self::$staticClient = new Client();
    }

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->client = self::$staticClient;
    }
}