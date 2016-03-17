<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;


use Smartbox\Integration\FrameworkBundle\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\producerUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

trait UsesProducersRouter {

    /**
     * @var InternalRouter
     */
    protected $producersRouter;

    /**
     * @return InternalRouter
     */
    public function getProducersRouter()
    {
        return $this->producersRouter;
    }

    /**
     * @param InternalRouter $producersRouter
     */
    public function setProducersRouter($producersRouter)
    {
        $this->producersRouter = $producersRouter;
    }

    protected function getExchangePattern($options){
        if(!array_key_exists(Producer::OPTION_EXCHANGE_PATTERN,$options)){
            throw new \Exception("Exchange pattern not defined for URI: ".$options[InternalRouter::KEY_URI]);
        }

        return $options[Producer::OPTION_EXCHANGE_PATTERN];
    }

    protected function isInOnly($options){
        return $this->getExchangePattern($options) == Producer::EXCHANGE_PATTERN_IN_ONLY;
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
                    throw new producerUnrecoverableException("Missing exchange header \"$param\" required to resolve the uri \"$uri\"");
                }
            }
        }

        return $uri;
    }

    public function resolveOptions($uri){
        try{
            $params = $this->getProducersRouter()->match($uri);
        }catch(RouteNotFoundException $exception)
        {
            throw new RouteNotFoundException("Producer not found for Endpoint with URI $uri",0,$exception);
        }

        if(!array_key_exists(InternalRouter::KEY_producer,$params)){
            throw new RouteNotFoundException("Producer not found for Endpoint with URI $uri");
        }

        $producer = $params[InternalRouter::KEY_producer];

        if($producer instanceof ProducerInterface) {
            $options = array_merge($producer->getDefaultOptions(), $params);
            $options[InternalRouter::KEY_URI] = $uri;
            $producer->validateOptions($options,true);

            return $options;
        }else{
            throw new \Exception("The producer must be an instance of Producer, for uri:".$uri);
        }
    }

    /**
     * @param Exchange $exchange
     * @param $uri
     * @throws \Exception
     */
    protected function sendTo(Exchange $exchange, $uri){
        $uri = self::resolveURIParams($exchange,$uri);
        $options = $this->resolveOptions($uri);

        /** @var ProducerInterface $producer */
        $producer = $options[InternalRouter::KEY_producer];
        $producer->send($exchange,$options);

        if($this->isInOnly($options)){
            $exchange->setOut(null);
        }
    }
}