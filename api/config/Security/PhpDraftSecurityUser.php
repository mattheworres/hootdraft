<?php
namespace PhpDraft\Config\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\Util\StringUtils;

class PhpDraftSecurityUser implements UserInterface, EquatableInterface
{
    private $email;
    private $name;
    private $enabled;
    private $password;
    private $salt;
    private $roles;
    private $verificationKey;

    public function __construct($email, $name, $password, $salt, array $roles, $enabled, $verificationKey)
    {
        $this->email = $email;
        $this->name = $name;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->enabled = $enabled;
        $this->verificationKey = $verificationKey;
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
        return $this->email;
    }

    public function getName() {
        return $this->name;
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

    public function hasVerificationKey() {
        return !empty($this->verificationKey);
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

        if ($this->email !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}