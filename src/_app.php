<?php 
require_once __DIR__.'/../vendor/autoload.php';

// use Symfony\Component\HttpFoundation\Request,
// 		Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

require_once __DIR__.'/_router.php';

return $app;