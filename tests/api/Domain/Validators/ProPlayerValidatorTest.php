<?php
namespace PhpDraft\Test;

use PHPUnit\Framework\TestCase;
use PhpDraft\Domain\Validators\ProPlayerValidator;
use PhpDraft\Domain\Repositories\DraftDataRepository;

class ProPlayerValidatorTest extends TestCase {
  function setUp() {
    $this->app = require __DIR__.'/../../../../api/config/_app.php';
    $draftDataRepository = new DraftDataRepository($this->app);
    $this->sut = new ProPlayerValidator($this->app);
    $this->app['phpdraft.DraftDataRepository'] = $draftDataRepository;

    $this->uploadedFileClass = 'Symfony\Component\HttpFoundation\File\UploadedFile';
  }

  public function testEnsuresSportIsNotEmpty() {
    $mockFile = $this->getMockBuilder($this->uploadedFileClass)
                    ->enableOriginalConstructor()
                    ->setConstructorArgs([tempnam(sys_get_temp_dir(), ''), 'dummy'])
                    ->getMock();

    $sport = "";

    $result = $this->sut->IsUploadSportValid($sport, $mockFile);

    $this->assertFalse($result->success);
    $this->assertContains("One or more missing fields.", $result->errors);
  }

  public function testDraftIdAndSecondsArePresent() {
    $mockFile = $this->getMockBuilder($this->uploadedFileClass)
                    ->enableOriginalConstructor()
                    ->setConstructorArgs([tempnam(sys_get_temp_dir(), ''), 'dummy'])
                    ->getMock();

    $mockFile->expects($this->once())
        ->method('getError')
        ->will($this->returnValue(1));

    $sport = "NFL";

    $result = $this->sut->IsUploadSportValid($sport, $mockFile);

    $this->assertFalse($result->success);
    $this->assertContains("Upload error - 1", $result->errors);
  }

  public function testValidateSportValues() {
    $mockFile = $this->getMockBuilder($this->uploadedFileClass)
                    ->enableOriginalConstructor()
                    ->setConstructorArgs([tempnam(sys_get_temp_dir(), ''), 'dummy'])
                    ->getMock();

    $sport = "QVC";

    $result = $this->sut->IsUploadSportValid($sport, $mockFile);

    $this->assertFalse($result->success);
    $this->assertContains("Sport QVC is an invalid value.", $result->errors);
  }
}