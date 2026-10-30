<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use HootDraft\Domain\Models\MailMessage;
use HootDraft\Domain\Services\EmailService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;

/**
 * Cookie-session authentication end to end against the migrated test DB (see DatabaseTest for setup).
 * Every user these tests create has an @auth-test.example email and is deleted afterwards.
 */
#[Group('database')]
class AuthenticationTest extends KernelTestCase
{
  private const SALT = 'Zm9vYmFyYmF6cXV4';
  private const PASSWORD = 'correct horse';
  private const GENERIC_FAILURE = 'Invalid credentials.';
  private const EMAIL_UNVERIFIED = 'Your account email is still unverified. Please click the link in your email to continue.';
  private const EMAIL_UNVERIFIED_SEND_FAILED = 'Your account email is still unverified. Your link expired and we could not send a new one. Please try again later.';
  private const EMAIL_UNVERIFIED_NEW_LINK = 'Your account email is still unverified. Your link expired, so we sent a new one. Please click the link in your email to continue.';

  /** @var array<string,string> cookies the "browser" holds */
  private array $cookies = [];

  /** @var MailMessage[] everything the app tried to send; the kernel reboots after each call, which empties its own list */
  private array $sent = [];

  protected function setUp(): void
  {
    self::bootKernel();
    $this->cookies = [];
    $this->sent = [];
    $this->db()->executeStatement("DELETE FROM users WHERE email LIKE '%@auth-test.example'");
  }

  protected function tearDown(): void
  {
    $this->db()->executeStatement("DELETE FROM users WHERE email LIKE '%@auth-test.example'");
    parent::tearDown();
  }

  private function db(): Connection
  {
    return static::getContainer()->get(Connection::class);
  }

  /** A user as the Silex-era app stored them: legacy digest + salt. */
  private function createLegacyUser(string $email, string $roles = 'ROLE_COMMISH', int $enabled = 1, int $emailVerified = 1): void
  {
    $hash = (new MessageDigestPasswordHasher('sha512', true, 5000))->hash(self::PASSWORD, self::SALT);

    $this->db()->insert('users', [
      'email' => $email, 'password' => $hash, 'salt' => self::SALT, 'name' => 'User ' . $email,
      'roles' => $roles, 'enabled' => $enabled, 'emailVerified' => $emailVerified, 'creationTime' => '2020-01-01 00:00:00',
    ]);
  }

  /** An Email Unverified user holding an Email Verification Key issued $ageSeconds ago (null: no key at all). */
  private function createEmailUnverifiedUser(string $email, ?int $ageSeconds, int $enabled = 1): void
  {
    $this->createLegacyUser($email, 'ROLE_COMMISH', $enabled, 0);

    if ($ageSeconds !== null) {
      $this->db()->executeStatement(
        'UPDATE users SET emailVerificationKey = ?, emailVerificationKeyCreated = UTC_TIMESTAMP() - INTERVAL ? SECOND WHERE email = ?',
        ['AAAAAAAAAAAAAAAA', $ageSeconds, $email],
      );
    }
  }

  private function userRow(string $email): array
  {
    return $this->db()->fetchAssociative('SELECT * FROM users WHERE email = ?', [$email]);
  }

