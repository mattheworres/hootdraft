<?php
namespace PhpDraft\Config\Security;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use PhpDraft\Config\Security\WsseUserToken;
use Symfony\Component\Security\Core\Util\StringUtils;
use Wukka\Nonce;

class WsseProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $cacheDir;

    public function __construct(UserProviderInterface $userProvider, $cacheDir)
    {
        $this->userProvider = $userProvider;
        $this->cacheDir     = $cacheDir;
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->userProvider->loadUserByUsername($token->getUsername());

        if ($user && $this->validateDigest($token->digest, $token->nonce, $token->created, $user->getUsername())) {

            $authenticatedToken = new WsseUserToken($user->getRoles());
            echo "So, whatd we get? $authenticatedToken";

            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The WSSE authentication failed.');
    }

    /**
     * This function is specific to Wsse authentication and is only used to help this example
     *
     * For more information specific to the logic here, see
     * https://github.com/symfony/symfony-docs/pull/3134#issuecomment-27699129
     */
    protected function validateDigest($digest, $nonce, $created, $secret)
    {
        // Check created time is not in the future
        $time = time();
        $timeout = 300;

        if (strtotime($created) > $time) {
            echo "time is future";
            return false;
        }

        // Expire timestamp after 5 minutes
        
        $yarp = strtotime($created);
        $amount = $time - $yarp;
        
        if ($time - strtotime($created) > $timeout) {
            echo "So the seconds passed is $amount ($time minus $yarp) which is more than the timeout of $timeout\n\n";
            echo "Timestamp is after limit.";
            return false;
        }

        // Validate that the nonce is *not* used in the last 5 minutes
        // if it has, this could be a replay attack
        $filesafe_nonce = urlencode($nonce);
        
        if (file_exists($this->cacheDir.'/'.$filesafe_nonce) && (file_get_contents($this->cacheDir.'/'.$filesafe_nonce) + $timeout) > $time) {
            //TODO: uncomment once we verify everything else works... then we can fix this
            //throw new NonceExpiredException('Previously used nonce detected');
        }

        // If cache directory does not exist we create it
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }

        file_put_contents($this->cacheDir.'/'.$filesafe_nonce, $time);

        // Validate Secret
        $nonceClass = new Nonce(AUTH_KEY);
        return $nonceClass->check(base64_decode($nonce), $secret);
        //$expected = base64_encode(sha1(base64_decode($nonce).$created.$secret, true));

        //return StringUtils::equals($expected, $digest);
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof WsseUserToken;
    }
}