<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\ProPlayer;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\ProPlayerRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProPlayerService {
  public function __construct(
    private ProPlayerRepository $proPlayers,
    private \PDO $pdo,
    private LoggerInterface $logger,
    #[Autowire('%hootdraft.set_csv_timeout%')] private bool $setCsvTimeout,
  ) {
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

  /**
   * Replaces a league's players with the rows of a semicolon-separated CSV ("Last, First";POS;TEAM). The whole CSV is
   * parsed before the database is touched, and the delete + inserts are one transaction, so a bad file or a failure
   * part-way keeps the old list instead of leaving the league empty.
   */
  public function Upload($sport, UploadedFile $file) {
    $response = new HootDraftResponse();

    $tempName = $file->getRealPath();
    $pro_players = array();

    if ($tempName === false || ($handle = @fopen($tempName, 'r')) === false) {
      $response->success = false;
      $response->errors[] = "Files permission issue: unable to open CSV on server.";

      return $response;
    }

    if ($this->setCsvTimeout) {
      set_time_limit(0);
    }

    $isFirstRow = true;
    $rowNumber = 0;
    $badRows = array();

    while (($data = fgetcsv($handle, 1000, ';', '"', '')) !== false) {
      $rowNumber++;

      //A blank line comes back as [null]
      if ($data === array(null)) {
        continue;
      }

      //Excel on Windows saves Windows-1252, which MySQL's utf8 columns reject for accented names
      $data = array_map(fn ($cell) => $cell === null || mb_check_encoding($cell, 'UTF-8') ? $cell : mb_convert_encoding($cell, 'UTF-8', 'Windows-1252'), $data);

      if ($isFirstRow) {
        //Excel's UTF-8 CSVs start with a byte order mark
        $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);

        //Only the first row can be the header
        if ($data[0] == "Player") {
          $isFirstRow = false;
          continue;
        }

        $isFirstRow = false;
      }

      $new_player = new ProPlayer();

      $new_player->league = $sport;
      //Split on the first comma only, so "Smith Jr, John, III" keeps its suffix with the first name
      $name_column = explode(",", (string) $data[0], 2);

      if (count($name_column) != 2 || trim($name_column[0]) === '' || trim($name_column[1]) === '') {
        $badRows[] = $rowNumber;
        continue;
      }

      $new_player->last_name = trim($name_column[0]);
      $new_player->first_name = trim($name_column[1]);

      $new_player->position = isset($data[1]) ? trim($data[1]) : '';
      $new_player->team = isset($data[2]) ? trim($data[2]) : '';

      $pro_players[] = $new_player;
    }

    fclose($handle);

    if (count($badRows) > 0) {
      $response->success = false;
      $response->errors[] = "Rows " . implode(", ", array_slice($badRows, 0, 10)) . (count($badRows) > 10 ? ", ..." : "")
        . " are not in \"Last, First\" format, so the existing players were kept.";

      return $response;
    }

    if (count($pro_players) == 0) {
      $response->success = false;
      $response->errors[] = "The CSV has no players in it, so the existing players were kept.";

      return $response;
    }

    try {
      $this->pdo->beginTransaction();
      $this->proPlayers->SaveProPlayers($sport, $pro_players);
      $this->pdo->commit();

      $response->success = true;
    } catch (\Exception $e) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      $this->logger->error("Unable to save the $sport pro players: " . $e->getMessage(), ['exception' => $e]);

      $response->success = false;
      $response->errors[] = "Error encountered when updating new players to database.";
    }

    return $response;
  }
}
