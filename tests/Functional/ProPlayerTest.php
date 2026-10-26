<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/** The commissioner's pro player autocomplete (/commish/proplayers/search). */
#[Group('database')]
class ProPlayerTest extends CommishDraftTestCase
{
  protected static function domain(): string
  {
    return 'proplayer-test.example';
  }

  protected function setUp(): void
  {
    parent::setUp();
    $this->db()->executeStatement("DELETE FROM pro_players WHERE last_name LIKE 'Zzqx%'");

    foreach ([['Anton', 'Zzqxone', 'WR', 'PIT'], ['Bella', 'Zzqxtwo', 'QB', 'ATL'], ['Anton', 'Zzqxthree', 'QB', 'PIT']] as [$first, $last, $position, $team]) {
      $this->db()->insert('pro_players', ['league' => 'NFL', 'first_name' => $first, 'last_name' => $last, 'position' => $position, 'team' => $team]);
    }
  }

  protected function tearDown(): void
  {
    $this->db()->executeStatement("DELETE FROM pro_players WHERE last_name LIKE 'Zzqx%'");
    parent::tearDown();
  }

  public function testSearchNeedsACommishSession(): void
  {
    $this->assertSame(401, $this->call('GET', '/commish/proplayers/search?league=NFL&searchTerm=Zzqx')->getStatusCode());

    $this->createUser('Bob', 'ROLE_USER');
    $this->logIn('Bob');
    $this->assertSame(403, $this->call('GET', '/commish/proplayers/search?league=NFL&searchTerm=Zzqx')->getStatusCode());
  }

  public function testSearchByTerm(): void
  {
    $this->createUser('Alice');
    $this->logIn('Alice');

    $response = $this->call('GET', '/commish/proplayers/search?league=NFL&searchTerm=Zzqxtwo');
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertSame(['Zzqxtwo'], array_column($body['players'], 'last_name'));
    $this->assertSame(['pro_player_id', 'league', 'first_name', 'last_name', 'position', 'team'], array_keys($body['players'][0]));
  }

  public function testSearchSplitsAFullNameWhenTheTermFindsNothing(): void
  {
    $this->createUser('Alice');
    $this->logIn('Alice');

    $body = $this->json($this->call('GET', '/commish/proplayers/search?league=NFL&searchTerm=' . rawurlencode('Anton Zzqxthree')));

    $this->assertTrue($body['success']);
    $this->assertSame(['Zzqxthree'], array_column($body['players'], 'last_name'));
  }

  public function testManualSearchFiltersByEachField(): void
  {
    $this->createUser('Alice');
    $this->logIn('Alice');

    $byTeam = $this->json($this->call('GET', '/commish/proplayers/search?league=NFL&last=Zzqx&team=PIT'));
    $this->assertEqualsCanonicalizing(['Zzqxone', 'Zzqxthree'], array_column($byTeam['players'], 'last_name'));

    $byPosition = $this->json($this->call('GET', '/commish/proplayers/search?league=NFL&first=Anton&last=Zzqx&position=QB'));
    $this->assertSame(['Zzqxthree'], array_column($byPosition['players'], 'last_name'));

    $otherLeague = $this->json($this->call('GET', '/commish/proplayers/search?league=NHL&last=Zzqx'));
    $this->assertTrue($otherLeague['success']);
    $this->assertSame([], $otherLeague['players']);
  }
}
