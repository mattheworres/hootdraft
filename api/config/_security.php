<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use PhpDraft\Config\Security\WsseProvider;
use PhpDraft\Config\Security\WsseListener;
//use PhpDraft\Config\Security\WsseFactory;
use PhpDraft\Config\Security\UserProvider;
use PhpDraft\Config\Security\AuthenticationEntryPoint;
use JDesrosiers\Silex\Provider\CorsServiceProvider;

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$app['security.authentication_listener.factory.wsse'] = $app->protect(function ($name, $options) use ($app) {
    // define the authentication provider object
    $app['security.authentication_provider.'.$name.'.wsse'] = $app->share(function () use ($app) {
        return new WsseProvider($app['security.user_provider.default'], __DIR__.'/security_cache');
    });

    // define the authentication listener object
    $app['security.authentication_listener.'.$name.'.wsse'] = $app->share(function () use ($app) {
        return new WsseListener($app['security'], $app['security.authentication_manager']);
    });

    return array(
        // the authentication provider id
        'security.authentication_provider.'.$name.'.wsse',
        // the authentication listener id
        'security.authentication_listener.'.$name.'.wsse',
        // the entry point id
        null,
        // the position of the listener in the stack
        'pre_auth'
    );
});

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    //Two secured firewalls: one for /commish (all commissioner role level actions) and /admin (all sitewide admin level actions)
    'security.user_provider.default' => $app->share(function() use ($app) {
      return new UserProvider();
    }),
    'security.entry_point.default' => $app->share(function() use ($app) {
      return new AuthenticationEntryPoint();
    }),
    'security.firewalls' => array(
      'admin' => array(
          'pattern' => '^/admin',
          'wsse' => true,
          // 'users' => $app->share(function() use ($app) {
          //   return new UserProvider();
          // }),
      ),
      'commish' => array(
          'pattern' => '^/commish',
          //'form' => array('login_path' => '/login', 'check_path' => '/commish/login_check'),
          // 'users' => $app->share(function() use ($app) {
          //   return new UserProvider();
          // })
      ),
    ),
    'security.role_hierarchy' => array(
      'ROLE_ADMIN' => array('ROLE_COMMISSIONER', 'ROLE_MANAGER'),
    )
));

//Uncomment until we can verify WSSE auth is working on localhost
// $app->register(new CorsServiceProvider(), array(
//     'cors.allowOrigin' => $app['phpdraft.apiBaseUrl'], // your client application
//     'cors.exposeHeaders' => 'X-Total-Count', // ng-admin read this header
// ));