  private function call(string $method, string $uri, ?array $json = null, bool $ajax = true): Response
  {
    $server = ['CONTENT_TYPE' => 'application/json'];
    if ($ajax) {
      $server['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    }

    $request = Request::create($uri, $method, [], $this->cookies, [], $server, $json === null ? null : json_encode($json));
    $response = self::$kernel->handle($request);
    $this->sent = array_merge($this->sent, static::getContainer()->get(EmailService::class)->sent);

    foreach ($response->headers->getCookies() as $cookie) {
      if ($cookie->getValue() === null || $cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() < time()) {
        unset($this->cookies[$cookie->getName()]); // the server expired it, as on logout
      } else {
        $this->cookies[$cookie->getName()] = $cookie->getValue();
      }
    }

    // Each browser request is a fresh PHP request; don't let session state leak between them in-process
    self::$kernel->shutdown();
    self::bootKernel();

    return $response;
  }

  private function body(Response $response): array
  {
    return json_decode($response->getContent(), true);
  }

  private function login(string $email, string $password = self::PASSWORD): Response
  {
    return $this->call('POST', '/login', ['_email' => $email, '_password' => $password]);
  }

  public function testLegacyUserCanLogInAndIsRehashed(): void
  {
    $this->createLegacyUser('legacy@auth-test.example');

    $response = $this->login('legacy@auth-test.example');

    $this->assertSame(200, $response->getStatusCode());
    $body = $this->body($response);
    $this->assertTrue($body['success']);
    $this->assertSame('User legacy@auth-test.example', $body['name']);
    $this->assertFalse($body['is_admin']);
    $this->assertArrayNotHasKey('token', $body);

    $row = $this->db()->fetchAssociative("SELECT password, salt FROM users WHERE email = 'legacy@auth-test.example'");
    $this->assertStringStartsWith('$', $row['password'], 'password should now be a modern hash');
    $this->assertSame('', $row['salt']);

    // ... and the new hash works for the next login
    $this->cookies = [];
    $this->assertSame(200, $this->login('legacy@auth-test.example')->getStatusCode());
  }

  public function testSessionCookieIsHttpOnlyAndSameSiteLax(): void
  {
    $this->createLegacyUser('cookie@auth-test.example');

    $response = $this->login('cookie@auth-test.example');

    $cookies = $response->headers->getCookies();
    $this->assertCount(1, $cookies);
    $this->assertTrue($cookies[0]->isHttpOnly());
    $this->assertSame('lax', $cookies[0]->getSameSite());
  }

  public function testBadPasswordUnknownEmailAndDisabledAccountAllLookTheSame(): void
  {
    $this->createLegacyUser('known@auth-test.example');
    $this->createLegacyUser('disabled@auth-test.example', 'ROLE_COMMISH', 0);

    foreach ([
      $this->login('known@auth-test.example', 'wrong password'),
      $this->login('nobody@auth-test.example'),
      $this->login('disabled@auth-test.example'),
    ] as $response) {
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame(['success' => false, 'errors' => ['Invalid credentials.']], $this->body($response));
    }

    $this->assertSame([], $this->cookies);
  }

  public function testMeRequiresSessionAndReportsUser(): void
  {
    $this->createLegacyUser('me@auth-test.example', 'ROLE_ADMIN');

    $this->assertSame(401, $this->call('GET', '/me')->getStatusCode());

    $this->login('me@auth-test.example');
    $response = $this->call('GET', '/me');

    $this->assertSame(200, $response->getStatusCode());
    $this->assertTrue($this->body($response)['is_admin']);
  }

  public function testCommishRoutesNeedLoginAndAdminRoutesNeedAdmin(): void
  {
    $this->createLegacyUser('commish@auth-test.example');

    $this->assertSame(401, $this->call('GET', '/commish/_probe')->getStatusCode());
    $this->assertSame(401, $this->call('GET', '/admin/_probe')->getStatusCode());

    $this->login('commish@auth-test.example');

    $this->assertSame(200, $this->call('GET', '/commish/_probe')->getStatusCode());
    $this->assertSame(403, $this->call('GET', '/admin/_probe')->getStatusCode());

    // ... and an admin inherits ROLE_COMMISH through the role hierarchy
    $this->createLegacyUser('admin@auth-test.example', 'ROLE_ADMIN');
    $this->cookies = [];
    $this->login('admin@auth-test.example');

    $this->assertSame(200, $this->call('GET', '/admin/_probe')->getStatusCode());
    $this->assertSame(200, $this->call('GET', '/commish/_probe')->getStatusCode());
  }

  public function testLogoutEndsTheSession(): void
  {
    $this->createLegacyUser('out@auth-test.example');
    $this->login('out@auth-test.example');
    $this->assertSame(200, $this->call('GET', '/me')->getStatusCode());

    $this->assertSame(200, $this->call('POST', '/logout')->getStatusCode());

    $this->assertSame(401, $this->call('GET', '/me')->getStatusCode());
  }

  public function testUserDisabledMidSessionIsLoggedOut(): void
  {
    $this->createLegacyUser('mid@auth-test.example');
    $this->login('mid@auth-test.example');

    $this->db()->executeStatement("UPDATE users SET enabled = 0 WHERE email = 'mid@auth-test.example'");

    $this->assertSame(401, $this->call('GET', '/me')->getStatusCode());
  }

  public function testStateChangingRequestsNeedTheCustomHeader(): void
  {
    $this->createLegacyUser('csrf@auth-test.example');

    $response = $this->call('POST', '/login', ['_email' => 'csrf@auth-test.example', '_password' => self::PASSWORD], false);

    $this->assertSame(403, $response->getStatusCode());
    $this->assertSame([], $this->cookies);
  }

  public function testRegisterFollowTheEmailLinkAndLogIn(): void
  {
    $response = $this->call('POST', '/register', [
      '_email' => 'new@auth-test.example', '_password' => 'hunter2hunter2', '_confirmPassword' => 'hunter2hunter2',
      '_name' => 'Newbie Auth Test', '_recaptcha' => 'ignored-in-debug-without-secret',
    ]);

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    $row = $this->userRow('new@auth-test.example');
    $this->assertSame([1, 0], [(int) $row['enabled'], (int) $row['emailVerified']]);
    $this->assertSame(16, strlen($row['emailVerificationKey']));
    $this->assertNotNull($row['emailVerificationKeyCreated']);
    $this->assertNull($row['resetKey']);

    $this->assertCount(1, $this->sent);
    $this->assertStringContainsString('/verify/new%40auth-test.example/' . urlencode($row['emailVerificationKey']), $this->sent[0]->altBody);

    // Email Unverified accounts can't log in yet
    $login = $this->login('new@auth-test.example', 'hunter2hunter2');
    $this->assertSame(400, $login->getStatusCode());
    $this->assertSame([self::EMAIL_UNVERIFIED], $this->body($login)['errors']);

    $response = $this->call('POST', '/emailVerification', ['_email' => 'new@auth-test.example', '_emailVerificationKey' => $row['emailVerificationKey']]);

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertSame('Newbie Auth Test', $this->body($response)['name']);
    $this->assertArrayNotHasKey('token', $this->body($response));

    // Following the link logged them in
    $this->assertSame(200, $this->call('GET', '/me')->getStatusCode());
    $row = $this->userRow('new@auth-test.example');
    $this->assertSame([1, 1], [(int) $row['enabled'], (int) $row['emailVerified']]);
    $this->assertNull($row['emailVerificationKey']);
    $this->assertNull($row['emailVerificationKeyCreated']);

    // ... and they can log in the normal way
    $this->cookies = [];
    $this->assertSame(200, $this->login('new@auth-test.example', 'hunter2hunter2')->getStatusCode());
  }

  public function testEmailUnverifiedWithAFreshKeyGetsTheMessageAndNoEmail(): void
  {
    $this->createEmailUnverifiedUser('fresh@auth-test.example', 3600);

    $response = $this->login('fresh@auth-test.example');

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['success' => false, 'errors' => [self::EMAIL_UNVERIFIED]], $this->body($response));
    $this->assertSame([], $this->sent);
    $this->assertSame([], $this->cookies);
    $this->assertSame('AAAAAAAAAAAAAAAA', $this->userRow('fresh@auth-test.example')['emailVerificationKey'], 'the link they were sent still works');
  }

  public function testEmailUnverifiedWithAnExpiredKeyGetsANewEmail(): void
  {
    $this->createEmailUnverifiedUser('expired@auth-test.example', 172800 + 60);

    $response = $this->login('expired@auth-test.example');

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['success' => false, 'errors' => [self::EMAIL_UNVERIFIED_NEW_LINK]], $this->body($response));

    $row = $this->userRow('expired@auth-test.example');
    $this->assertNotSame('AAAAAAAAAAAAAAAA', $row['emailVerificationKey']);
    $this->assertSame(16, strlen($row['emailVerificationKey']));
    $this->assertNull($row['resetKey']);
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT emailVerificationKeyCreated > UTC_TIMESTAMP() - INTERVAL 1 MINUTE FROM users WHERE email = ?', ['expired@auth-test.example']));

    $this->assertCount(1, $this->sent);
    $this->assertSame(['expired@auth-test.example' => 'User expired@auth-test.example'], $this->sent[0]->to_addresses);
    $this->assertStringContainsString('/verify/expired%40auth-test.example/' . urlencode($row['emailVerificationKey']), $this->sent[0]->altBody);

    // The old link is dead, the new one makes them Email Verified
    $old = $this->call('POST', '/emailVerification', ['_email' => 'expired@auth-test.example', '_emailVerificationKey' => 'AAAAAAAAAAAAAAAA']);
    $this->assertSame(400, $old->getStatusCode());
    $new = $this->call('POST', '/emailVerification', ['_email' => 'expired@auth-test.example', '_emailVerificationKey' => $row['emailVerificationKey']]);
    $this->assertSame(200, $new->getStatusCode(), $new->getContent());
    $this->assertSame(1, (int) $this->userRow('expired@auth-test.example')['emailVerified']);
  }

