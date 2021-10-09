<?php

namespace dnj\VsphereInstaller\Contracts;

use dnj\phpvmomi\API;
use Psr\Log\LoggerAwareInterface;

interface IInstaller extends LoggerAwareInterface
{
    /**
     * @return static
     */
    public function setAPI(?API $api): self;

    public function getAPI(): ?API;
}
