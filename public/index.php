<?php

use App\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__.'/../.env');
}

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env));

if ($debug) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

$linea  = strtok($request,"\n");
$offset = strpos($linea,"/");
$linea  = substr($linea,$offset);
$length = strrpos($linea," ")-1;
$servicio = str_replace("/","_",substr($linea,1,$length));
$logfilename = $servicio.date('_Y-m-d_H-i-s_').uniqid();
$logdir ='logs/'.date('Y').'/'.date('m').'/'.date('d').'/';
if (!is_dir($logdir)) {
  mkdir($logdir,0777,true);
}
file_put_contents($logdir.$logfilename.'.req', $request, FILE_APPEND | LOCK_EX);
file_put_contents($logdir.$logfilename.'.res', $response, FILE_APPEND | LOCK_EX);

