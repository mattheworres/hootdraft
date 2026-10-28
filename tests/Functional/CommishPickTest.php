<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/** The commissioner's pick entry routes (/commish/draft/{id}/pick*, picks/*, round/*). */
#[Group('database')]
class CommishPickTest extends CommishDraftTestCase
{
  private const PLAYER = ['first_name' => 'Antonio', 'last_name' => 'Brown', 'team' => 'PIT', 'position' => 'WR'];

  protected static function domain(): string
  {
    return 'commish-pick-test.example';
  }

  public function testRoutesNeedACommishSessionAndTheirOwnDraft(): void
  {
    [$draft, , , $picks] = $this->seedDraft();

    $this->assertSame(401, $this->call('GET', "/commish/draft/$draft/pick/current")->getStatusCode());
    $this->assertSame(401, $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", self::PLAYER)->getStatusCode());

    $this->createUser('Bob');
    $this->logIn('Bob');

    foreach ([
      ['GET', "/commish/draft/$draft/pick/current"],
      ['GET', "/commish/draft/$draft/picks/lastFive"],
      ['GET', "/commish/draft/$draft/round/1/picks"],
      ['GET', "/commish/draft/$draft/picks/alreadyDrafted?first_name=A&last_name=B"],
      ['POST', "/commish/draft/$draft/pick/{$picks[0]}"],
      ['PUT', "/commish/draft/$draft/pick/{$picks[0]}"],
    ] as [$method, $uri]) {
      $response = $this->call($method, $uri, $method === 'GET' ? null : self::PLAYER);
      $this->assertSame(400, $response->getStatusCode(), $uri);
      $this->assertSame([['success' => false, 'errors' => ['You do not have permission to this draft.']]], $this->json($response), $uri);
    }

    $this->assertNull($this->db()->fetchOne('SELECT pick_time FROM players WHERE player_id = ?', [$picks[0]]));
  }

  public function testRoutesNeedADraftInProgress(): void
  {
    [$draft, , , $picks] = $this->seedDraft('undrafted');
    $this->logIn('Alice');

    $response = $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", self::PLAYER);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertFalse($this->json($response)[0]['success']);
    $this->assertNull($this->db()->fetchOne('SELECT pick_time FROM players WHERE player_id = ?', [$picks[0]]));
  }

  public function testGetCurrentPickIncludesTheLookupData(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    $this->logIn('Alice');

    $response = $this->call('GET', "/commish/draft/$draft/pick/current");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertSame($picks[0], (int) $body['pick']['player_id']);
    $this->assertTrue($body['pick']['on_the_clock']);
    $this->assertArrayHasKey('PIT', $body['teams']);
    $this->assertArrayHasKey('WR', $body['positions']);
    $this->assertArrayHasKey('historical_teams', $body);
    $this->assertSame([], $body['last_5_picks']);
    $this->assertCount(4, $body['next_5_picks']);
  }

  public function testAddPickMovesTheDraftForward(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    $this->logIn('Alice');

    $response = $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", self::PLAYER);
    $this->assertSame(201, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertFalse($body['draft_is_complete']);
    $this->assertSame('Brown', $body['pick']['last_name']);
    $this->assertSame(1, (int) $body['pick']['player_counter']);
    $this->assertNotNull($body['pick']['pick_time']);
    $this->assertSame($picks[1], (int) $body['next_pick']['player_id']);
    $this->assertSame([$picks[0]], array_map('intval', array_column($body['last_5_picks'], 'player_id')));

    $row = $this->db()->fetchAssociative('SELECT * FROM players WHERE player_id = ?', [$picks[0]]);
    $this->assertSame('Antonio', $row['first_name']);
    $this->assertSame('PIT', $row['team']);
    $this->assertNotNull($row['pick_time']);

    $draftRow = $this->db()->fetchAssociative('SELECT * FROM draft WHERE draft_id = ?', [$draft]);
    $this->assertSame(2, (int) $draftRow['draft_current_pick']);
    $this->assertSame(1, (int) $draftRow['draft_counter']);
  }

  public function testAddPickRejectsInvalidPicks(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    [$otherDraft, , , $otherPicks] = $this->seedDraft();
    $this->logIn('Alice');

    $missing = $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", ['first_name' => 'Only']);
    $this->assertSame(400, $missing->getStatusCode());
    $this->assertContains('One or more missing fields.', $this->json($missing)['errors']);

    $notCurrent = $this->call('POST', "/commish/draft/$draft/pick/{$picks[1]}", self::PLAYER);
    $this->assertSame(400, $notCurrent->getStatusCode());
    $this->assertSame(["Pick #2 is not the current pick for draft #$draft."], $this->json($notCurrent)['errors']);

    $badTeam = $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", ['team' => 'XXX', 'position' => 'ZZ'] + self::PLAYER);
    $this->assertSame(['Team XXX is an invalid value.', 'Position ZZ is an invalid value.'], $this->json($badTeam)['errors']);

    $foreign = $this->call('POST', "/commish/draft/$draft/pick/{$otherPicks[0]}", self::PLAYER);
    $this->assertSame(400, $foreign->getStatusCode());
    $this->assertContains("Pick does not belong to draft #$draft.", $this->json($foreign)['errors']);

    $unknown = $this->call('POST', "/commish/draft/$draft/pick/999999999", self::PLAYER);
    $this->assertSame(400, $unknown->getStatusCode());
    $this->assertSame(['Unable to add pick #999999999'], $this->json($unknown)['errors']);

    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM players WHERE draft_id IN (?, ?) AND pick_time IS NOT NULL', [$draft, $otherDraft]));
    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT draft_counter FROM draft WHERE draft_id = ?', [$draft]));
  }

  public function testTheLastPickCompletesTheDraft(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    $this->logIn('Alice');

    foreach ($picks as $index => $pickId) {
      $response = $this->call('POST', "/commish/draft/$draft/pick/$pickId", self::PLAYER);
      $this->assertSame(201, $response->getStatusCode(), $response->getContent());
    }

    $body = $this->json($response);
    $this->assertTrue($body['draft_is_complete']);
    $this->assertNull($body['next_pick']);
    $this->assertSame($draft, (int) $body['draft_statistics']['draft_id']);
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM draft_stats WHERE draft_id = ?', [$draft]));
    $this->assertSame('complete', $this->db()->fetchOne('SELECT draft_status FROM draft WHERE draft_id = ?', [$draft]));
    $this->assertNotNull($this->db()->fetchOne('SELECT draft_end_time FROM draft WHERE draft_id = ?', [$draft]));

    // Completed drafts are no longer "in progress", so the commissioner routes close
    $after = $this->call('GET', "/commish/draft/$draft/pick/current");
    $this->assertSame(400, $after->getStatusCode());
  }

  public function testUpdatePickChangesThePlayerAndBumpsTheCounter(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    $this->logIn('Alice');
    $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", self::PLAYER);

    $response = $this->call('PUT', "/commish/draft/$draft/pick/{$picks[0]}", ['first_name' => 'Julio', 'last_name' => 'Jones', 'team' => 'ATL', 'position' => 'WR']);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertSame('Jones', $body['pick']['last_name']);
    $this->assertSame(2, (int) $body['pick']['player_counter']);
    $this->assertSame('ATL', $this->db()->fetchOne('SELECT team FROM players WHERE player_id = ?', [$picks[0]]));
    // Editing a pick leaves the draft where it was
    $this->assertSame(2, (int) $this->db()->fetchOne('SELECT draft_current_pick FROM draft WHERE draft_id = ?', [$draft]));

    $invalid = $this->call('PUT', "/commish/draft/$draft/pick/{$picks[0]}", ['first_name' => 'Julio', 'last_name' => 'Jones', 'team' => 'XXX', 'position' => 'WR']);
    $this->assertSame(400, $invalid->getStatusCode());
    $this->assertSame(['Team XXX is an invalid value.'], $this->json($invalid)['errors']);

    $unknown = $this->call('PUT', "/commish/draft/$draft/pick/999999999", self::PLAYER);
    $this->assertSame(['Unable to edit pick #999999999'], $this->json($unknown)['errors']);
  }

  public function testAlreadyDrafted(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    $this->logIn('Alice');
    $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", self::PLAYER);

    $match = $this->json($this->call('GET', "/commish/draft/$draft/picks/alreadyDrafted?first_name=Antonio&last_name=Brown"));
    $this->assertTrue($match['success']);
    $this->assertTrue($match['possibleMatchExists']);
    $this->assertSame([$picks[0]], array_map('intval', array_column($match['matches'], 'player_id')));
    $this->assertSame('Team A', $match['matches'][0]['manager_name']);

    $none = $this->json($this->call('GET', "/commish/draft/$draft/picks/alreadyDrafted?first_name=Nobody&last_name=Here"));
    $this->assertTrue($none['success']);
    $this->assertFalse($none['possibleMatchExists']);
    $this->assertSame([], $none['matches']);
  }

  public function testLastFiveAndRoundPicks(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    $this->logIn('Alice');
    $this->call('POST', "/commish/draft/$draft/pick/{$picks[0]}", self::PLAYER);
    $this->call('POST', "/commish/draft/$draft/pick/{$picks[1]}", ['last_name' => 'Jones'] + self::PLAYER);

    $last = $this->json($this->call('GET', "/commish/draft/$draft/picks/lastFive"));
    $this->assertTrue($last['success']);
    $this->assertSame(2, (int) $last['draft_rounds']);
    $this->assertSame([$picks[1], $picks[0]], array_map('intval', array_column($last['last_5_picks'], 'player_id')));

    $round = $this->json($this->call('GET', "/commish/draft/$draft/round/1/picks"));
    $this->assertTrue($round['success']);
    $this->assertSame(1, $round['round']);
    $this->assertSame([$picks[1], $picks[0]], array_map('intval', array_column($round['round_picks'], 'player_id')));
  }
}
