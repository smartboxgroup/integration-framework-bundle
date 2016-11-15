<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use BeSimple\SoapCommon\Cache;
use BeSimple\SoapClient\Curl;
use Symfony\Component\Config\FileLocatorInterface;

trait FakeClientTrait
{
    protected $initialised = false;

    protected $actionName;

    /**
     * @var FileLocatorInterface
     */
    protected $fileLocator;

    protected $cacheDir;
    protected $cacheExclusions = [];

    /**
     * @param FileLocatorInterface $fileLocator
     * @param $cacheDir
     * @param $cacheExclusions
     */
    public function init(FileLocatorInterface $fileLocator, $cacheDir, $cacheExclusions)
    {
        $this->fileLocator = $fileLocator;
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
        $this->cacheExclusions = $cacheExclusions;

        $this->initialised = true;
    }

    /**
     * Method to ensure that fake client was initialised correctly.
     *
     * @throws \RuntimeException
     */
    protected function checkInitialisation()
    {
        if (!$this->initialised) {
            throw new \RuntimeException('Fake client is not initialised.');
        }
    }

    protected function getResponseFromCache($resource, $suffix = null)
    {
        $file = $this->getFileName($resource, $suffix);
        $fixturePath = $this->fileLocator->locate($file);

        return file_get_contents($fixturePath);
    }

    protected function setResponseInCache($resource, $content, $suffix = null)
    {
        if (!in_array($this->actionName, $this->cacheExclusions)) {
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0777, true);
            }

            file_put_contents($this->getFileName($resource, $suffix), $content);
        }
    }

    protected function saveWsdlToCache($wsdl, $options)
    {
        $originalWsdlCacheDir = Cache::getDirectory();
        Cache::setDirectory($this->cacheDir);

        $wsdlDownloader = new WsdlDownloader(new Curl($options));
        $cachedFile = $wsdlDownloader->download($wsdl);

        Cache::setDirectory($originalWsdlCacheDir);

        return $cachedFile;
    }

    protected function getWsdlPathFromCache($wsdl, $options)
    {
        $originalWsdlCacheDir = Cache::getDirectory();
        Cache::setDirectory($this->cacheDir);

        $wsdlDownloader = new WsdlDownloader(new Curl($options));
        $cachedFile = $wsdlDownloader->cacheFileFromWsdl($wsdl);

        Cache::setDirectory($originalWsdlCacheDir);

        return $cachedFile;
    }

    protected function getFileName($resource, $suffix = null)
    {
        return $this->cacheDir.DIRECTORY_SEPARATOR.$resource.(($suffix) ? '.'.$suffix : null);
    }
}
