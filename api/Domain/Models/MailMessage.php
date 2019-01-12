<?php

namespace PhpDraft\Domain\Models;

//TODO: Implement more of PHPMailer's features here. Just doing bare minimum functionality right now

class MailMessage {
  public function __construct() {
    $this->to_addresses = array();
    $this->cc_addresses = array();
    $this->bcc_addresses = array();
    $this->is_html = false;
  }

  public $to_addresses;
  public $cc_addresses;
  public $bcc_addresses;
  public $subject;
  public $body;
  public $is_html;
}
