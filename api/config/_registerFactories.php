<?php

use PhpDraft\Domain\Models\PhpDraftResponse;

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//Factories
//Factories are services or classes that return a different instance each time its called.
// $app['phpdraft.ResponseFactory'] = $app->factory(function () use ($app) {
//   return new PhpDraftResponse();
// });
$app['phpdraft.ResponseFactory'] = $app->factory(function() {
  return function($success, $errors) {
    return new PhpDraftResponse($success, $errors);
  };
});