  public function testAFailedResendKeepsTheOldKeyAndTheNextLoginTriesAgain(): void
  {
    $this->createEmailUnverifiedUser('down@auth-test.example', 172800 + 60);
    static::getContainer()->get(EmailService::class)->failing = true;

    $response = $this->login('down@auth-test.example');

    $this->assertSame(['success' => false, 'errors' => [self::EMAIL_UNVERIFIED_SEND_FAILED]], $this->body($response));
    $this->assertSame('AAAAAAAAAAAAAAAA', $this->userRow('down@auth-test.example')['emailVerificationKey'], 'the key was not replaced by one nobody received');

    // Nothing is locked for 48 hours: once mail works again, the next login sends the new link
    static::getContainer()->get(EmailService::class)->failing = false;
    $this->assertSame([self::EMAIL_UNVERIFIED_NEW_LINK], $this->body($this->login('down@auth-test.example'))['errors']);
    $this->assertCount(1, $this->sent);
  }

  public function testEmailUnverifiedWithoutAKeyGetsANewEmail(): void
  {
    $this->createEmailUnverifiedUser('nokey@auth-test.example', null);

    $response = $this->login('nokey@auth-test.example');

    $this->assertSame([self::EMAIL_UNVERIFIED_NEW_LINK], $this->body($response)['errors']);
    $this->assertCount(1, $this->sent);
    $this->assertSame(16, strlen($this->userRow('nokey@auth-test.example')['emailVerificationKey']));
  }

