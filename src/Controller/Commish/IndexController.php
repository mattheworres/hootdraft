<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Security\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController
{
  public function __construct(private CurrentUser $currentUser)
  {
  }

  #[Route('/commish', name: 'commish_index', methods: ['GET'])]
  public function index(): JsonResponse
  {
    $user = $this->currentUser->get();

    $response = array(
      "authenticated" => $user !== null,
      "roles" => $user?->getRoles() ?? array(),
    );

    return new JsonResponse($response, Response::HTTP_OK);
  }
}
