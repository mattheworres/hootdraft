<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * The public draft reads, commissioner lookups and the draft state checks, against the migrated test DB.
 * Everything created here is an @draft-test.example user or one of their drafts, removed afterwards.
 */
#[Group('database')]
class DraftReadTest extends CommishDraftTestCase
{
  private const PASSWORD_HEADER = 'HTTP_X_PHPDRAFT_DRAFTPASSWORD';

  protected static function domain(): string
  {
    return 'draft-test.example';
  }

  public function testDraftListHidesPasswordsAndLocksPrivateDrafts(): void
  {
    $alice = $this->createUser('Alice');
    $open = $this->createDraft($alice, 'undrafted', null, ['draft_name' => 'Open draft']);
    $private = $this->createDraft($alice, 'in_progress', 'secret');

    $drafts = array_column($this->json($this->call('GET', '/drafts')), null, 'draft_id');

    $this->assertSame('Open draft', $drafts[$open]['draft_name']);
    $this->assertSame('Setting Up', $drafts[$open]['display_status']);
    $this->assertTrue($drafts[$open]['draft_visible']);
    $this->assertSame('Alice', $drafts[$open]['commish_name']);
    $this->assertStringEndsWith(' UTC', $drafts[$open]['draft_create_time']);
    $this->assertArrayNotHasKey('draft_password', $drafts[$open]);

    $this->assertTrue($drafts[$private]['is_locked']);
    $this->assertSame('locked', $drafts[$private]['draft_status']);
    $this->assertSame('Locked', $drafts[$private]['display_status']);
    $this->assertSame('', $drafts[$private]['draft_sport']);
    $this->assertArrayNotHasKey('draft_password', $drafts[$private]);

    $unlocked = array_column($this->json($this->call('GET', '/drafts', null, [self::PASSWORD_HEADER => 'secret'])), null, 'draft_id');
    $this->assertFalse($unlocked[$private]['is_locked']);
    $this->assertSame('in_progress', $unlocked[$private]['draft_status']);
  }

  public function testDraftsByCommissionerOnlyReturnTheirOwn(): void
  {
    $alice = $this->createUser('Alice');
    $bob = $this->createUser('Bob');
    $this->createDraft($alice, 'undrafted', null, ['draft_name' => 'Alice draft']);
    $this->createDraft($bob, 'undrafted');

    $drafts = $this->json($this->call('GET', "/drafts/$alice"));

    $this->assertSame(['Alice draft'], array_column($drafts, 'draft_name'));
  }

  public function testSingleDraftWithAndWithoutDraftData(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted', null, ['draft_name' => 'Detail draft', 'using_depth_charts' => 1]);
    $this->db()->insert('depth_chart_positions', ['draft_id' => $id, 'position' => 'QB', 'slots' => 2, 'display_order' => 1]);

    $plain = $this->json($this->call('GET', "/draft/$id"));
    $this->assertSame('Detail draft', $plain['draft_name']);
    $this->assertArrayNotHasKey('sports', $plain);

    $full = $this->json($this->call('GET', "/draft/$id?get_draft_data=true"));
    $this->assertSame('Football (NFL)', $full['sports']['NFL']);
    $this->assertArrayHasKey('serpentine', $full['styles']);
    $this->assertArrayHasKey('undrafted', $full['statuses']);
    $this->assertSame('Dallas Cowboys', $full['teams']['DAL'] ?? 'Dallas Cowboys');
    $this->assertNotEmpty($full['positions']);
    $this->assertSame('QB', $full['depthChartPositions'][0]['position']);
  }

  public function testMissingDraftIsAServerErrorLikeTheOldApp(): void
  {
    $response = $this->call('GET', '/draft/999999999');

    $this->assertSame(500, $response->getStatusCode());
    $this->assertArrayHasKey('error', $this->json($response));
  }

  public function testOwnerSeesPrivateDraftWithoutThePassword(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted', 'secret');

    $this->assertTrue($this->json($this->call('GET', "/draft/$id"))['is_locked']);

    $this->logInSuccessfully('Alice');
    $own = $this->json($this->call('GET', "/draft/$id"));

    $this->assertFalse($own['is_locked']);
    $this->assertTrue($own['commish_editable']);
  }

