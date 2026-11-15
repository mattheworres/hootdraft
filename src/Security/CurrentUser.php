<?php

namespace HootDraft\Security;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Repositories\LoginUserRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Who is making this request, from the session cookie. Replaces the old LoginUserService::GetCurrentUser()
 * and GetUserFromHeaderToken(). Works on public routes too (the firewall is lazy): null means anonymous.
 */
class CurrentUser
{
  public function __construct(private Security $security, private LoginUserRepository $repository)
  {
  }

  public function get(): ?User
  {
    $user = $this->security->getUser();

    return $user instanceof User ? $user : null;
  }

  /** The full database entity, for code ported from the old services that expects a LoginUser. */
  public function loginUser(): ?LoginUser
  {
    $user = $this->get();

    return $user === null ? null : $this->repository->Load($user->getEmail());
  }
}
