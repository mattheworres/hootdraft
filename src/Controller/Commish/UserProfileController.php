<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\UserProfile;
use HootDraft\Domain\Services\LoginUserService;
use HootDraft\Domain\Services\UsersService;
use HootDraft\Domain\Validators\LoginUserValidator;
use HootDraft\Security\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserProfileController
{
  public function __construct(
    private CurrentUser $currentUser,
    private LoginUserValidator $validator,
    private LoginUserService $service,
    private UsersService $usersService,
  ) {
  }

  #[Route('/commish/profile', name: 'commish_profile_get', methods: ['GET'])]
  public function get(): JsonResponse
  {
    $user = $this->currentUser->loginUser();

    if (empty($user)) {
      return new JsonResponse(new HootDraftResponse(false));
    }

    return new JsonResponse(new UserProfile($user->id, $user->email, $user->name), Response::HTTP_OK);
  }

  #[Route('/commish/profile', name: 'commish_profile_put', methods: ['PUT'])]
  public function put(Request $request): JsonResponse
  {
    $payload = $request->getPayload();
    $user = $this->currentUser->loginUser();

    $email = $payload->getString('_email');
    $name = $payload->getString('_name');

    $validity = $this->validator->IsUserProfileUpdateValid(
      $user,
      $email,
      $name,
      $payload->getString('_password'),
      $payload->getString('_newPassword'),
      $payload->getString('_newConfirmedPassword'),
    );

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->UpdateUserProfile($user, $email, $name, $payload->getString('_newPassword'));

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/user/invite', name: 'commish_user_invite', methods: ['POST'])]
  public function inviteNewCommissioner(Request $request): JsonResponse
  {
    $payload = $request->getPayload();

    $user = new LoginUser();

    $user->enabled = 0;
    $user->email = strtolower($payload->getString('email'));
    $user->name = $payload->getString('name');

    $message = $payload->getString('message');

    $validity = $this->validator->IsInviteNewUserValid($user, $message);

    if (!$validity->success) {
      return new JsonResponse($validity, $validity->responseType());
    }

    $response = $this->usersService->InviteNewUser($user, $message);

    return new JsonResponse($response, $response->responseType());
  }
}
