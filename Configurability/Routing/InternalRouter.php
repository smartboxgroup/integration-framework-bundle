<?php

namespace Smartbox\Integration\FrameworkBundle\Configurability\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class InternalRouter.
 */
class InternalRouter extends Router
{
    const KEY_ITINERARY = '_itinerary';
    const KEY_PRODUCER = '_producer';

    const OPTION_USERNAME = 'username';
    const OPTION_PASS = 'pass';
    const OPTION_FRAGMENT = 'fragment';
    const OPTION_PORT = 'port';

    /** @var ContainerInterface */
    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     * @param mixed              $resource  The main resource to load
     * @param array              $options   An array of options
     */
    public function __construct(ContainerInterface $container, $resource, array $options = [])
    {
        $this->container = $container;
        parent::__construct($container, $resource, $options, null);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $res = $this->getGenerator()->generate($name, $parameters, $referenceType);
        if ($res[0] == '/') {
            $res = substr($res, 1);
        }

        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function match($uri)
    {
        /*
         * scheme - e.g. http
         * host
         * port
         * user
         * pass
         * path
         * query - after the question mark ?
         * fragment - after the hashmark #
         */
        $query = parse_url($uri, PHP_URL_QUERY);
        $user = parse_url($uri, PHP_URL_USER);
        $pass = parse_url($uri, PHP_URL_PASS);
        $fragment = parse_url($uri, PHP_URL_FRAGMENT);
        $port = parse_url($uri, PHP_URL_PORT);
        $host = parse_url($uri, PHP_URL_HOST);
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        $path = parse_url($uri, PHP_URL_PATH);

        $internalPath = '';
        $options = [];

        // Build internal path
        if ($scheme) {
            $internalPath .= $scheme.'://';
        }
        if ($host) {
            $internalPath .= $host;
        }
        if ($path) {
            $internalPath .= $path;
        }

        // Build options
        if ($query) {
            parse_str($query, $options);
        }

        if ($user) {
            $options[self::OPTION_USERNAME] = $user;
        }
        if ($pass) {
            $options[self::OPTION_PASS] = $pass;
        }
        if ($fragment) {
            $options[self::OPTION_FRAGMENT] = $fragment;
        }
        if ($port) {
            $options[self::OPTION_PORT] = $port;
        }

        if ($scheme) {
            $internalPath = '/'.$internalPath;
        }

        try {
            $result = parent::match($internalPath);
        } catch (ResourceNotFoundException $e) {
            throw new InternalRouterResourceNotFound("Internal resource is not found: $uri");
        }

        $result = array_merge($result, $options);
        $this->resolveServices($result);

        return $result;
    }

    /**
     * @param array $array
     */
    public function resolveServices(array &$array)
    {
        foreach ($array as $key => $value) {
            if (strpos($value, '@') === 0) {
                $array[$key] = $this->container->get(substr($value, 1));
            }
        }
    }
}
