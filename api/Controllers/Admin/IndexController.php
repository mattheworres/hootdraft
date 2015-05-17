<?php

namespace PhpDraft\Controllers\Admin;

use \Silex\Application;

class IndexController
{
	public function Index(Application $app) {
    //$drafts = \DraftQuery::create()->find();

    //How current user is accessed: --Important, cant lock down by role at security level
    // $token = $app['security']->getToken();

    // if($token !== null) {
    //   $usr = $token->getUser();
    //   return $app->json($usr->getUsername());
    // }

    $stmt = $app['db']->prepare("SELECT * FROM draft ORDER BY draft_create_time");

    $stmt->setFetchMode(\PDO::FETCH_CLASS, 'Draft');
    $stmt->execute();

    while ($draft_row = $stmt->fetch())
      $drafts[] = $draft_row;

		return $app->json($drafts);
	}
}