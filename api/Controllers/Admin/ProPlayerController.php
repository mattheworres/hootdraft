<?php

namespace PhpDraft\Controllers\Admin;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Entities\Draft;

class ProPlayerController
{
  public function Upload(Application $app, Request $request) {
    $sport = $request->get('sport');
    $file = $request->files->get('csv_file');

    $validity = $app['phpdraft.ProPlayerValidator']->IsUploadSportValid($sport, $file);

    $response = $app['phpdraft.ProPlayerService']->Upload($sport, $file);

    return $app->json($response, $response->responseType());
  }
}