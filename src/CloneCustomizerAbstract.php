<?php

namespace dnj\VsphereInstaller;

use dnj\phpvmomi\API;
use dnj\phpvmomi\ManagedObjects\VirtualMachine;
use dnj\VsphereInstaller\Contracts\ICloneCustomizer;
use dnj\VsphereInstaller\Contracts\ICloneInstaller;
use dnj\VsphereInstaller\Contracts\ICustomization;

abstract class CloneCustomizerAbstract implements ICloneCustomizer
{
    protected VirtualMachine $vm;
    protected ICustomization $customization;
    protected ICloneInstaller $installer;
    protected API $api;

    public function __construct(VirtualMachine $vm, ICustomization $customization, ICloneInstaller $installer)
    {
        $this->vm = $vm;
        $this->customization = $customization;
        $this->installer = $installer;
        $this->api = $vm->getAPI();
    }

    public function getVirtualMachine(): VirtualMachine
    {
        return $this->vm;
    }

    public function getCustomization(): ICustomization
    {
        return $this->customization;
    }

    public function getCloneInstaller(): ICloneInstaller
    {
        return $this->installer;
    }
}