  public function testWrongPasswordGetsNoEmailAndNoHint(): void
  {
    $this->createEmailUnverifiedUser('hint@auth-test.example', 172800 + 60);

    foreach ([$this->login('hint@auth-test.example', 'wrong password'), $this->login('nobody@auth-test.example')] as $response) {
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame(['success' => false, 'errors' => [self::GENERIC_FAILURE]], $this->body($response));
    }

    $this->assertSame([], $this->sent);
    $this->assertSame('AAAAAAAAAAAAAAAA', $this->userRow('hint@auth-test.example')['emailVerificationKey']);
  }

  public function testDisabledAndEmailUnverifiedGetsTheGenericMessage(): void
  {
    $this->createEmailUnverifiedUser('both@auth-test.example', 172800 + 60, 0);

    $response = $this->login('both@auth-test.example');

    $this->assertSame(['success' => false, 'errors' => [self::GENERIC_FAILURE]], $this->body($response));
    $this->assertSame([], $this->sent, 'no new link for a Disabled user');
    $this->assertSame('AAAAAAAAAAAAAAAA', $this->userRow('both@auth-test.example')['emailVerificationKey']);
  }

  public function testDisabledStaysDisabledAfterFollowingTheEmailLink(): void
  {
    $this->createEmailUnverifiedUser('stuck@auth-test.example', 60, 0);

    $response = $this->call('POST', '/emailVerification', ['_email' => 'stuck@auth-test.example', '_emailVerificationKey' => 'AAAAAAAAAAAAAAAA']);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame([], $this->cookies, 'following the link must not log a Disabled user in');
    $row = $this->userRow('stuck@auth-test.example');
    $this->assertSame([0, 1], [(int) $row['enabled'], (int) $row['emailVerified']]);
    $this->assertSame(self::GENERIC_FAILURE, $this->body($this->login('stuck@auth-test.example'))['errors'][0]);
  }

