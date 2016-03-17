<?php

namespace Smartbox\Integration\FrameworkBundle\Endpoints;


use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEndpointRouter;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class EndpointFactory extends Service {

    use UsesEndpointRouter;

    /**
     * @param string $uri
     * @return EndpointInterface
     * @throws \Exception
     */
    public function createEndpoint($uri){
        $router = $this->getEndpointsRouter();

        try{
            $params = $router->match($uri);
        }catch(RouteNotFoundException $exception)
        {
            throw new RouteNotFoundException("Endpoint not found for URI: $uri",0,$exception);
        }

        if(!array_key_exists(EndpointInterface::OPTION_CLASS,$params)) {
            throw new \RuntimeException("Endpoint class not defined for URI: ".$uri);
        }

        $className = $params[EndpointInterface::OPTION_CLASS];

        if(!in_array(EndpointInterface::class, class_implements($className))){
            throw new \InvalidArgumentException("Expected class implementing EndpointInterface, $className given");
        }

        return new $className($uri,$params);
    }


}