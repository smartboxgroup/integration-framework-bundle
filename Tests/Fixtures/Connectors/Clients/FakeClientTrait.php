<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Connectors\Clients;

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
     * Method to ensure that fake client was initialised correctly
     * @throws \RuntimeException
     */
    protected function checkInitialisation()
    {
       if(!$this->initialised) {
           throw new \RuntimeException('Fake client is not initialised.');
       }
    }

    protected function getResponseFromCache($resource, $suffix = null)
    {
        $file = $this->cacheDir . DIRECTORY_SEPARATOR . $resource . (($suffix)? '.' . $suffix : null);
        $fixturePath = $this->fileLocator->locate($file);
        return file_get_contents($fixturePath);
    }

    protected function setResponseInCache($resource, $content, $suffix = null)
    {
        if (!in_array($this->actionName, $this->cacheExclusions)) {
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0777, true);
            }
            file_put_contents(
                $this->cacheDir . DIRECTORY_SEPARATOR . $resource . (($suffix)? '.' . $suffix : null),
                $content
            );
        }
    }
}