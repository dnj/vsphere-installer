<?php

namespace dnj\VsphereInstaller;

use dnj\phpvmomi\ManagedObjects\VirtualMachine;
use dnj\VsphereClone\Contracts\IHandler;
use dnj\VsphereClone\Contracts\ILocation;
use dnj\VsphereClone\ESXiHandler;
use dnj\VsphereClone\VCenterHandler;
use dnj\VsphereInstaller\Contracts\ICloneInstaller;
use Exception;

abstract class CloneInstallerAbstract extends InstallerAbstract implements ICloneInstaller
{
    use CustomizeTrait;

    protected ?ILocation $location = null;

    /**
     * @var VirtualMachine|string|null
     */
    protected $source = null;
    protected ?IHandler $cloneHandler = null;

    public function setSourceByPath(string $path): self
    {
        $this->source = $path;

        return $this;
    }

    public function setSourceByTemplate(VirtualMachine $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setLocation(?ILocation $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): ?ILocation
    {
        return $this->location;
    }

    public function setCloneHandler(?IHandler $cloneHandler): self
    {
        $this->cloneHandler = $cloneHandler;

        return $this;
    }

    public function getCloneHandler(): ?IHandler
    {
        return $this->cloneHandler;
    }

    public function cloneTo(string $name): VirtualMachine
    {
        if (!$this->api) {
            throw new Exception('API is required');
        }
        if (!$this->source) {
            throw new Exception('Source is required');
        }
        if (!$this->cloneHandler) {
            $type = $this->api->getApiType();
            if ('HostAgent' == $type) {
                $this->cloneHandler = new ESXiHandler($this->api, $this->source);
            } elseif ('VirtualCenter' == $type) {
                $this->cloneHandler = new VCenterHandler($this->api, $this->source);
            }
        }
        if (!$this->cloneHandler) {
            throw new Exception('Clone handler is required');
        }
        $this->cloneHandler->setLocation($this->location);
        $this->cloneHandler->makePowerOn(true);
        $this->cloneHandler->makeTemplate(false);
        $vm = $this->cloneHandler->cloneTo($name);
        $this->customize($vm);

        return $vm;
    }
}
