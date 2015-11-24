<?php


namespace Smartbox\Integration\FrameworkBundle\Helper;


use Smartbox\Integration\FrameworkBundle\Connectors\Connector;
use Symfony\Component\DependencyInjection\ContainerAware;

class EndpointHelper extends ContainerAware
{
    const ENDPOINT_PREFIX = "endpoint.";

    static public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '_', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'na';
        }

        return $text;
    }

    public static function getIdForURI($uri)
    {
        return EndpointHelper::ENDPOINT_PREFIX.EndpointHelper::slugify($uri);
    }

    /**
     * @param $uri
     * @return Connector
     */
    public function getEndpointByURI($uri)
    {
        $id = self::getIdForURI($uri);

        return $this->container->get($id);
    }

    /**
     * @param $identifier
     * @return Connector
     */
    public function getEndpoint($identifier)
    {
        if ($this->container->has($identifier)) {
            return $this->container->get($identifier);
        } else {
            return $this->getEndpointByURI($identifier);
        }
    }

    /**
     * @param $scheme
     * @param $host
     * @param $path
     * @return Connector
     */
    public function getEndpointByParams($scheme, $host, $path)
    {
        return $this->getEndpointByURI($scheme."://".$host.$path);
    }
}