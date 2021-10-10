<?php

namespace dnj\VsphereInstaller;

use dnj\phpvmomi\ManagedObjects\HostSystem;
use dnj\SSH\Native\Connection;
use dnj\SSH\Native\Connector;
use Exception;

class EsxiSSHConnector
{
    protected HostSystem $host;

    public function __construct(HostSystem $host)
    {
        if ('HostAgent' != $host->getAPI()->getApiType()) {
            throw new Exception('You cannot connect to Esxi ssh if connected to VCenter API');
        }
        $this->host = $host;
    }

    public function getHost(): HostSystem
    {
        return $this->host;
    }

    public function getSSHPort(): int
    {
        if (!isset($this->host->config->firewall->ruleset)) {
            throw new Exception('incomplete HostSystem object');
        }
        $ruleset = null;
        foreach ($this->host->config->firewall->ruleset as $key => $rule) {
            if ('sshServer' == $key) {
                $ruleset = $rule;
                break;
            }
        }
        if (!$ruleset) {
            return 22;
        }
        foreach ($ruleset->rule as $rule) {
            if ('inbound' == $rule->direction) {
                return $rule->port;
            }
        }

        return 22;
    }

    public function getHostname(): string
    {
        $host = parse_url($this->host->getAPI()->getOption('sdk'), PHP_URL_HOST);
        if (!$host) {
            throw new Exception('Cannot find hostname from API SDK URL');
        }

        return $host;
    }

    public function connect(): Connection
    {
        $host = $this->getHostname();
        $port = $this->getSSHPort();
        $this->testThePort($host, $port);
        $username = $this->host->getAPI()->getOption('username');
        $password = $this->host->getAPI()->getOption('password');

        return (new Connector())
            ->connect($host, $port)
            ->loginByPassword($username, $password);
    }

    protected function testThePort(string $host, int $port): void
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 10);
        if (false !== $fp) {
            fclose($fp);

            return;
        }
        throw new Exception("Cannot open connection to {$host}:{$port} with-in 10 seconds");
    }
}
