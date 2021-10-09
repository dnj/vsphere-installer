<?php

namespace dnj\VsphereInstaller\Contracts;

use dnj\phpvmomi\ManagedObjects\VirtualMachine;

interface IPingRepository
{
    public function generateToken(VirtualMachine $target): string;

    public function findAndDelete(string $token): bool;
}
