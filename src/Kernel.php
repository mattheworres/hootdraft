<?php

namespace HootDraft;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
  use MicroKernelTrait;

  public function boot(): void
  {
    // Fail fast: an empty secret would silently weaken everything signed with kernel.secret
    if ($this->environment === 'prod' && ($_SERVER['APP_SECRET'] ?? '') === '') {
      throw new \RuntimeException('APP_SECRET is empty. Set it in .env.local (see deploy/env.local.ci) or the environment.');
    }

    parent::boot();
  }
}
