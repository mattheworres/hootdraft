<?php

namespace PhpDraft\Domain\Models;

//TODO: Implement more of PHPMailer's features here. Just doing bare minimum functionality right now

class MailMessage {
  public function __construct() {
    $this->to_addresses = array();
    //$this->to_addresses = array (
    //  "user@example.com"ß
    //)
    $this->is_html = false;
  }

  public $to_addresses;
  public $subject;
  public $body;
  public $is_html;
}