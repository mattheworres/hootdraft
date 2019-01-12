<?php
namespace PhpDraft\Domain\Services;

class TemplateRenderService {
  private $twig;

  public function __construct() {
    $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../EmailTemplates');
    $this->twig = new \Twig_Environment($loader);
  }

  public function RenderTemplate($emailTemplateFilename, $parameters) {
    return $this->twig->render($emailTemplateFilename, $parameters);
  }
}
