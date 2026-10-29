<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/** Public manager list, commissioner manager CRUD and the depth chart position lookup, against the migrated test DB. */
#[Group('database')]
class ManagerTest extends CommishDraftTestCase
{
  private const PASSWORD_HEADER = 'HTTP_X_PHPDRAFT_DRAFTPASSWORD';

  protected static function domain(): string
  {
    return 'manager-test.example';
  }

  private function names(int $draftId): array
  {
    return $this->db()->fetchFirstColumn('SELECT manager_name FROM managers WHERE draft_id = ? ORDER BY draft_order', [$draftId]);
  }

  public function testPublicManagerListIsOrderedAndRespectsPrivateDrafts(): void
  {
    $alice = $this->createUser('Alice');
    $open = $this->createDraft($alice, 'undrafted');
    $this->createManager($open, 'Second', 2);
    $this->createManager($open, 'First', 1);
    $private = $this->createDraft($alice, 'undrafted', 'secret');
    $this->createManager($private, 'Hidden', 1);

    $managers = $this->json($this->call('GET', "/draft/$open/managers"));
    $this->assertSame(['First', 'Second'], array_column($managers, 'manager_name'));
    $this->assertSame(['manager_id', 'draft_id', 'manager_name', 'draft_order'], array_keys($managers[0]));

    $blocked = $this->call('GET', "/draft/$private/managers");
    $this->assertSame(400, $blocked->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($blocked));

    $unlocked = $this->call('GET', "/draft/$private/managers", null, [self::PASSWORD_HEADER => 'secret']);
    $this->assertSame(['Hidden'], array_column($this->json($unlocked), 'manager_name'));
  }

