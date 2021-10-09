<?php

namespace dnj\VsphereInstaller\Contracts;

use dnj\Filesystem\Contracts\IFile;
use dnj\phpvmomi\ManagedObjects\VirtualMachine;

interface IIsoInstaller extends IInstaller
{
    /**
     * @return static
     */
    public function setTarget(?VirtualMachine $target): self;

    public function getTarget(): ?VirtualMachine;

    /**
     * @param array<IFile|string>|null $isoFiles
     *
     * @return static
     */
    public function setIsoFiles(?array $isoFiles): self;

    /**
     * @return array<IFile|string>|null
     */
    public function getIsoFiles(): ?array;

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
    public function install(): self;

    /**
     * @return static
     */
    public function customize(VirtualMachine $vm): self;
}
