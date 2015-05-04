<?php 

require_once __DIR__.'/../../vendor/autoload.php';

$app = new Silex\Application();

$configuration_variables = array('DB_USER', 'DB_PASS');

foreach($configuration_variables as $variable) {
    define($variable, get_cfg_var("phpdraft.cfg.$variable"));
}

require_once __DIR__.'/_settings.php';
require_once __DIR__.'/_database.php';
require_once __DIR__.'/_log.php';
require_once __DIR__.'/_router.php';
require_once __DIR__.'/Security/_userProvider.php';
require_once __DIR__.'/Security/_wsseProvider.php';
require_once __DIR__.'/Security/_wsseListener.php';
require_once __DIR__.'/Security/_authenticationEntryPoint.php';
require_once __DIR__.'/_security.php';

return $app;