<?php

namespace PhpDraft\Controllers;

use \Silex\Application;
use \PhpDraft\Domain\Entities\Draft;

class IndexController
{
  public function Index(Application $app) {
    $stmt = $app['db']->prepare("SELECT * FROM draft ORDER BY draft_create_time");

    $stmt->setFetchMode(\PDO::FETCH_CLASS, 'Draft');
    $stmt->execute();

    while ($draft_row = $stmt->fetch())
      $drafts[] = $draft_row;

    return $app->json($drafts);
  }
}