  public function testEmailChangeEndsTheSessionAndBlocksLoginUntilEmailVerified(): void
  {
    $this->createLegacyUser('before@auth-test.example');
    $this->login('before@auth-test.example');

    $response = $this->call('PUT', '/commish/profile', [
      '_email' => 'after@auth-test.example', '_name' => 'User before@auth-test.example', '_password' => self::PASSWORD,
      '_newPassword' => '', '_newConfirmedPassword' => '',
    ]);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    $row = $this->userRow('after@auth-test.example');
    $this->assertSame([1, 0], [(int) $row['enabled'], (int) $row['emailVerified']]);
    $this->assertCount(1, $this->sent);

    $this->assertSame(401, $this->call('GET', '/me')->getStatusCode(), 'the session ended');

    $login = $this->login('after@auth-test.example');
    $this->assertSame([self::EMAIL_UNVERIFIED], $this->body($login)['errors']);
    $this->assertCount(1, $this->sent, 'the key is fresh, so no second email');

    $followed = $this->call('POST', '/emailVerification', ['_email' => 'after@auth-test.example', '_emailVerificationKey' => $row['emailVerificationKey']]);
    $this->assertSame(200, $followed->getStatusCode(), $followed->getContent());
    $this->cookies = [];
    $this->assertSame(200, $this->login('after@auth-test.example')->getStatusCode());
  }

  public function testUserWhoBecomesEmailUnverifiedMidSessionIsLoggedOut(): void
  {
    $this->createLegacyUser('midsession@auth-test.example');
    $this->login('midsession@auth-test.example');

    $this->db()->executeStatement("UPDATE users SET emailVerified = 0 WHERE email = 'midsession@auth-test.example'");

    $this->assertSame(401, $this->call('GET', '/me')->getStatusCode());
  }

  public function testRegisterRejectsBadInput(): void
  {
    $response = $this->call('POST', '/register', [
      '_email' => 'not-an-email', '_password' => 'short', '_confirmPassword' => 'different', '_name' => 'X', '_recaptcha' => 'x',
    ]);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertFalse($this->body($response)['success']);
  }

  public function testLostPasswordAndResetFlow(): void
  {
    $this->createLegacyUser('reset@auth-test.example');

    $response = $this->call('POST', '/lostPassword', ['_email' => 'reset@auth-test.example', '_recaptcha' => 'x']);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    $key = $this->db()->fetchOne("SELECT resetKey FROM users WHERE email = 'reset@auth-test.example'");
    $this->assertSame(16, strlen($key));

    $this->assertSame(200, $this->call('GET', '/checkResetKey?' . http_build_query(['_email' => 'reset@auth-test.example', '_resetKey' => $key]))->getStatusCode());
    $this->assertSame(400, $this->call('GET', '/checkResetKey?' . http_build_query(['_email' => 'reset@auth-test.example', '_resetKey' => 'AAAAAAAAAAAAAAAA']))->getStatusCode());

    $response = $this->call('POST', '/resetPassword', [
      '_email' => 'reset@auth-test.example', '_password' => 'a brand new one', '_confirmPassword' => 'a brand new one',
      '_resetKey' => $key,
    ]);

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertSame(200, $this->call('GET', '/me')->getStatusCode());

    $this->cookies = [];
    $this->assertSame(400, $this->login('reset@auth-test.example')->getStatusCode(), 'old password no longer works');
    $this->assertSame(200, $this->login('reset@auth-test.example', 'a brand new one')->getStatusCode());
  }

