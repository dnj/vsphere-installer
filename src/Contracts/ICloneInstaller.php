<?php

namespace dnj\VsphereInstaller\Contracts;

use dnj\phpvmomi\ManagedObjects\VirtualMachine;
use dnj\VsphereClone\Contracts\IHandler;
use dnj\VsphereClone\Contracts\ILocation;

interface ICloneInstaller extends IInstaller
{
    public function setSourceByPath(string $path): self;

    public function setSourceByTemplate(VirtualMachine $source): self;

    /**
     * @return VirtualMachine|string|null
     */
    public function getSource();

    /**
     * @return static
     */
    public function setLocation(?ILocation $location): self;

    public function getLocation(): ?ILocation;

    /**
     * @return static
     */
    public function setCredentials(string $username, string $password): self;

    /**
     * @return array{"username":string,"password":string}|null
     */
    public function getCredentials(): ?array;

    /**
     * @return static
     */
    public function removeCredentials(): self;

    /**
     * @return static
     */
    public function setCustomization(?ICustomization $customization = null): self;

    public function getCustomization(): ?ICustomization;

    /**
     * @param class-string<ICloneCustomizer>|null $customizer
     *
     * @return static
     */
    public function setCustomizer(?string $customizer): self;

    /**
     * @return class-string<ICloneCustomizer>|null
     */
    public function getCustomizer(): ?string;

    /**
     * @return static
     */
    public function setCloneHandler(?IHandler $cloneHandler): self;

    public function getCloneHandler(): ?IHandler;

    public function cloneTo(string $name): VirtualMachine;

    /**
     * @return static
     */
    public function customize(VirtualMachine $vm): self;
}
