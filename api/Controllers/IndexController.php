<?php

namespace PhpDraft\Controllers;

use \Silex\Application;

class IndexController
{
	public function Index(Application $app) {
		$drafts = \DraftQuery::create()->find();
		return $app->json($drafts->toArray());
	}
}