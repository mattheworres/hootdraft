<?php

namespace HootDraft\Controller\Admin;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Services\LoginUserService;
use HootDraft\Domain\Validators\LoginUserValidator;
use HootDraft\Security\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_ADMIN) comes from security.yaml. */
class UserController
{
  public function __construct(
    private LoginUserRepository $users,
    private LoginUserService $service,
    private LoginUserValidator $validator,
    private CurrentUser $currentUser,
  ) {
  }

  #[Route('/admin/users', name: 'admin_users_get', methods: ['GET'])]
  public function get(): JsonResponse
  {
    $response = $this->service->GetAll();

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/admin/user/{user_id}', name: 'admin_user_update', methods: ['PUT'])]
  public function update(Request $request, string $user_id): JsonResponse
  {
    $user = $this->load($user_id);
    //->all() rather than ->get(): get() throws on array values, which would skip our error response
    $payload = $request->getPayload()->all();

    $payloadValidity = $this->validator->IsAdminUserPayloadValid($payload);

    if (!$payloadValidity->success) {
      return new JsonResponse($payloadValidity, $payloadValidity->responseType());
    }

    $user->email = strtolower((string) ($payload['email'] ?? ''));
    $user->name = (string) ($payload['name'] ?? '');
    $user->roles = $payload['roles'] ?? '';

    //Coerce down to a 1 or 0 type
    $user->enabled = filter_var($payload['enabled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    $validity = $this->validator->IsAdminUserUpdateValid($user, $this->currentUser->get()?->getId());

    if (!$validity->success) {
      return new JsonResponse($validity, $validity->responseType());
    }

    $this->users->Update($user);

    $response = new HootDraftResponse(true, array());

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/admin/user/{user_id}', name: 'admin_user_delete', methods: ['DELETE'])]
  public function delete(string $user_id): JsonResponse
  {
    $user = $this->load($user_id);

    if ($this->service->IsAdmin($user)) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to delete user - user is admin.";

      return new JsonResponse($response, $response->responseType());
    }

    $response = $this->service->DeleteUser($user);

    return new JsonResponse($response, $response->responseType());
  }

  private function load(string $user_id)
  {
    try {
      return $this->users->LoadById((int) $user_id);
    } catch (\Exception $e) {
      throw new NotFoundHttpException("User does not exist.");
    }
  }
}
