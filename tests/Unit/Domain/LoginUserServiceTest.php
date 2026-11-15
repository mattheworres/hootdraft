<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\EmailVerificationResend;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Services\DraftService;
use HootDraft\Domain\Services\EmailService;
use HootDraft\Domain\Services\LoginUserService;
use HootDraft\Domain\Services\SaltService;
use HootDraft\Domain\Services\TemplateRenderService;
use HootDraft\Domain\Services\TransactionService;
use HootDraft\Tests\Support\FakesPdoTransactions;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class LoginUserServiceTest extends TestCase
{
  use FakesPdoTransactions;

  private function service(
    TransactionService $transactions,
    ?LoginUserRepository $repository = null,
    ?EmailService $email = null,
    ?DraftRepository $drafts = null,
    ?DraftService $draftService = null,
  ): LoginUserService {
    $salts = $this->createStub(SaltService::class);
    $salts->method('GenerateSaltForUrl')->willReturn('new-key');

    return new LoginUserService(
      $repository ?? $this->createStub(LoginUserRepository::class),
      $transactions,
      $email ?? $this->createStub(EmailService::class),
      $this->createStub(TemplateRenderService::class),
      $salts,
      new NullLogger(),
      $this->createStub(PasswordHasherFactoryInterface::class),
      $drafts ?? $this->createStub(DraftRepository::class),
      $draftService ?? $this->createStub(DraftService::class),
      'https://hootdraft.example',
    );
  }

  private function user(): LoginUser
  {
    $user = new LoginUser();
    $user->id = 3;
    $user->email = 'pat@example.com';
    $user->name = 'Pat';
    $user->enabled = true;
    $user->emailVerified = false;

    return $user;
  }

  public function testAnErrorWhileDeletingAUsersDraftRollsBackAndIsRethrown(): void
  {
    $drafts = $this->createStub(DraftRepository::class);
    $drafts->method('GetAllDraftsByCommish')->willReturn(array(new Draft()));
    $draftService = $this->createStub(DraftService::class);
    $draftService->method('DeleteDraft')->willThrowException(new \TypeError('Bad draft'));
    $repository = $this->createMock(LoginUserRepository::class);
    $repository->expects($this->never())->method('Delete');

    $service = $this->service($this->transactions(0, 1), $repository, drafts: $drafts, draftService: $draftService);

    $this->expectException(\TypeError::class);

    $service->DeleteUser($this->user());
  }

  public function testAFailedEmailVerificationEmailRollsBackTheNewKey(): void
  {
    $email = $this->createStub(EmailService::class);
    $email->method('SendMail')->willThrowException(new \Exception('SMTP down'));
    $repository = $this->createMock(LoginUserRepository::class);
    $repository->expects($this->once())->method('Update');

    $result = $this->service($this->transactions(0, 1), $repository, $email)->ResendEmailVerificationIfExpired($this->user());

    $this->assertSame(EmailVerificationResend::Failed, $result);
  }

  public function testASentEmailVerificationEmailCommitsTheNewKey(): void
  {
    $result = $this->service($this->transactions(1, 0))->ResendEmailVerificationIfExpired($this->user());

    $this->assertSame(EmailVerificationResend::Sent, $result);
  }
}
