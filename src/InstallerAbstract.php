<?php

namespace dnj\VsphereInstaller;

use dnj\phpvmomi\API;
use dnj\VsphereInstaller\Contracts\IInstaller;
use Psr\Log\LoggerAwareTrait;

abstract class InstallerAbstract implements IInstaller
{
    use LoggerAwareTrait;

    protected ?API $api = null;

    public function setAPI(?API $api): self
    {
        $this->api = $api;

        return $this;
    }

    public function getAPI(): ?API
    {
        return $this->api;
    }
}
