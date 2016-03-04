<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Connectors\Clients;

class FakeSoapClient extends \SoapClient
{
    use FakeClientTrait;

    const CACHE_SUFFIX = 'xml';

    /**
     * {@inheritdoc}
     */
    public function __call($function_name, $arguments)
    {
        $this->checkInitialisation();
        $this->actionName = $function_name;
        return parent::__soapCall($function_name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null)
    {
        $this->checkInitialisation();
        $this->actionName = $function_name;
        return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
    }

    /**
     * {@inheritdoc}
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $this->checkInitialisation();

        $actionName = md5($location) . '_' . $this->actionName;

        try {
            $response = $this->getResponseFromCache($actionName, self::CACHE_SUFFIX);
        } catch (\InvalidArgumentException $e) {
            $response = parent::__doRequest($request, $location, $action, $version, $one_way);

            $this->setResponseInCache($actionName, $response, self::CACHE_SUFFIX);
        }

        return $response;
    }
}