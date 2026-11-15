<?php

namespace HootDraft\Domain\Models;

use Symfony\Component\HttpFoundation\Response;

// Services attach arbitrary result properties (users, commissioners, ...) before it is serialized to JSON.
#[\AllowDynamicProperties]
class HootDraftResponse {
  public function __construct($success = false, $errors = array()) {
    $this->success = $success;
    $this->errors = $errors;
  }

  public $success;
  public $errors;

  public function responseType($successResponse = Response::HTTP_OK) {
    return $this->success
      ? $successResponse
      : Response::HTTP_BAD_REQUEST;
  }
}
