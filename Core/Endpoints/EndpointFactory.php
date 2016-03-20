<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Endpoints;

use PhpOption\Option;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\ProtocolInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointRouter;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class EndpointFactory extends Service {

    use UsesEndpointRouter;

    protected $basicProtocol;

    protected $endpointsCache = [];

    public function __construct(){
        $this->basicProtocol = new Protocol();
    }

    /**
     * @param string $uri
     * @return EndpointInterface
     * @throws \Exception
     */
    public function createEndpoint($uri){
        if(array_key_exists($uri,$this->endpointsCache)){
            return $this->endpointsCache[$uri];
        }

        $router = $this->getEndpointsRouter();

        try{
            $routeOptions = $router->match($uri);
        }catch(RouteNotFoundException $exception)
        {
            throw new RouteNotFoundException("Endpoint not found for URI: $uri",0,$exception);
        }

        // Get and remove _protocol from the options
        if(!array_key_exists(Protocol::OPTION_PROTOCOL,$routeOptions)) {
            $protocol = $this->basicProtocol;
        }else{
            $protocol = $routeOptions[Protocol::OPTION_PROTOCOL];
            unset($routeOptions[Protocol::OPTION_PROTOCOL]);
        }

        if(!$protocol instanceof ProtocolInterface){
            throw new \InvalidArgumentException("Error trying to create Endpoint for URI: $uri. Expected protocol to be instance of ProtocolInterface.");
        }

        // Resolve options
        $optionsResolver = $this->getOptionsResolver($uri, $routeOptions, $protocol);


        try{
            $options = $optionsResolver->resolve($routeOptions);
        }catch(\Exception $ex){
            throw new \RuntimeException(
                "EndpointFactory failed to resolve options while trying to create endpoint for URI: $uri. "
                ."Original error: ".$ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }

        // Get Consumer, Producer and Handler and remove them from the resolved options

        $consumer = null;
        $producer = null;
        $handler = null;

        if (array_key_exists(Protocol::OPTION_CONSUMER, $options)){
            $consumer = $options[Protocol::OPTION_CONSUMER];
            unset($options[Protocol::OPTION_CONSUMER]);
        }

        if (array_key_exists(Protocol::OPTION_PRODUCER, $options)){
            $producer = $options[Protocol::OPTION_PRODUCER];
            unset($options[Protocol::OPTION_PRODUCER]);
        }

        if (array_key_exists(Protocol::OPTION_HANDLER, $options)){
            $handler = $options[Protocol::OPTION_HANDLER];
            unset($options[Protocol::OPTION_HANDLER]);
        }

        // Create
        $endpoint = new Endpoint($uri,$options,$protocol,$producer,$consumer,$handler);

        // Cache
        $this->endpointsCache[$uri] = $endpoint;

        return $endpoint;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function defineInternalKeys(OptionsResolver $resolver){
        $resolver->setDefined([
            Protocol::OPTION_PRODUCER,
            Protocol::OPTION_HANDLER,
            Protocol::OPTION_CONSUMER,
        ]);
    }

    /**
     * @param string $uri
     * @param array $routeOptions
     * @param ProtocolInterface $protocol
     * @return OptionsResolver
     */
    protected function getOptionsResolver($uri, array &$routeOptions, ProtocolInterface $protocol){
        $optionsResolver = new OptionsResolver();
        $protocol->configureOptionsResolver($optionsResolver);

        $consumer = array_key_exists(Protocol::OPTION_CONSUMER,$routeOptions) ? $routeOptions[Protocol::OPTION_CONSUMER] : $protocol->getDefaultConsumer();
        $producer = array_key_exists(Protocol::OPTION_PRODUCER,$routeOptions) ? $routeOptions[Protocol::OPTION_PRODUCER] : $protocol->getDefaultProducer();
        $handler = array_key_exists(Protocol::OPTION_HANDLER,$routeOptions) ? $routeOptions[Protocol::OPTION_HANDLER] : $protocol->getDefaultHandler();

        // Check Consumer
        if ($consumer) {
            if ($consumer instanceof ConsumerInterface){
                if($consumer instanceof ConfigurableInterface){
                    $consumer->configureOptionsResolver($optionsResolver);
                }
            }else{
                throw new \RuntimeException(
                    "Consumers must implement ConsumerInterface. Found consumer class for endpoint with URI: "
                    .$uri
                    ." that does not implement ConsumerInterface."
                );
            }
        }

        // Check Producer
        if ($producer) {
            if ($producer instanceof ProducerInterface) {
                if($producer instanceof ConfigurableInterface){
                    $producer->configureOptionsResolver($optionsResolver);
                }
            } else {
                throw new \RuntimeException(
                    "Producers must implement ProducerInterface. Found producer class for endpoint with URI: "
                    .$uri
                    ." that does not implement ProducerInterface."
                );
            }
        }

        // Check Handler
        if ($handler) {
            if ($handler instanceof HandlerInterface) {
                if($handler instanceof ConfigurableInterface){
                    $handler->configureOptionsResolver($optionsResolver);
                }
            } else {
                throw new \RuntimeException(
                    "Handlers must implement HandlerInterface. Found handler class for endpoint with URI: "
                    .$uri
                    ." that does not implement HandlerInterface."
                );
            }
        }

        $this->defineInternalKeys($optionsResolver);

        return $optionsResolver;
    }


    static public function resolveURIParams(Exchange $exchange, $uri){
        preg_match_all('/\\{([^{}]+)\\}/', $uri, $matches);
        $params = $matches[1];
        $headers = $exchange->getHeaders();

        if(!empty($params)){
            foreach($params as $param){
                if(array_key_exists($param,$headers)){
                    $uri = str_replace('{'.$param.'}',$headers[$param],$uri);
                }else{
                    throw new EndpointUnrecoverableException("Missing exchange header \"$param\" required to resolve the uri \"$uri\"");
                }
            }
        }

        return $uri;
    }
}