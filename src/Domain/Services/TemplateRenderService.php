<?php

namespace HootDraft\Domain\Services;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TemplateRenderService {
  private $twig;

  public function __construct(#[Autowire('%kernel.project_dir%/templates/email')] string $templatePath) {
    $this->twig = new Environment(new FilesystemLoader($templatePath));
  }

  public function RenderTemplate($emailTemplateFilename, $parameters) {
    return $this->twig->render($emailTemplateFilename, $parameters);
  }
}
