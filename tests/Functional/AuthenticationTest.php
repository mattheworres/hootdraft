<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use HootDraft\Tests\Support\CapturingEmailService;
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

  /** @var array<string,string> cookies the "browser" holds */
  private array $cookies = [];

  protected function setUp(): void
  {
    self::bootKernel();
    $this->cookies = [];
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
  private function createLegacyUser(string $email, string $roles = 'ROLE_COMMISH', int $enabled = 1): void
  {
    $hash = (new MessageDigestPasswordHasher('sha512', true, 5000))->hash(self::PASSWORD, self::SALT);

    $this->db()->insert('users', [
      'email' => $email, 'password' => $hash, 'salt' => self::SALT, 'name' => 'User ' . $email,
      'roles' => $roles, 'enabled' => $enabled, 'creationTime' => '2020-01-01 00:00:00',
    ]);
  }

  private function call(string $method, string $uri, ?array $json = null, bool $ajax = true): Response
  {
    $server = ['CONTENT_TYPE' => 'application/json'];
    if ($ajax) {
      $server['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    }

    $request = Request::create($uri, $method, [], $this->cookies, [], $server, $json === null ? null : json_encode($json));
    $response = self::$kernel->handle($request);

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

  public function testRegisterVerifyAndLoginFlow(): void
  {
    $response = $this->call('POST', '/register', [
      '_email' => 'new@auth-test.example', '_password' => 'hunter2hunter2', '_confirmPassword' => 'hunter2hunter2',
      '_name' => 'Newbie Auth Test', '_recaptcha' => 'ignored-in-debug-without-secret',
    ]);

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    $mailer = static::getContainer()->get(\HootDraft\Domain\Services\EmailService::class);
    $this->assertInstanceOf(CapturingEmailService::class, $mailer);

    // Unverified accounts can't log in yet
    $this->assertSame(400, $this->login('new@auth-test.example', 'hunter2hunter2')->getStatusCode());

    $key = $this->db()->fetchOne("SELECT resetKey FROM users WHERE email = 'new@auth-test.example'");
    $response = $this->call('POST', '/verify', ['_email' => 'new@auth-test.example', '_verificationToken' => $key]);

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertSame('Newbie Auth Test', $this->body($response)['name']);
    $this->assertArrayNotHasKey('token', $this->body($response));

    // Verifying logged them in
    $this->assertSame(200, $this->call('GET', '/me')->getStatusCode());
    $this->assertNull($this->db()->fetchOne("SELECT resetKey FROM users WHERE email = 'new@auth-test.example'"));
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

    $this->assertSame(200, $this->call('GET', '/verifyToken?' . http_build_query(['_email' => 'reset@auth-test.example', '_verificationToken' => $key]))->getStatusCode());
    $this->assertSame(400, $this->call('GET', '/verifyToken?' . http_build_query(['_email' => 'reset@auth-test.example', '_verificationToken' => 'AAAAAAAAAAAAAAAA']))->getStatusCode());

    $response = $this->call('POST', '/resetPassword', [
      '_email' => 'reset@auth-test.example', '_password' => 'a brand new one', '_confirmPassword' => 'a brand new one',
      '_verificationToken' => $key,
    ]);

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertSame(200, $this->call('GET', '/me')->getStatusCode());

    $this->cookies = [];
    $this->assertSame(400, $this->login('reset@auth-test.example')->getStatusCode(), 'old password no longer works');
    $this->assertSame(200, $this->login('reset@auth-test.example', 'a brand new one')->getStatusCode());
  }

  public function testResetIsRefusedForUnverifiedAccountsAndLeavesTheirKeyAlone(): void
  {
    $this->createLegacyUser('pending@auth-test.example', 'ROLE_COMMISH', 0);
    $this->db()->executeStatement("UPDATE users SET resetKey = 'AAAAAAAAAAAAAAAA', resetKeyCreated = UTC_TIMESTAMP() WHERE email = 'pending@auth-test.example'");

    $response = $this->call('POST', '/lostPassword', ['_email' => 'pending@auth-test.example', '_recaptcha' => 'x']);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame('AAAAAAAAAAAAAAAA', $this->db()->fetchOne("SELECT resetKey FROM users WHERE email = 'pending@auth-test.example'"));
  }

  public function testEmailedKeysExpire(): void
  {
    $this->createLegacyUser('stale@auth-test.example');
    $this->db()->executeStatement("UPDATE users SET resetKey = 'AAAAAAAAAAAAAAAA', resetKeyCreated = UTC_TIMESTAMP() - INTERVAL 2 HOUR WHERE email = 'stale@auth-test.example'");

    $query = http_build_query(['_email' => 'stale@auth-test.example', '_verificationToken' => 'AAAAAAAAAAAAAAAA']);
    $this->assertSame(400, $this->call('GET', '/verifyToken?' . $query)->getStatusCode(), 'a reset link is good for an hour');

    $this->db()->executeStatement("UPDATE users SET resetKeyCreated = UTC_TIMESTAMP() - INTERVAL 30 MINUTE WHERE email = 'stale@auth-test.example'");
    $this->assertSame(200, $this->call('GET', '/verifyToken?' . $query)->getStatusCode());
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
