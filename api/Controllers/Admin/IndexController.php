<?php

namespace PhpDraft\Controllers\Admin;

use \Silex\Application;

class IndexController
{
	public function Index(Application $app) {
    $drafts = \DraftQuery::create()->find();
		return $app->json($drafts->toArray());
	}
}