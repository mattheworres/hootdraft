<?php

use Egulias\EmailValidator\EmailValidator;
use PhpDraft\Domain\Models\PhpDraftResponse;
use Symfony\Component\Security\Core\Util\StringUtils;

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//Factories
//Factories are services or classes that return a different instance each time its called.
$app['phpdraft.ResponseFactory'] = $app->factory(function() {
  return function($success, $errors) {
    return new PhpDraftResponse($success, $errors);
  };
});

$app['phpdraft.EmailValidator'] = $app->factory(function() {
  return new EmailValidator();
});

$app['phpdraft.StringsEqual'] = $app->factory(function() {
  return function($string1, $string2) {
    return StringUtils::equals($string1, $string2);
  };
});
