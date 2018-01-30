<?php

namespace PhpDraft\Config\Security;

class SaltService {
  public function GenerateSalt() {
    //Special thanks: http://stackoverflow.com/a/18899561/324527
    $length = 16;
    return substr(base64_encode(mcrypt_create_iv(ceil(0.75*$length), MCRYPT_DEV_URANDOM)), 0, $length);
  }

  public function UrlDecodeSalt($encoded_salt_value) {
    return str_replace(' ', '+', urldecode($encoded_salt_value));
  }

  public function GenerateSaltForUrl() {
    $salt = $this->GenerateSalt();

    while(strpos($salt, '/') != 0) {
      $salt = $this->GenerateSalt();
    }

    return $salt;
  }
}
