<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use PhpDraft\Config\Security\UserProvider;
use PhpDraft\Config\Security\AuthenticationEntryPoint;
//use JDesrosiers\Silex\Provider\CorsServiceProvider;

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$app['users'] = function () use ($app) {
  return new UserProvider($app);
};

$app['security.jwt'] = [
    'secret_key' => AUTH_KEY,
    'life_time'  => 86400,
    'algorithm'  => ['HS256'],
    'options'    => [
        'header_name' => 'X-Access-Token'
    ]
];

$app['security.firewalls'] = array(
  'login' => [
    'pattern' => 'login|register|verify|lostPassword|resetPassword',
    'anonymous' => true,
  ],
  'admin' => array(
    'pattern' => '^/admin',
    'logout' => array('logout_path' => '/logout'),
    'users' => $app['users'],
    'jwt' => array(
        'use_forward' => true,
        'require_previous_session' => false,
        'stateless' => true,
    )
  ),
  'commish' => array(
    'pattern' => '^/commish',
    'logout' => array('logout_path' => '/logout'),
    'users' => $app['users'],
    'jwt' => array(
        'use_forward' => true,
        'require_previous_session' => false,
        'stateless' => true,
    )
  )
);

$app['security.role_hierarchy'] = array(
  'ROLE_ADMIN' => array('ROLE_MANAGER'),
);

$app->register(new Silex\Provider\SecurityServiceProvider());
$app->register(new Silex\Provider\SecurityJWTServiceProvider());

//Uncomment until we can verify WSSE auth is working on localhost
// $app->register(new CorsServiceProvider(), array(
//     'cors.allowOrigin' => $app['phpdraft.apiBaseUrl'], // your client application
//     'cors.exposeHeaders' => 'X-Total-Count', // ng-admin read this header
// ));