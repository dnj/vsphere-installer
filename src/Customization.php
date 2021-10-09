<?php

namespace dnj\VsphereInstaller;

use dnj\VsphereInstaller\Contracts\ICustomization;

/**
 * @phpstan-import-type NetworkSetup from ICustomization
 */
class Customization implements ICustomization
{
    /**
     * @var NetworkSetup|null
     */
    protected ?array $network = null;
    protected ?string $timezone = null;
    protected string $password = '';
    protected bool $icmp = true;
    protected bool $autoUpdate = true;

    public function setNetwork(?array $options): self
    {
        $this->network = $options;

        return $this;
    }

    public function getNetwork(): ?array
    {
        return $this->network;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function enableICMP(bool $enable = true): self
    {
        $this->icmp = $enable;

        return $this;
    }

    public function getICMP(): bool
    {
        return $this->icmp;
    }

    public function enableAutoUpdate(bool $enable = true): self
    {
        $this->autoUpdate = $enable;

        return $this;
    }

    public function getAutoUpdate(): bool
    {
        return $this->autoUpdate;
    }
}
