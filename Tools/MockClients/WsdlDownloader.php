<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use BeSimple\SoapClient\WsdlDownloader as BeSimpleSoapWsdlDownloader;
use BeSimple\SoapCommon\Helper;


class WsdlDownloader extends BeSimpleSoapWsdlDownloader
{
    public function cacheFileFromWsdl($wsdl)
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'wsdl_' . md5($wsdl) . '.cache';
    }

    protected function extractFilePartFromCachePath($path)
    {
        return str_replace($this->cacheDir . DIRECTORY_SEPARATOR, '', $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveRemoteIncludes($xml, $cacheFilePath, $parentFilePath = null)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace(Helper::PFX_XML_SCHEMA, Helper::NS_XML_SCHEMA);
        $xpath->registerNamespace(Helper::PFX_WSDL, Helper::NS_WSDL);

        // WSDL include/import
        $query = './/' . Helper::PFX_WSDL . ':include | .//' . Helper::PFX_WSDL . ':import';
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                $location = $node->getAttribute('location');
                if ($this->isRemoteFile($location)) {
                    $location = $this->extractFilePartFromCachePath($this->download($location));
                    $node->setAttribute('location', $location);
                } elseif (null !== $parentFilePath) {
                    $location = $this->resolveRelativePathInUrl($parentFilePath, $location);
                    $location = $this->extractFilePartFromCachePath($this->download($location));
                    $node->setAttribute('location', $location);
                }
            }
        }

        // XML schema include/import
        $query = './/' . Helper::PFX_XML_SCHEMA . ':include | .//' . Helper::PFX_XML_SCHEMA . ':import';
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                if ($node->hasAttribute('schemaLocation')) {
                    $schemaLocation = $node->getAttribute('schemaLocation');
                    if ($this->isRemoteFile($schemaLocation)) {
                        $schemaLocation = $this->extractFilePartFromCachePath($this->download($schemaLocation));
                        $node->setAttribute('schemaLocation', $schemaLocation);
                    } elseif (null !== $parentFilePath) {
                        $schemaLocation = $this->resolveRelativePathInUrl($parentFilePath, $schemaLocation);
                        $schemaLocation = $this->extractFilePartFromCachePath($this->download($schemaLocation));
                        $node->setAttribute('schemaLocation', $schemaLocation);
                    }
                }
            }
        }

        $doc->save($cacheFilePath);
    }
}
