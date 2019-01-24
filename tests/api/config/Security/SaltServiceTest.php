<?php
namespace PhpDraft\Test;

use PHPUnit\Framework\TestCase;
use PhpDraft\Config\Security\SaltService;

class SaltServiceTest extends TestCase {
  function setUp() {
    $this->sut = new SaltService();
  }

  public function testSaltGeneratedIsSixteenCharactersLong() {
    $result = $this->sut->GenerateSalt();

    $this->assertEquals(16, strlen($result));
  }

  public function testSaltForUrlGeneratedDoesntContainSlashes() {
    for ($i = 0; $i < 30; $i++) {
      $result = $this->sut->GenerateSaltForUrl();

      $this->assertTrue(strpos($result, '/') === false);
    }
  }
}
