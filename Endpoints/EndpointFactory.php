<?php

namespace Smartbox\Integration\FrameworkBundle\Endpoints;

use Smartbox\Integration\FrameworkBundle\Exceptions\EndpointUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEndpointRouter;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class EndpointFactory extends Service {

    use UsesEndpointRouter;

    protected $endpointsCache = [];

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
            $params = $router->match($uri);
        }catch(RouteNotFoundException $exception)
        {
            throw new RouteNotFoundException("Endpoint not found for URI: $uri",0,$exception);
        }

        if(!array_key_exists(EndpointInterface::OPTION_CLASS,$params)) {
            $className = Endpoint::class;
        }else{
            $className = $params[EndpointInterface::OPTION_CLASS];
            unset($params[EndpointInterface::OPTION_CLASS]);
        }

        if(!in_array(EndpointInterface::class, class_implements($className))){
            throw new \InvalidArgumentException("Expected class implementing EndpointInterface, $className given");
        }

        $endpoint = new $className($uri,$params);
        $this->endpointsCache[$uri] = $endpoint;
        return $endpoint;
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