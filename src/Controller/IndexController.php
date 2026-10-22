<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftDataRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController
{
  public function __construct(private DraftDataRepository $draftData)
  {
  }

  #[Route('/style', name: 'style', methods: ['GET'])]
  public function style(): Response
  {
    $minified_css = '';

    foreach ($this->draftData->GetPositionColors() as $position => $hex_color_key) {
      $minified_css .= "div.pick$position{background-color:$hex_color_key;}";
    }

    return new Response($minified_css, 200, array("Content-Type" => "text/css"));
  }

  #[Route('/draftOptions', name: 'draft_options', methods: ['GET'])]
  public function draftOptions(): JsonResponse
  {
    $response = new HootDraftResponse(true);
    $response->sports = $this->draftData->GetSports();
    $response->statuses = $this->draftData->GetStatuses();

    return new JsonResponse($response, $response->responseType());
  }
}
