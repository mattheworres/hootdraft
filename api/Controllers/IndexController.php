<?php

namespace PhpDraft\Controllers;

use Base\DraftQuery;

class IndexController
{
	public function __construct(/*dependncies injected here*/) {

	}

	public function Index() {
		$drafts = DraftQuery::create()->find();
		
		return $app->json($drafts);
	}
}