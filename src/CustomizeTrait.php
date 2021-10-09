<?php

namespace dnj\VsphereInstaller;

use dnj\phpvmomi\ManagedObjects\VirtualMachine;
use dnj\VsphereInstaller\Contracts\ICloneCustomizer;
use dnj\VsphereInstaller\Contracts\ICustomization;
use Exception;

trait CustomizeTrait
{
    protected ?ICustomization $customization = null;

    /**
     * @var class-string<ICloneCustomizer>|null
     */
    protected ?string $customizer = null;

    protected ?string $username = null;
    protected ?string $password = null;

    public function setCredentials(string $username, string $password): self
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    public function getCredentials(): ?array
    {
        return (null !== $this->username and null !== $this->password) ? [
            'username' => $this->username,
            'password' => $this->password,
        ] : null;
    }

    public function removeCredentials(): self
    {
        $this->username = null;
        $this->password = null;

        return $this;
    }

    public function setCustomization(?ICustomization $customization = null): self
    {
        $this->customization = $customization;

        return $this;
    }

    public function getCustomization(): ?ICustomization
    {
        return $this->customization;
    }

    public function setCustomizer(?string $customizer): self
    {
        $this->customizer = $customizer;

        return $this;
    }

    public function getCustomizer(): ?string
    {
        return $this->customizer;
    }

    public function customize(VirtualMachine $vm): self
    {
        if (!$this->customization) {
            return $this;
        }
        if (!$this->customizer) {
            throw new Exception('Customization are requested but there is no customizer');
        }
        $className = $this->customizer;
        $customizer = new $className($vm, $this->customization, $this);
        $customizer->start();

        return $this;
    }
}
