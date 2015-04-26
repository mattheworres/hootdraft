<?php 

require_once __DIR__.'/../../vendor/autoload.php';

$app = new Silex\Application();

require_once __DIR__.'/_settings.php';
require_once __DIR__.'/_database.php';
require_once __DIR__.'/_log.php';
require_once __DIR__.'/_router.php';

return $app;