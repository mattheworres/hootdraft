<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\ProPlayerRepository;

// Upload (the admin CSV import) arrives with the admin routes in 4.6. See old-api for the original.
class ProPlayerService {
  public function __construct(private ProPlayerRepository $proPlayers) {
  }

  public function SearchPlayersManual($league, $first, $last, $team, $position) {
    $response = new HootDraftResponse();

    try {
      $players = $this->proPlayers->SearchPlayersManual($league, $first, $last, $team, $position);

      $response->success = true;
      $response->players = $players;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function SearchPlayers($league, $searchTerm) {
    $response = new HootDraftResponse();

    try {
      $players = $this->proPlayers->SearchPlayers($league, $searchTerm);

      #If there's a space and no matches so far, create another searches where we manually split them firstname/lastname by sace automatically
      $split_name_automatically = count($players) == 0 && strpos($searchTerm, " ") != false;

      if ($split_name_automatically) {
        $names = explode(" ", $searchTerm, 2);
        $players = $this->proPlayers->SearchPlayersByAssumedName($league, $names[0], $names[1]);
      }

      $response->success = true;
      $response->players = $players;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }
}