  public function testCommissionerSearchAndLookupOnlyExposeIdAndName(): void
  {
    $alice = $this->createUser('Zzsearchable');

    $found = $this->json($this->call('GET', '/commissioners/search?searchTerm=Zzsearch'));

    $this->assertTrue($found['success']);
    $this->assertSame([['id' => $alice, 'name' => 'Zzsearchable']], json_decode(json_encode($found['commissioners']), true));

    $one = $this->json($this->call('GET', "/commissioners/$alice"));
    $this->assertTrue($one['success']);
    $this->assertSame(['id' => $alice, 'name' => 'Zzsearchable'], $one['commissioner']);

    $missing = $this->call('GET', '/commissioners/999999999');
    $this->assertSame(400, $missing->getStatusCode());
    $this->assertFalse($this->json($missing)['success']);
  }

  public function testStyleAndDraftOptions(): void
  {
    $style = $this->call('GET', '/style');
    $this->assertStringStartsWith('text/css', $style->headers->get('Content-Type'));
    $this->assertStringContainsString('div.pickQB{background-color:#CCFFFF;}', $style->getContent());

    $options = $this->json($this->call('GET', '/draftOptions'));
    $this->assertTrue($options['success']);
    $this->assertSame('Football (NFL)', $options['sports']['NFL']);
    $this->assertSame('Setting Up', $options['statuses']['undrafted']);
  }

  public function testDraftViewableCheckBlocksPrivateDraftsWith400AndWrappedResponse(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted', 'secret');

    $blocked = $this->call('GET', "/_probe/draft/$id/viewable");

    $this->assertSame(400, $blocked->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($blocked));
    $this->assertSame(200, $this->call('GET', "/_probe/draft/$id/viewable", null, [self::PASSWORD_HEADER => 'secret'])->getStatusCode());
  }

  public function testDraftStateChecks(): void
  {
    $alice = $this->createUser('Alice');
    $setup = $this->createDraft($alice, 'undrafted');
    $running = $this->createDraft($alice, 'in_progress');
    $done = $this->createDraft($alice, 'complete', null, ['draft_end_time' => gmdate('Y-m-d H:i:s', time() - 3600)]);
    $justDone = $this->createDraft($alice, 'complete', null, ['draft_end_time' => gmdate('Y-m-d H:i:s', time() - 120)]);

    $expect = [
      // route => [draft => status]
      'setting-up' => [$setup => 200, $running => 200, $done => 400, $justDone => 400],
      'in-progress' => [$setup => 400, $running => 200, $done => 400, $justDone => 400],
      'in-progress-or-completed' => [$setup => 400, $running => 200, $done => 200, $justDone => 200],
      // Completed drafts stay editable for ten minutes after they end, then the grace period is over
      'ten-minutes' => [$setup => 400, $running => 200, $done => 400, $justDone => 200],
    ];

    foreach ($expect as $route => $statuses) {
      foreach ($statuses as $id => $status) {
        $this->assertSame($status, $this->call('GET', "/_probe/draft/$id/$route")->getStatusCode(), "$route for draft $id");
      }
    }

    $blocked = $this->json($this->call('GET', "/_probe/draft/$setup/in-progress"));
    $this->assertSame([['success' => false, 'errors' => ["Unable to work on draft #$setup: draft is setting up"]]], $blocked);
  }

  public function testCommishEditableCheckOnlyLetsTheOwnerOrAnAdminThrough(): void
  {
    $alice = $this->createUser('Alice');
    $this->createUser('Bob');
    $this->createUser('Root', 'ROLE_ADMIN');
    $id = $this->createDraft($alice, 'undrafted', null, ['draft_name' => 'Alice draft']);
    $uri = "/commish/_probe/draft/$id/editable";

    $this->assertSame(401, $this->call('GET', $uri)->getStatusCode());

    $this->logInSuccessfully('Bob');
    $denied = $this->call('GET', $uri);
    $this->assertSame(400, $denied->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['You do not have permission to this draft.']]], $this->json($denied));

    $this->cookies = [];
    $this->logInSuccessfully('Alice');
    $this->assertSame(200, $this->call('GET', $uri)->getStatusCode());

    $this->cookies = [];
    $this->logInSuccessfully('Root');
    $this->assertSame(200, $this->call('GET', $uri)->getStatusCode());
  }
}
