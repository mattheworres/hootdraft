<?php

namespace HootDraft\Domain\Services;

/** Generates the random 16 character tokens used for Email Verification Key and Reset Key links. */
class SaltService {
  public function GenerateSalt() {
    $length = 16;

    return substr(base64_encode(random_bytes($length)), 0, $length);
  }

  public function UrlDecodeSalt($encoded_salt_value) {
    return str_replace(' ', '+', urldecode($encoded_salt_value));
  }

  public function GenerateSaltForUrl() {
    $salt = $this->GenerateSalt();

    while (strpos($salt, '/') !== false) {
      $salt = $this->GenerateSalt();
    }

    return $salt;
  }
}
