<?php

namespace PhpDraft\Config\Security;

class SaltService {
  public function GenerateSalt() {
    //Special thanks: http://stackoverflow.com/a/18899561/324527
    $length = 16;
    return substr(base64_encode(openssl_random_pseudo_bytes($length)), 0, $length);
  }

  public function UrlDecodeSalt($encoded_salt_value) {
    return str_replace(' ', '+', urldecode($encoded_salt_value));
  }

  public function GenerateSaltForUrl() {
    $salt = $this->GenerateSalt();

    while (strpos($salt, '/') != 0) {
      $salt = $this->GenerateSalt();
    }

    return $salt;
  }
}
