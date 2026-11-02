<?php
namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Validators\ProPlayerValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProPlayerValidatorTest extends TestCase {
  private ProPlayerValidator $sut;

  protected function setUp(): void {
    $this->sut = new ProPlayerValidator(new DraftDataRepository());
  }

  private function file(int $error = 0): UploadedFile {
    $file = $this->createStub(UploadedFile::class);
    $file->method('getError')->willReturn($error);

    return $file;
  }

  public function testEnsuresLeagueIsNotEmpty(): void {
    $result = $this->sut->IsUploadLeagueValid("", $this->file());

    $this->assertFalse($result->success);
    $this->assertContains("One or more missing fields.", $result->errors);
  }

  public function testUploadErrorsAreReported(): void {
    $result = $this->sut->IsUploadLeagueValid("NFL", $this->file(1));

    $this->assertFalse($result->success);
    $this->assertContains("Upload error - 1", $result->errors);
  }

  public function testValidateLeagueValues(): void {
    $result = $this->sut->IsUploadLeagueValid("QVC", $this->file());

    $this->assertFalse($result->success);
    $this->assertContains("League QVC is an invalid value.", $result->errors);
  }

  public function testAFileIsRequired(): void {
    $result = $this->sut->IsUploadLeagueValid("NFL", null);

    $this->assertFalse($result->success);
    $this->assertContains("Must upload a CSV file", $result->errors);
  }

  public function testAValidLeagueAndFilePass(): void {
    $result = $this->sut->IsUploadLeagueValid("NFL", $this->file());

    $this->assertTrue($result->success);
    $this->assertSame([], $result->errors);
  }
}
