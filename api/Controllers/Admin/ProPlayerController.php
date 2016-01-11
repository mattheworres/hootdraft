<?php

namespace PhpDraft\Controllers\Admin;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Entities\Draft;

class ProPlayerController
{
  public function GetSports(Application $app, Request $request) {
    $sports = $app['phpdraft.DraftDataRepository']->GetSports();

    return $app->json($sports, Response::HTTP_OK);
  }

  public function Upload(Application $app, Request $request) {
    $sport = $request->get('sport');
    $file = $request->files->get('file');

    $validity = $app['phpdraft.ProPlayerValidator']->IsUploadSportValid($sport, $file);

    if(!$validity->success) {
      return $app->json($validity, $validity->responseType());
    }

    $response = $app['phpdraft.ProPlayerService']->Upload($sport, $file);

    return $app->json($response, $response->responseType());
  }
}