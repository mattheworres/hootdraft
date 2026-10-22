<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\ProPlayer;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ProPlayerService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function SearchPlayersManual($league, $first, $last, $team, $position) {
    $response = new PhpDraftResponse();

    try {
      $players = $this->app['phpdraft.ProPlayerRepository']->SearchPlayersManual($league, $first, $last, $team, $position);

      $response->success = true;
      $response->players = $players;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function SearchPlayers($league, $searchTerm) {
    $response = new PhpDraftResponse();

    try {
      $players = $this->app['phpdraft.ProPlayerRepository']->SearchPlayers($league, $searchTerm);

      #If there's a space and no matches so far, create another searches where we manually split them firstname/lastname by sace automatically
      $split_name_automatically = count($players) == 0 && strpos($searchTerm, " ") != false;

      if ($split_name_automatically) {
        $names = explode(" ", $searchTerm, 2);
        $players = $this->app['phpdraft.ProPlayerRepository']->SearchPlayersByAssumedName($league, $names[0], $names[1]);
      }

      $response->success = true;
      $response->players = $players;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function Upload($sport, &$file) {
    $response = new PhpDraftResponse();

    $tempName = $file->getRealPath();
    $pro_players = array();

    if (($handle = fopen($tempName, 'r')) == FALSE) {
      $response->success = false;
      $response->errors[] = "Files permission issue: unable to open CSV on server.";

      return $response;
    }

    if (SET_CSV_TIMEOUT) {
      set_time_limit(0);
    }

    while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
      if ($data[0] == "Player") {
        continue;
      }

      $new_player = new ProPlayer();

      $new_player->league = $sport;
      $name_column = explode(",", $data[0]);

      if (count($name_column) == 2) {
        $new_player->last_name = trim($name_column[0]);
        $new_player->first_name = trim($name_column[1]);
      } else {
        $new_player->last_name = "Player";
        $new_player->first_name = "Unknown";
      }

      $new_player->position = isset($data[1]) ? trim($data[1]) : '';
      $new_player->team = isset($data[2]) ? trim($data[2]) : '';

      $pro_players[] = $new_player;
    }

    fclose($handle);

    try {
      $this->app['phpdraft.ProPlayerRepository']->SaveProPlayers($sport, $pro_players);
      $response->success = true;
    } catch (Exception $e) {
      $response->success = false;
      $response->errors[] = "Error encountered when updating new players to database: " . $e->getMessage();
    }

    return $response;
  }
}
