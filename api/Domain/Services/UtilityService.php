<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;

class UtilityService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function ConvertTimeForClientDisplay($time_value) {
    if(empty($time_value)) {
      return null;
    }

    $time_value_length = strlen($time_value);
    $supposed_utc_index = $time_value_length > 3 ? $time_value_length - 3 : 0;
    $utc_index = strstr($time_value, "UTC");

    if($utc_index == $supposed_utc_index) {
      return $time_value;
    } else {
      return $time_value . " UTC";
    }
  }
}