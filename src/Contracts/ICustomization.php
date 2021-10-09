<?php

namespace dnj\VsphereInstaller\Contracts;

/**
 * @phpstan-type NetworkSetup array{"identifier":string,"ipv4":array{"dhcp"?:bool,"address"?:string,"netmask"?:string,"gateway"?:string},"dns-servers"?:string[]}|null
 */
interface ICustomization
{
    /**
     * @param NetworkSetup $options
     *
     * @return static
     */
    public function setNetwork(?array $options): self;

    /**
     * @return NetworkSetup
     */
    public function getNetwork(): ?array;

    /**
     * @return static
     */
    public function setTimezone(?string $timezone): self;

    public function getTimezone(): ?string;

    /**
     * @return static
     */
    public function setPassword(string $password): self;

    public function getPassword(): string;

    /**
     * @return static
     */
    public function enableICMP(bool $enable = true): self;

    public function getICMP(): bool;

    /**
     * @return static
     */
    public function enableAutoUpdate(bool $enable = true): self;

    public function getAutoUpdate(): bool;
}
