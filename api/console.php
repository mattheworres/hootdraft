<?php
use Silex\Application AS SilexApplication;
use Symfony\Component\Console\Application AS ConsoleApplication;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\HelperSet;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
//require_once __DIR__.'/../../vendor/autoload.php';
$loader = require_once __DIR__.'/../vendor/autoload.php';

set_time_limit(0);

$app = new SilexApplication();
$cli = true;

require_once('api/bootstrap.php');

$console = new ConsoleApplication('PHP Draft', '2.0.0');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));

/*
 * Doctrine CLI
 */
$helperSet = new HelperSet(array(
    'db' => new ConnectionHelper($app['db']->getWrappedConnection())/*,
    'em' => new EntityManagerHelper($app['orm.em'])*/
));

$console->setHelperSet($helperSet);
ConsoleRunner::addCommands($console);

$console->run();