<?php
namespace HootDraft\Domain\Services;

class UtilityService {
  public function ConvertTimeForClientDisplay($time_value) {
    if (empty($time_value)) {
      return null;
    }

    if (str_ends_with($time_value, "UTC")) {
      return $time_value;
    }

    return $time_value . " UTC";
  }
}
