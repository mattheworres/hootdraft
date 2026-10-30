<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Services\LoginUserService;
use HootDraft\Domain\Services\RecaptchaVerifier;
use HootDraft\Domain\Services\SaltService;
use HootDraft\Domain\Validators\LoginUserValidator;
use HootDraft\Security\AuthResponse;
use HootDraft\Security\CurrentUser;
use HootDraft\Security\UserProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Login, logout and the account email flows. Authentication is a session cookie set by the firewall
 * (config/packages/security.yaml); none of these endpoints return a token any more.
 */
class AuthenticationController
{
  public function __construct(
    private LoginUserValidator $validator,
    private LoginUserService $service,
    private LoginUserRepository $repository,
    private RecaptchaVerifier $recaptcha,
    private SaltService $saltService,
    private Security $security,
    private UserProvider $userProvider,
    private CurrentUser $currentUser,
  ) {
  }

  /** Handled by the firewall's json_login authenticator before it ever gets here. Needs a route to exist. */
  #[Route('/login', name: 'login', methods: ['POST'])]
  public function login(): never
  {
    throw new \LogicException('The json_login authenticator should have handled this request.');
  }

  /** Handled by the firewall's logout listener. POST only so a link can't log somebody out. */
  #[Route('/logout', name: 'logout', methods: ['POST'])]
  public function logout(): never
  {
    throw new \LogicException('The logout listener should have handled this request.');
  }

  /** Who the session cookie belongs to; the client asks on load instead of keeping a token. */
  #[Route('/me', name: 'me', methods: ['GET'])]
  public function me(): JsonResponse
  {
    // access_control guarantees an authenticated user here
    return new JsonResponse(AuthResponse::for($this->currentUser->get()));
  }

  #[Route('/register', name: 'register', methods: ['POST'])]
  public function register(Request $request): JsonResponse
  {
    $payload = $request->getPayload();

    // The captcha comes first: the validation below says which names and emails are already registered
    if ($failure = $this->recaptchaFailure($payload->getString('_recaptcha'), $request)) {
      return $failure;
    }

    $validity = $this->validator->IsRegistrationUserValid(
      $payload->getString('_password'),
      $payload->getString('_confirmPassword'),
      $payload->getString('_email'),
      $payload->getString('_name'),
      $payload->getString('_recaptcha'),
    );

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $user = new LoginUser();
    $user->email = $payload->getString('_email');
    $user->password = $payload->getString('_password');
    $user->name = $payload->getString('_name');

    $response = $this->service->CreateEmailUnverifiedNewUser($user);

    return new JsonResponse($response, $response->responseType());
  }

  /** The emailed Email Verification Key link lands here. */
  #[Route('/emailVerification', name: 'email_verification', methods: ['POST'])]
  public function followEmailVerificationLink(Request $request): JsonResponse
  {
    $payload = $request->getPayload();
    $email = $payload->getString('_email');

    $validity = $this->validator->IsEmailVerificationKeyValid($email, $payload->getString('_emailVerificationKey'));

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $user = $this->repository->Load($email);
    $response = $this->service->MarkEmailVerified($user);

    // The address is Email Verified now, but a Disabled user stays out: no session for them
    if (!$user->enabled) {
      $response = new HootDraftResponse(false, ['Account is not enabled.']);

      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    if ($response->success) {
      $response = $this->startSession($email, $response);
    }

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/lostPassword', name: 'lost_password', methods: ['POST'])]
  public function lostPassword(Request $request): JsonResponse
  {
    $payload = $request->getPayload();
    $email = $payload->getString('_email');

    // The captcha comes first: the validation below says whether an account exists for the email
    if ($failure = $this->recaptchaFailure($payload->getString('_recaptcha'), $request)) {
      return $failure;
    }

    $validity = $this->validator->IsForgottenPasswordUserValid($email);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->BeginForgottenPasswordProcess($this->repository->Load($email));

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/checkResetKey', name: 'reset_key_check', methods: ['GET'])]
  public function checkResetKey(Request $request): JsonResponse
  {
    $validity = $this->validator->IsResetKeyValid(
      $request->query->getString('_email'),
      $request->query->getString('_resetKey'),
    );

    return new JsonResponse($validity, $validity->responseType());
  }

  #[Route('/resetPassword', name: 'reset_password', methods: ['POST'])]
  public function resetPassword(Request $request): JsonResponse
  {
    $payload = $request->getPayload();
    $email = $payload->getString('_email');
    $password = $payload->getString('_password');

    $validity = $this->validator->IsResetRequestValid(
      $email,
      $password,
      $payload->getString('_confirmPassword'),
      $payload->getString('_resetKey'),
    );

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $user = $this->repository->Load($email);
    $user->password = $password;

    $response = $this->service->ResetPassword($user);

    if ($response->success) {
      $response = $this->startSession($email, $response);
    }

    return new JsonResponse($response, $response->responseType());
  }

  /** Starts the session for somebody who just proved who they are with an emailed link. */
  private function startSession(string $email, HootDraftResponse $response): HootDraftResponse
  {
    $user = $this->userProvider->loadUserByIdentifier($email);

    $this->security->login($user);

    return AuthResponse::for($user, $response);
  }

  private function recaptchaFailure(string $captcha, Request $request): ?JsonResponse
  {
    $errors = $this->recaptcha->Verify($captcha, $request->getClientIp());

    if ($errors === []) {
      return null;
    }

    $response = new HootDraftResponse(false, $errors);

    return new JsonResponse($response, $response->responseType());
  }
}
