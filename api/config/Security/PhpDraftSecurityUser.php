<?php
namespace PhpDraft\Config\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\Util\StringUtils;

class PhpDraftSecurityUser implements UserInterface, EquatableInterface
{
    private $username;
    private $email;
    private $enabled;
    private $password;
    private $salt;
    private $roles;
    private $verificationKey;

    public function __construct($username, $email, $password, $salt, array $roles, $enabled, $verificationKey)
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->enabled = $enabled;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function eraseCredentials()
    {
    }

    public function isEnabled() {
        return $this->enabled;
    }

    public function verificationKeyMatches($provided_key) {
        return StringUtils::equals($this->verificationKey, $provided_key);
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof PhpDraftSecurityUser) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}