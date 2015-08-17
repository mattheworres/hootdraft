<?php

namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
  public function Style(Application $app) {
    $colors = $app['phpdraft.DraftDataRepository']->GetPositionColors();

    $minified_css = '';

    foreach($colors as $position => $hex_color_key) {
      $minified_css .= "div.pick$position{background-color:$hex_color_key;}";
    }

    return new Response($minified_css, 200, array(
        "Content-Type" => "text/css"
    ));
  }
}