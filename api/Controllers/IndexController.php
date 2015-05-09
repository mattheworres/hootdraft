<?php

namespace PhpDraft\Controllers;

use \Silex\Application;
use \Wukka\Nonce;

class IndexController
{
	public function Index(Application $app) {
		$drafts = \DraftQuery::create()->find();
		return $app->json($drafts->toArray());
	}

  public function GetHash(Application $app) {
    //echo password_hash('password', PASSWORD_BCRYPT) . "\n";
    echo wsse_header('matty', '$2y$10$QFedy2pov0US3SIKH8XBGulhWByyC/3l7iZvpmr0u1B0rBkU/MC/S') . "\n\n";
    return $app->json('blart');
  }
}

function wsse_header($username, $hashedPassword)
{
    //$nonce = hash_hmac('sha512', uniqid(null, true), uniqid(), true);
    //TODO: when used for reals, use the user ID instead of username:
    $wukkaNonce = new Nonce(AUTH_KEY);
    $nonce = $wukkaNonce->create($username);
    $created = new \DateTime('now', new \DateTimeZone('UTC'));
    $created = $created->format(\DateTime::ISO8601);
    $digest = hash('sha512', $nonce . $created . $hashedPassword, true);

    return sprintf(
            'X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $username, base64_encode($digest), base64_encode($nonce), $created
    );
}