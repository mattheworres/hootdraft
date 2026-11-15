<?php
namespace HootDraft\Domain\Services;

// Marks a database time as UTC so the AngularJS client displays it in the viewer's time zone
class ClientTimeFormatter {
  public function MarkAsUtc(?string $time_value): ?string {
    if (empty($time_value)) {
      return null;
    }

    if (str_ends_with($time_value, "UTC")) {
      return $time_value;
    }

    return $time_value . " UTC";
  }
}
