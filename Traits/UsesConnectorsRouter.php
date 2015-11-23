<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;


use Smartbox\Integration\FrameworkBundle\Connectors\Connector;
use Smartbox\Integration\FrameworkBundle\Connectors\ConnectorInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;

trait UsesConnectorsRouter {

    /**
     * @var InternalRouter
     */
    protected $connectorsRouter;

    /**
     * @return InternalRouter
     */
    public function getConnectorsRouter()
    {
        return $this->connectorsRouter;
    }

    /**
     * @param InternalRouter $connectorsRouter
     */
    public function setConnectorsRouter($connectorsRouter)
    {
        $this->connectorsRouter = $connectorsRouter;
    }

    protected function getExchangePattern($options){
        if(!array_key_exists(Connector::OPTION_EXCHANGE_PATTERN,$options)){
            throw new \Exception("Exchange pattern not defined for URI: ".$options[InternalRouter::KEY_URI]);
        }

        return $options[Connector::OPTION_EXCHANGE_PATTERN];
    }

    protected function isInOnly($options){
        return $this->getExchangePattern($options) == Connector::EXCHANGE_PATTERN_IN_ONLY;
    }

    static public function resolveURI(Exchange $exchange, $uri){
        if(strpos($uri,'{') !== false){
            foreach($exchange->getHeaders() as $key => $value){
                $uri = str_replace('{'.$key.'}',$value,$uri);
            }
        }

        return $uri;
    }

    /**
     * @param Exchange $exchange
     * @param $uri
     * @throws \Exception
     */
    protected function sendTo(Exchange $exchange, $uri){
        $uri = self::resolveURI($exchange,$uri);
        $params = $this->getConnectorsRouter()->match($uri);

        if(!array_key_exists(InternalRouter::KEY_CONNECTOR,$params)){
            throw new \Exception("Endpoint: Connector not found for uri: ".$uri);
        }

        $connector = $params[InternalRouter::KEY_CONNECTOR];

        if($connector instanceof ConnectorInterface){
            $options = array_merge($connector->getDefaultOptions(),$params);
            $options[InternalRouter::KEY_URI] = $uri;
            $connector->validateOptions($options,true);
            $connector->send($exchange,$options);
        }else{
            throw new \Exception("The connector must be an instance of Connector, for uri:".$uri);
        }

        if($this->isInOnly($options)){
            $exchange->setOut(null);
        }
    }
}