  public function testCommishRoutesNeedASessionAndOwnership(): void
  {
    $alice = $this->createUser('Alice');
    $this->createUser('Bob');
    $id = $this->createDraft($alice, 'undrafted');

    $this->assertSame(401, $this->call('GET', "/commish/draft/$id/managers")->getStatusCode());

    $this->logInSuccessfully('Bob');
    $denied = $this->call('POST', "/commish/draft/$id/manager", ['manager_name' => 'Intruder']);
    $this->assertSame(400, $denied->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['You do not have permission to this draft.']]], $this->json($denied));
    $this->assertSame([], $this->names($id));
  }

  public function testCreateManager(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted');
    $this->logInSuccessfully('Alice');

    $created = $this->call('POST', "/commish/draft/$id/manager", ['manager_name' => 'Zed']);
    $this->assertSame(201, $created->getStatusCode(), $created->getContent());
    $body = $this->json($created);
    $this->assertTrue($body['success']);
    $this->assertSame('Zed', $body['manager']['manager_name']);
    // The response echoes the entity as given (no draft_order), the order is assigned in the database
    $this->assertNull($body['manager']['draft_order']);

    $this->assertTrue($this->json($this->call('POST', "/commish/draft/$id/manager", ['manager_name' => 'Yan']))['success']);
    $this->assertSame([1, 2], array_map('intval', $this->db()->fetchFirstColumn('SELECT draft_order FROM managers WHERE draft_id = ? ORDER BY draft_order', [$id])));

    $duplicate = $this->call('POST', "/commish/draft/$id/manager", ['manager_name' => 'zed']);
    $this->assertSame(400, $duplicate->getStatusCode());
    $this->assertSame(["Manager name 'zed' already exists - please choose another name."], $this->json($duplicate)['errors']);

    $missing = $this->call('POST', "/commish/draft/$id/manager", []);
    $this->assertSame(['One or more missing fields.'], $this->json($missing)['errors']);

    $this->assertSame(['Zed', 'Yan'], array_column($this->json($this->call('GET', "/commish/draft/$id/managers")), 'manager_name'));
  }

  public function testCreateManyReorderUpdateAndDelete(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted');
    $this->logInSuccessfully('Alice');

    $created = $this->call('POST', "/commish/draft/$id/managers", ['managers' => [['manager_name' => 'A'], ['manager_name' => 'B'], ['manager_name' => 'C']]]);
    $this->assertSame(201, $created->getStatusCode(), $created->getContent());
    $ids = array_column($this->json($created)['managers'], 'manager_id');
    $this->assertCount(3, $ids);
    $this->assertSame(['A', 'B', 'C'], $this->names($id));

    $duplicates = $this->call('POST', "/commish/draft/$id/managers", ['managers' => [['manager_name' => 'D'], ['manager_name' => 'D']]]);
    $this->assertSame(400, $duplicates->getStatusCode());

    $reordered = $this->call('PUT', "/commish/draft/$id/managers/reorder", ['ordered_manager_ids' => [$ids[2], $ids[0], $ids[1]]]);
    $this->assertSame(200, $reordered->getStatusCode(), $reordered->getContent());
    $this->assertSame(['C', 'A', 'B'], $this->names($id));

    $updated = $this->call('PUT', "/commish/draft/$id/manager/{$ids[0]}", ['name' => 'Renamed']);
    $this->assertSame(200, $updated->getStatusCode(), $updated->getContent());
    $this->assertSame('Renamed', $this->json($updated)['manager']['manager_name']);

    $clash = $this->call('PUT', "/commish/draft/$id/manager/{$ids[0]}", ['name' => 'b']);
    $this->assertSame(400, $clash->getStatusCode());

    $deleted = $this->call('DELETE', "/commish/draft/$id/manager/{$ids[2]}");
    $this->assertSame(200, $deleted->getStatusCode(), $deleted->getContent());
    $this->assertSame(['Renamed', 'B'], $this->names($id));
    $this->assertSame([1, 2], array_column($this->json($deleted)['managers'], 'draft_order'));
  }

  public function testUpdateAndDeleteRefuseAManagerFromAnotherDraft(): void
  {
    $alice = $this->createUser('Alice');
    $mine = $this->createDraft($alice, 'undrafted');
    $other = $this->createDraft($alice, 'undrafted');
    $foreign = $this->createManager($other, 'Foreign', 1);
    $this->logInSuccessfully('Alice');

    $delete = $this->call('DELETE', "/commish/draft/$mine/manager/$foreign");
    $this->assertSame(400, $delete->getStatusCode());
    $this->assertSame(["Unable to delete manager #$foreign"], $this->json($delete)['errors']);

    $update = $this->call('PUT', "/commish/draft/$mine/manager/$foreign", ['name' => 'Taken over']);
    $this->assertSame(400, $update->getStatusCode());
    $this->assertSame(['Foreign'], $this->names($other));

    $missing = $this->call('DELETE', "/commish/draft/$mine/manager/999999999");
    $this->assertSame(["Unable to delete manager #999999999"], $this->json($missing)['errors']);
  }

  public function testManagerChangesAreBlockedOnceTheDraftIsComplete(): void
  {
    $alice = $this->createUser('Alice');
    $done = $this->createDraft($alice, 'complete');
    $manager = $this->createManager($done, 'Locked in', 1);
    $this->logInSuccessfully('Alice');

    $blocked = $this->call('POST', "/commish/draft/$done/manager", ['manager_name' => 'Late']);
    $this->assertSame(400, $blocked->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ["Unable to work on draft #$done: draft is completed"]]], $this->json($blocked));
    $this->assertSame(400, $this->call('DELETE', "/commish/draft/$done/manager/$manager")->getStatusCode());
    $this->assertSame(['Locked in'], $this->names($done));

    // Reading is still fine
    $this->assertSame(200, $this->call('GET', "/commish/draft/$done/managers")->getStatusCode());
  }

  public function testWildcardCharactersInANameAreNotMatchedAgainstOtherNames(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted');
    $this->createManager($id, 'Bob', 1);
    $this->logInSuccessfully('Alice');

    foreach (['%', 'B_b', 'B%'] as $name) {
      $created = $this->call('POST', "/commish/draft/$id/manager", ['manager_name' => $name]);
      $this->assertSame(201, $created->getStatusCode(), "$name: " . $created->getContent());
    }

    $this->assertSame(['Bob', '%', 'B_b', 'B%'], $this->names($id));
    $this->assertSame(400, $this->call('POST', "/commish/draft/$id/manager", ['manager_name' => 'BOB'])->getStatusCode());
  }

  public function testCreateManyRejectsEmptyMalformedAndCaseInsensitiveDuplicateLists(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted');
    $this->logInSuccessfully('Alice');

    foreach ([[], ['managers' => []], ['managers' => 'Bob'], ['managers' => ['Bob']]] as $body) {
      $response = $this->call('POST', "/commish/draft/$id/managers", $body);
      $this->assertSame(400, $response->getStatusCode(), json_encode($body));
    }
    $this->assertSame(['At least one manager is required.'], $this->json($this->call('POST', "/commish/draft/$id/managers", []))['errors']);

    $duplicates = $this->call('POST', "/commish/draft/$id/managers", ['managers' => [['manager_name' => 'Bob'], ['manager_name' => 'bob']]]);
    $this->assertSame(400, $duplicates->getStatusCode());
    $this->assertSame([], $this->names($id));
  }

  public function testReorderRejectsTheSameManagerTwice(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted');
    $first = $this->createManager($id, 'First', 1);
    $this->createManager($id, 'Second', 2);
    $this->logInSuccessfully('Alice');

    $response = $this->call('PUT', "/commish/draft/$id/managers/reorder", ['ordered_manager_ids' => [$first, $first]]);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['First', 'Second'], $this->names($id));
  }

  public function testUpdateAcceptsEitherNameKey(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted');
    $manager = $this->createManager($id, 'First', 1);
    $this->logInSuccessfully('Alice');

    $this->assertSame(200, $this->call('PUT', "/commish/draft/$id/manager/$manager", ['manager_name' => 'Renamed'])->getStatusCode());
    $this->assertSame(['Renamed'], $this->names($id));

    $this->assertSame(200, $this->call('PUT', "/commish/draft/$id/manager/$manager", ['name' => 'Again'])->getStatusCode());
    $this->assertSame(['Again'], $this->names($id));
  }

  public function testTheTwentyManagerLimitHoldsAtTheDatabase(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted');
    for ($i = 1; $i <= 19; $i++) {
      $this->createManager($id, "M$i", $i);
    }
    $this->logInSuccessfully('Alice');

    $this->assertSame(201, $this->call('POST', "/commish/draft/$id/manager", ['manager_name' => 'Twentieth'])->getStatusCode());
    $over = $this->call('POST', "/commish/draft/$id/manager", ['manager_name' => 'Twenty-first']);
    $this->assertSame(400, $over->getStatusCode());
    $this->assertCount(20, $this->names($id));
  }

  public function testDepthChartPositionsForASport(): void
  {
    $this->createUser('Alice');

    $this->assertSame(401, $this->call('GET', '/commish/depthchartposition/positions?draft_sport=NFL')->getStatusCode());

    $this->logInSuccessfully('Alice');
    $ok = $this->json($this->call('GET', '/commish/depthchartposition/positions?draft_sport=NFL'));
    $this->assertTrue($ok['success']);
    $this->assertSame('Quarterback', $ok['positions']['QB']);

    $bad = $this->call('GET', '/commish/depthchartposition/positions?draft_sport=XYZ');
    $this->assertSame(400, $bad->getStatusCode());
    $this->assertSame(['Draft sport is an invalid value.'], $this->json($bad)['errors']);
  }
}
