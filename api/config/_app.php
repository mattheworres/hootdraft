<?php 

require_once __DIR__.'/../../vendor/autoload.php';

// use Symfony\Component\HttpFoundation\Request,
// 		Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->register(new \Knp\Provider\ConsoleServiceProvider(), array(
	'console.name'								=>	'PHPDraft',
	'console.version'							=>	'2.0.0',
	'console.project_directory'		=>	__DIR__ . '/api'
));

require_once __DIR__.'/_router.php';
require_once __DIR__.'/_settings.php';
require_once __DIR__.'/_database.php';
require_once __DIR__.'/_log.php';

return $app;