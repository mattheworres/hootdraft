<?php

namespace HootDraft\Tests\Unit\Security;

use HootDraft\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\MigratingPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

/**
 * Pins the hash format of every existing account: the Silex-era app stored base64(sha512 x5000) of
 * "password{salt}". The fixture below was produced by that algorithm; if it stops verifying, existing users
 * could no longer log in.
 */
class LegacyHashMigrationTest extends TestCase
{
  private const SALT = 'Zm9vYmFyYmF6cXV4'; // 16 chars, like SaltService::GenerateSalt()
  // base64(sha512 digest stretched 5000x of "correct horse{Zm9vYmFyYmF6cXV4}")
  private const LEGACY_HASH = 'Abg6g3jlchElqZZh0bw+XINtyPVhSXC5ZFHt0OW8t/LS+bGVLQv4AxuWIc42m24Tb7xHqORiJn52GcrHQlH5Jw==';

  private function hasher(): MigratingPasswordHasher
  {
    return new MigratingPasswordHasher(
      new NativePasswordHasher(),
      new MessageDigestPasswordHasher('sha512', true, 5000),
    );
  }

  public function testLegacyHashStillVerifiesWithItsSalt(): void
  {
    $this->assertTrue($this->hasher()->verify(self::LEGACY_HASH, 'correct horse', self::SALT));
    $this->assertFalse($this->hasher()->verify(self::LEGACY_HASH, 'wrong horse', self::SALT));
    $this->assertFalse($this->hasher()->verify(self::LEGACY_HASH, 'correct horse', 'AAAAAAAAAAAAAAAA'));
  }

  public function testLegacyHashIsFlaggedForRehash(): void
  {
    $this->assertTrue($this->hasher()->needsRehash(self::LEGACY_HASH));
  }

  public function testNewHashesVerifyAndNeedNoSalt(): void
  {
    $hash = $this->hasher()->hash('correct horse');

    $this->assertTrue($this->hasher()->verify($hash, 'correct horse'));
    $this->assertFalse($this->hasher()->needsRehash($hash));
  }

  public function testUserHidesPasswordHashInSession(): void
  {
    $user = new User(1, 'a@b.co', 'Al', self::LEGACY_HASH, self::SALT, ['ROLE_COMMISH'], true, true);

    $this->assertStringNotContainsString(self::LEGACY_HASH, serialize($user));
  }
}
