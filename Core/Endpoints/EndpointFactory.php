<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Endpoints;

use Psr\Log\LoggerInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouterResourceNotFound;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\ProtocolInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointRouter;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EndpointFactory.
 */
class EndpointFactory extends Service
{
    const MODE_CONSUME = 'consume';
    const MODE_PRODUCE = 'produce';

    use UsesEndpointRouter;

    /** @var Protocol */
    protected $basicProtocol;

    /** @var array */
    protected $endpointsCache = [];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->basicProtocol = new Protocol();
    }

    /**
     * @param string               $uri
     * @param string               $mode
     * @param LoggerInterface|null $logger
     *
     * @return mixed|Endpoint
     */
    public function createEndpoint($uri, $mode = self::MODE_PRODUCE, LoggerInterface $logger = null)
    {
        if (array_key_exists($uri, $this->endpointsCache)) {
            return $this->endpointsCache[$uri];
        }

        $router = $this->getEndpointsRouter();

        try {
            $routeOptions = $router->match($uri);
        } catch (InternalRouterResourceNotFound $exception) {
            throw new InternalRouterResourceNotFound(
                "Endpoint not found for URI: $uri",
                $exception->getCode(),
                $exception
            );
        }

        // Get and remove _protocol from the options
        if (!array_key_exists(Protocol::OPTION_PROTOCOL, $routeOptions)) {
            $protocol = $this->basicProtocol;
        } else {
            $protocol = $routeOptions[Protocol::OPTION_PROTOCOL];
            unset($routeOptions[Protocol::OPTION_PROTOCOL]);
        }

        if (!$protocol instanceof ProtocolInterface) {
            throw new \InvalidArgumentException("Error trying to create Endpoint for URI: $uri. Expected protocol to be instance of ProtocolInterface.");
        }

        // Resolve options
        $optionsResolver = $this->getOptionsResolver($uri, $routeOptions, $protocol, $mode);

        try {
            $options = $optionsResolver->resolve($routeOptions);
        } catch (\Exception $ex) {
            throw new \RuntimeException(
                "EndpointFactory failed to resolve options while trying to create endpoint for URI: $uri. "
                .'Original error: '.$ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }

        // Get Consumer, Producer and Handler and remove them from the resolved options

        $consumer = null;
        $producer = null;
        $handler = null;

        if (array_key_exists(Protocol::OPTION_CONSUMER, $options)) {
            $consumer = $options[Protocol::OPTION_CONSUMER];
            unset($options[Protocol::OPTION_CONSUMER]);
        }

        if (array_key_exists(Protocol::OPTION_PRODUCER, $options)) {
            $producer = $options[Protocol::OPTION_PRODUCER];
            unset($options[Protocol::OPTION_PRODUCER]);
        }

        if (array_key_exists(Protocol::OPTION_HANDLER, $options)) {
            $handler = $options[Protocol::OPTION_HANDLER];
            unset($options[Protocol::OPTION_HANDLER]);
        }

        // Create
        $endpoint = new Endpoint($uri, $options, $protocol, $producer, $consumer, $handler);
        $endpoint->setLogger($logger);

        // Cache
        $this->endpointsCache[$uri] = $endpoint;

        return $endpoint;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function defineInternalKeys(OptionsResolver $resolver)
    {
        $resolver->setDefined([
            Protocol::OPTION_PRODUCER,
            Protocol::OPTION_HANDLER,
            Protocol::OPTION_CONSUMER,
        ]);
    }

    /**
     * @param $uri
     * @param array             $routeOptions
     * @param ProtocolInterface $protocol
     * @param string            $mode
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver($uri, array &$routeOptions, ProtocolInterface $protocol, $mode)
    {
        $optionsResolver = new OptionsResolver();
        $protocol->configureOptionsResolver($optionsResolver);

        $consumer = array_key_exists(Protocol::OPTION_CONSUMER, $routeOptions) ? $routeOptions[Protocol::OPTION_CONSUMER] : $protocol->getDefaultConsumer();
        $producer = array_key_exists(Protocol::OPTION_PRODUCER, $routeOptions) ? $routeOptions[Protocol::OPTION_PRODUCER] : $protocol->getDefaultProducer();
        $handler = array_key_exists(Protocol::OPTION_HANDLER, $routeOptions) ? $routeOptions[Protocol::OPTION_HANDLER] : $protocol->getDefaultHandler();

        // Check Consumer
        if (self::MODE_CONSUME == $mode && $consumer) {
            if ($consumer instanceof ConsumerInterface) {
                if ($consumer instanceof ConfigurableInterface) {
                    $consumer->configureOptionsResolver($optionsResolver);
                }
            } else {
                throw new \RuntimeException(
                    'Consumers must implement ConsumerInterface. Found consumer class for endpoint with URI: '
                    .$uri
                    .' that does not implement ConsumerInterface.'
                );
            }
        }

        // Check Producer
        if (self::MODE_PRODUCE == $mode && $producer) {
            if ($producer instanceof ProducerInterface) {
                if ($producer instanceof ConfigurableInterface) {
                    $producer->configureOptionsResolver($optionsResolver);
                }
            } else {
                throw new \RuntimeException(
                    'Producers must implement ProducerInterface. Found producer class for endpoint with URI: '
                    .$uri
                    .' that does not implement ProducerInterface.'
                );
            }
        }

        // Check Handler
        if ($handler) {
            if ($handler instanceof HandlerInterface) {
                if ($handler instanceof ConfigurableInterface) {
                    $handler->configureOptionsResolver($optionsResolver);
                }
            } else {
                throw new \RuntimeException(
                    'Handlers must implement HandlerInterface. Found handler class for endpoint with URI: '
                    .$uri
                    .' that does not implement HandlerInterface.'
                );
            }
        }

        $this->defineInternalKeys($optionsResolver);

        return $optionsResolver;
    }

    /**
     * @param Exchange $exchange
     * @param string   $uri
     *
     * @return mixed
     */
    public static function resolveURIParams(Exchange $exchange, $uri)
    {
        preg_match_all('/\\{([^{}]+)\\}/', $uri, $matches);
        $params = $matches[1];
        $headers = $exchange->getHeaders();

        if (!empty($params)) {
            foreach ($params as $param) {
                if (array_key_exists($param, $headers)) {
                    $uri = str_replace('{'.$param.'}', $headers[$param], $uri);
                } else {
                    throw new \RuntimeException(
                        "Missing exchange header \"$param\" required to resolve the uri \"$uri\""
                    );
                }
            }
        }

        return $uri;
    }
}