  public function testLostPasswordIsRefusedForEmailUnverifiedAndDisabledAccountsAndLeavesTheirKeysAlone(): void
  {
    $this->createEmailUnverifiedUser('pending@auth-test.example', 60);
    $this->createLegacyUser('off@auth-test.example', 'ROLE_COMMISH', 0);

    foreach (['pending@auth-test.example', 'off@auth-test.example'] as $email) {
      $response = $this->call('POST', '/lostPassword', ['_email' => $email, '_recaptcha' => 'x']);

      $this->assertSame(400, $response->getStatusCode());
    }

    $this->assertSame([], $this->sent);
    $row = $this->userRow('pending@auth-test.example');
    $this->assertSame('AAAAAAAAAAAAAAAA', $row['emailVerificationKey']);
    $this->assertNull($row['resetKey']);
  }

  public function testAResetDoesNotTouchTheEmailVerificationKey(): void
  {
    $this->createLegacyUser('keeps@auth-test.example');
    $this->db()->executeStatement("UPDATE users SET emailVerificationKey = 'AAAAAAAAAAAAAAAA', emailVerificationKeyCreated = '2026-10-30 12:00:00' WHERE email = 'keeps@auth-test.example'");

    $this->call('POST', '/lostPassword', ['_email' => 'keeps@auth-test.example', '_recaptcha' => 'x']);
    $key = $this->userRow('keeps@auth-test.example')['resetKey'];
    $this->assertSame(16, strlen($key));

    $response = $this->call('POST', '/resetPassword', [
      '_email' => 'keeps@auth-test.example', '_password' => 'a brand new one', '_confirmPassword' => 'a brand new one', '_resetKey' => $key,
    ]);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    $row = $this->userRow('keeps@auth-test.example');
    $this->assertNull($row['resetKey']);
    $this->assertSame('AAAAAAAAAAAAAAAA', $row['emailVerificationKey']);
    $this->assertSame('2026-10-30 12:00:00', $row['emailVerificationKeyCreated']);
    $this->assertSame(1, (int) $row['emailVerified']);
  }

  public function testAnEmailVerificationKeyCannotBeUsedForAReset(): void
  {
    $this->createLegacyUser('cross@auth-test.example');
    $this->db()->executeStatement("UPDATE users SET emailVerificationKey = 'AAAAAAAAAAAAAAAA', emailVerificationKeyCreated = UTC_TIMESTAMP() WHERE email = 'cross@auth-test.example'");

    $query = http_build_query(['_email' => 'cross@auth-test.example', '_resetKey' => 'AAAAAAAAAAAAAAAA']);

    $this->assertSame(400, $this->call('GET', '/checkResetKey?' . $query)->getStatusCode());
  }

  public function testEmailedKeysExpire(): void
  {
    $this->createLegacyUser('stale@auth-test.example');
    $this->db()->executeStatement("UPDATE users SET resetKey = 'AAAAAAAAAAAAAAAA', resetKeyCreated = UTC_TIMESTAMP() - INTERVAL 2 HOUR WHERE email = 'stale@auth-test.example'");

    $query = http_build_query(['_email' => 'stale@auth-test.example', '_resetKey' => 'AAAAAAAAAAAAAAAA']);
    $this->assertSame(400, $this->call('GET', '/checkResetKey?' . $query)->getStatusCode(), 'a reset link is good for an hour');

    $this->db()->executeStatement("UPDATE users SET resetKeyCreated = UTC_TIMESTAMP() - INTERVAL 30 MINUTE WHERE email = 'stale@auth-test.example'");
    $this->assertSame(200, $this->call('GET', '/checkResetKey?' . $query)->getStatusCode());
  }

  public function testLoggingInDoesNotCancelAPendingReset(): void
  {
    $this->createLegacyUser('pendingreset@auth-test.example');
    $this->call('POST', '/lostPassword', ['_email' => 'pendingreset@auth-test.example', '_recaptcha' => 'x']);
    $key = $this->db()->fetchOne("SELECT resetKey FROM users WHERE email = 'pendingreset@auth-test.example'");

    $this->assertSame(200, $this->login('pendingreset@auth-test.example')->getStatusCode());

    $this->assertSame($key, $this->db()->fetchOne("SELECT resetKey FROM users WHERE email = 'pendingreset@auth-test.example'"));
  }
}
