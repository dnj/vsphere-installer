<?php

namespace dnj\VsphereInstaller;

use dnj\Filesystem\Local;
use dnj\Filesystem\Tmp;
use dnj\phpvmomi\API;
use dnj\phpvmomi\ManagedObjects\Datastore;
use dnj\phpvmomi\ManagedObjects\HostDatastoreBrowser;
use dnj\phpvmomi\ManagedObjects\HostSystem;
use dnj\phpvmomi\Utils\Path;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FileTransport implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected API $api;
    protected Datastore $datastore;

    public function __construct(Datastore $datastore)
    {
        $this->datastore = $datastore;
        $this->api = $datastore->getAPI();
    }

    public function getAPI(): API
    {
        return $this->api;
    }

    public function getDatastore(): Datastore
    {
        return $this->datastore;
    }

    /**
     * @param Local\File|string $local
     */
    public function upload($local): Path
    {
        if ('HostAgent' == $this->api->getApiType() and is_string($local) and $this->isValidUrlForEsxi($local)) {
            $this->logger->info('File is a Valid url and api type is HostAgent, so downloading directly using ssh', ['url' => $local]);
            try {
                return $this->downloadInEsxi($local);
            } catch (\Exception $e) {
                $this->logger->error('Direct download has failed, try with local download', ['url' => $local, 'exception' => $e]);
            }
        }

        if (is_string($local)) {
            $this->logger->debug('Downloading', ['url' => $local]);
            $local = $this->downloadURL($local);
            $this->logger->debug('Downloaded', ['path' => $local->getPath()]);
        }
        $this->logger->debug('Check for existance of file on datastore');
        $remotePath = $this->datastore->getPath('iso/'.$local->md5().'.iso');
        if (!$this->fileExists($remotePath)) {
            $this->logger->debug('File does not exists and need to upload', ['url' => $remotePath->toURL($this->api)]);
            $this->api->getFileManager()->upload($remotePath->toURL($this->api), $local);
        } else {
            $this->logger->debug('File already exists and does not need to upload again', ['url' => $remotePath->toURL($this->api)]);
        }

        return $remotePath;
    }

    protected function isValidUrlForEsxi(string $str): bool
    {
        $supportHttps = $this->api->getServiceContent()->about->version >= '6.7';

        return 1 == preg_match('/^http'.($supportHttps ? 's?' : '').":\/\//", $str);
    }

    protected function downloadInEsxi(string $url): Path
    {
        $remotePath = $this->datastore->getPath('iso/'.md5($url).'.iso');
        if ($this->fileExists($remotePath)) {
            $this->logger->debug('File already exists and does not need to download again', ['url' => $remotePath->toURL($this->api)]);

            return $remotePath;
        }
        $this->logger->debug('Connecting to Esxi SSH');
        $host = (new HostSystem($this->api))->byID('ha-host');
        $ssh = (new EsxiSSHConnector($host))->connect();

        $dirname = "/vmfs/volumes/{$remotePath->datastore}/".dirname($remotePath->path);
        $process = $ssh->execute(['mkdir', '-p', $dirname]);
        $process = $ssh->execute([
            'wget',
            '-O', $dirname.'/'.$remotePath->getBasename(),
            $url,
        ]);

        return $remotePath;
    }

    protected function downloadURL(string $url): Tmp\File
    {
        $file = new Tmp\File();
        $fd = fopen($file->getPath(), 'w');
        if (!$fd) {
            throw new Exception('Cannot open '.$file->getPath());
        }

        $curl = curl_init($url);
        if (!$curl) {
            throw new Exception('Cannot init curl');
        }
        curl_setopt_array($curl, [
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FILE => $fd,
        ]);
        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        fclose($fd);
        if (true !== $result or $info['http_code'] >= 400) {
            throw new Exception("http code: {$info['http_code']}");
        }

        return $file;
    }

    protected function fileExists(Path $path): bool
    {
        /**
         * @var HostDatastoreBrowser
         */
        $browser = $this->datastore->browser->init($this->api);

        return null !== $browser->getFileByPath($path->toDSPath());
    }
}
