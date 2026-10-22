<?php

namespace HootDraft\Http\Check;

use HootDraft\Domain\Models\HootDraftResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shared plumbing for the checks that guard a `{draft_id}` route. A failed check answers like the old
 * Silex closures did: the failing HootDraftResponse wrapped in a one-element array, with its 400 status.
 */
abstract class DraftCheck implements RouteCheck
{
  protected function draftId(Request $request): int
  {
    return (int) $request->attributes->get('draft_id');
  }

  protected function failure(HootDraftResponse $response): JsonResponse
  {
    return new JsonResponse([$response], $response->responseType());
  }
}
