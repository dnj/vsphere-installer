<?php

namespace dnj\VsphereInstaller\Contracts;

use dnj\phpvmomi\ManagedObjects\VirtualMachine;

interface ICloneCustomizer
{
    public function __construct(VirtualMachine $vm, ICustomization $customization, ICloneInstaller $installer);

    public function getCustomization(): ICustomization;

    public function getVirtualMachine(): VirtualMachine;

    public function getCloneInstaller(): ICloneInstaller;

    /**
     * @return static
     */
    public function start(): self;
}
