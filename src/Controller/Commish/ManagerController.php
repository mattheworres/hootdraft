<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Services\ManagerService;
use HootDraft\Domain\Validators\ManagerValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_COMMISH) comes from security.yaml; ownership of the draft from the commishEditableDraft check. */
class ManagerController
{
  public function __construct(
    private DraftRepository $drafts,
    private ManagerRepository $managers,
    private ManagerValidator $validator,
    private ManagerService $service,
  ) {
  }

  #[Route('/commish/draft/{draft_id}/managers', name: 'commish_managers_get', methods: ['GET'], defaults: ['_checks' => ['commishEditableDraft']])]
  public function get(string $draft_id): JsonResponse
  {
    return new JsonResponse($this->managers->GetManagersByDraftOrder((int) $draft_id), Response::HTTP_OK);
  }

  #[Route('/commish/draft/{draft_id}/manager', name: 'commish_manager_create', methods: ['POST'], defaults: ['_checks' => ['commishEditableDraft', 'draftSettingUpOrInProgress']])]
  public function create(Request $request, string $draft_id): JsonResponse
  {
    $draftId = (int) $draft_id;

    $manager = new Manager();
    $manager->draft_id = $draftId;
    $manager->manager_name = $request->request->get('manager_name');

    $validity = $this->validator->IsManagerValidForCreate($draftId, $manager);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->CreateNewManager($manager);

    return new JsonResponse($response, $response->responseType(Response::HTTP_CREATED));
  }

  #[Route('/commish/draft/{draft_id}/managers', name: 'commish_managers_create_many', methods: ['POST'], defaults: ['_checks' => ['commishEditableDraft', 'draftSettingUpOrInProgress']])]
  public function createMany(Request $request, string $draft_id): JsonResponse
  {
    $draftId = (int) $draft_id;

    $newManagers = array();

    $managersRequest = $request->request->all()['managers'] ?? null;

    if (!is_array($managersRequest) || count($managersRequest) == 0) {
      return new JsonResponse(new HootDraftResponse(false, ['At least one manager is required.']), Response::HTTP_BAD_REQUEST);
    }

    foreach ($managersRequest as $managerRequest) {
      $newManager = new Manager();
      $newManager->draft_id = $draftId;
      $newManager->manager_name = is_array($managerRequest) ? ($managerRequest['manager_name'] ?? null) : null;

      $newManagers[] = $newManager;
    }

    $validity = $this->validator->AreManagersValidForCreate($draftId, $newManagers);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->CreateManyManagers($draftId, $newManagers);

    return new JsonResponse($response, $response->responseType(Response::HTTP_CREATED));
  }

  #[Route('/commish/draft/{draft_id}/managers/reorder', name: 'commish_managers_reorder', methods: ['PUT'], defaults: ['_checks' => ['commishEditableDraft', 'draftSettingUpOrInProgress']])]
  public function reorder(Request $request, string $draft_id): JsonResponse
  {
    $draftId = (int) $draft_id;

    $managerIds = array();

    $orderedIds = $request->request->all()['ordered_manager_ids'] ?? [];

    foreach (is_array($orderedIds) ? $orderedIds : [] as $managerId) {
      $managerIds[] = (int) $managerId;
    }

    $validity = $this->validator->AreManagerIdsValidForOrdering($draftId, $managerIds);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->ReorderManagers($managerIds);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}/manager/{manager_id}', name: 'commish_manager_update', methods: ['PUT'], defaults: ['_checks' => ['commishEditableDraft', 'draftSettingUpOrInProgress']])]
  public function update(Request $request, string $draft_id, string $manager_id): JsonResponse
  {
    try {
      $draft = $this->drafts->Load((int) $draft_id);
      $manager = $this->managers->Load($manager_id);
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$manager_id";

      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    // Create sends 'manager_name'; 'name' is what the client has always sent for an update, so accept both
    $manager->manager_name = $request->request->get('manager_name') ?? $request->request->get('name');

    $validity = $this->validator->IsManagerValidForUpdate($draft, $manager);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->UpdateManager($manager);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}/manager/{manager_id}', name: 'commish_manager_delete', methods: ['DELETE'], defaults: ['_checks' => ['commishEditableDraft', 'draftSettingUpOrInProgress']])]
  public function delete(string $draft_id, string $manager_id): JsonResponse
  {
    try {
      $manager = $this->managers->Load($manager_id);
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$manager_id";

      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    if ($manager->draft_id != (int) $draft_id) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$manager_id";

      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->DeleteManager($manager);

    return new JsonResponse($response, $response->responseType());
  }
}
