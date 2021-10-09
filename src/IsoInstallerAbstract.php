<?php

namespace dnj\VsphereInstaller;

use dnj\Filesystem\Contracts\IFile;
use dnj\Filesystem\Local;
use dnj\phpvmomi\ManagedObjects\Datastore;
use dnj\phpvmomi\ManagedObjects\VirtualMachine;
use dnj\phpvmomi\Utils\Path;
use dnj\phpvmomi\Utils\VirtualMachineConfig;
use dnj\VsphereInstaller\Contracts\IIsoInstaller;
use dnj\VsphereInstaller\Contracts\IPingRepository;
use Exception;

abstract class IsoInstallerAbstract extends InstallerAbstract implements IIsoInstaller
{
    use CustomizeTrait;

    protected ?VirtualMachine $target = null;

    /**
     * @var array<IFile|string>|null
     */
    protected ?array $isoFiles = null;

    protected ?IPingRepository $pingRepository = null;
    protected ?string $pingToken = null;

    /**
     * @var array<array{"local":string|Local\File,"remote":Path}>
     */
    protected array $transferedIsos = [];

    public function setTarget(?VirtualMachine $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getTarget(): ?VirtualMachine
    {
        return $this->target;
    }

    public function setIsoFiles(?array $isoFiles): self
    {
        $this->isoFiles = $isoFiles;

        return $this;
    }

    public function getIsoFiles(): ?array
    {
        return $this->isoFiles;
    }

    public function setPingRepository(?IPingRepository $pingRepository): self
    {
        $this->pingRepository = $pingRepository;

        return $this;
    }

    public function getPingRepository(): ?IPingRepository
    {
        return $this->pingRepository;
    }

    public function setPingToken(?string $pingToken): self
    {
        $this->pingToken = $pingToken;

        return $this;
    }

    public function getPingToken(): ?string
    {
        return $this->pingToken;
    }

    /**
     * @return static
     */
    public function install(): self
    {
        $target = $this->target;
        if (!$target) {
            throw new Exception('Target is required');
        }

        $this->logger->debug('Transfer Iso files to host');
        $paths = $this->transferIsoFilesToHost();

        $this->logger->debug('Mount Iso files');
        $this->mountISOs($paths);

        $this->logger->debug('Make Cdrom boot');
        $this->setBootOrder(['cdrom', 'disk', 'ethernet']);

        if (!$target->isOn()) {
            $this->logger->debug('PowerOn');
            $target->_PowerOnVM_Task()->waitFor(0);
            $target->reloadFromAPI();
        }

        $this->logger->debug('Wait for 10 seconds');
        sleep(10);

        $this->logger->debug('Make disk boot');
        $this->setBootOrder(['disk', 'cdrom', 'ethernet']);

        $this->logger->debug('Wait for sign of life');
        $this->waitForSignOfLife();

        $this->logger->debug('Unmount Iso files');
        $this->unmountISOs();

        $this->logger->debug('Customize');
        $this->customize($target);

        return $this;
    }

    /**
     * @param string[] $order
     */
    protected function setBootOrder(array $order): void
    {
        if (!$this->target) {
            throw new Exception('Target is required');
        }
        $config = VirtualMachineConfig::forVM($this->target);
        $config->setBootOrder($order);
        $this->target->_ReconfigVM_Task($config)->waitFor(0);
        $this->target->reloadFromAPI();
    }

    /**
     * @return Path[]
     */
    protected function transferIsoFilesToHost(): array
    {
        if (!$this->isoFiles) {
            return [];
        }
        if (!$this->api) {
            throw new Exception('API is required');
        }
        if (!$this->target) {
            throw new Exception('Target is required');
        }
        if (null === $this->target->datastore) {
            throw new Exception('Cannot find any sutuble datastore');
        }
        $datastores = array_values((array) $this->target->datastore);
        $datastoreRef = $datastores[0] ?? null;
        if (!$datastoreRef) {
            throw new Exception('Cannot find any sutuble datastore');
        }
        /**
         * @var Datastore
         */
        $datastore = $datastoreRef->get($this->api);

        /**
         * @var Path[]
         */
        $paths = [];
        $this->transferedIsos = [];
        $transport = new FileTransport($datastore);
        $transport->setLogger($this->logger);
        foreach ($this->isoFiles as $isoFile) {
            if (!$isoFile instanceof Local\File and !is_string($isoFile)) {
                throw new Exception('unsupported iso file');
            }
            $remote = $transport->upload($isoFile);
            $paths[] = $remote;
            $this->transferedIsos[] = [
                'local' => $isoFile,
                'remote' => $remote,
            ];
        }

        return $paths;
    }

    /**
     * @return array<array{"local":string|Local\File,"remote":Path}>
     */
    public function getTransferedIsos(): array
    {
        return $this->transferedIsos;
    }

    /**
     * @param Path[] $paths
     */
    protected function mountISOs(array $paths): void
    {
        if (!$this->target) {
            throw new Exception('Target is required');
        }
        if (!$this->target->isOff()) {
            $this->target->_PowerOffVM_Task()->waitFor(0);
        }
        $this->target->reloadFromAPI();
        $config = VirtualMachineConfig::forVM($this->target)->mountISO($paths);
        $this->target->_ReconfigVM_Task($config)->waitFor(0);
        $this->target->reloadFromAPI();
    }

    protected function unmountISOs(): void
    {
        if (!$this->target) {
            throw new Exception('Target is required');
        }
        $config = VirtualMachineConfig::forVM($this->target)->unmountISO();
        $this->target->_ReconfigVM_Task($config)->waitFor(0);
        $this->target->reloadFromAPI();
    }

    /**
     * @return static
     */
    protected function waitForSignOfLife(int $timeout = 3600): self
    {
        if (!$this->target) {
            throw new Exception('Target is required');
        }
        $alive = false;
        $start = time();
        do {
            if ($this->target->guest and 'guestToolsRunning' === $this->target->guest->toolsRunningStatus) {
                $alive = true;
            } elseif ($this->pingToken and $this->pingRepository and $this->pingRepository->findAndDelete($this->pingToken)) {
                $alive = true;
            }
            if (!$alive) {
                sleep(1);
                $this->target->reloadFromAPI();
            }
        } while (!$alive and (0 === $timeout or time() - $start < $timeout));
        if (!$alive) {
            throw new Exception('Timeout');
        }

        return $this;
    }
}
