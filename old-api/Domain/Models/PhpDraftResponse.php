<?php

namespace PhpDraft\Domain\Models;

use Symfony\Component\HttpFoundation\Response;

class PhpDraftResponse {
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