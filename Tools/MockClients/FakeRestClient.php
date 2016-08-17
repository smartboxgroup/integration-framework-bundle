<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

class FakeRestClient extends Client
{
    use FakeClientTrait {
        getResponseFromCache as trait_getResponseFromCache;
        setResponseInCache as trait_setResponseInCache;
    }

    const CACHE_SUFFIX = 'json';

    /**
     * {@inheritdoc}
     */
    public function send(RequestInterface $request, array $options = [])
    {
        $this->checkInitialisation();
        $this->actionName = $this->prepareActionName($request->getMethod(), $request->getUri());

        try {
            $response = $this->getResponseFromCache($this->actionName, self::CACHE_SUFFIX);
        } catch (\InvalidArgumentException $e) {
            $response = parent::send($request, $options);

            $this->setResponseInCache($this->actionName, $response, self::CACHE_SUFFIX);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $uri = null, array $options = [])
    {
        $this->checkInitialisation();
        $this->actionName = $this->prepareActionName($method, $uri);

        if (getenv('MOCKS_ENABLED') === 'true') {
            try {
                return $this->getResponseFromCache($this->actionName, self::CACHE_SUFFIX);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        $response = parent::request($method, $uri, $options);

        if (getenv('RECORD_RESPONSE') === 'true') {
            $this->setResponseInCache($this->actionName, $response, self::CACHE_SUFFIX);
        }

        return $response;
    }

    private function prepareActionName($method, $uri)
    {
        if (!$uri instanceof Psr7\Uri) {
            $uri = new Psr7\Uri($uri);
        }

        $actionName = $method.DIRECTORY_SEPARATOR.trim($uri->getPath(), DIRECTORY_SEPARATOR);

        return preg_replace('/[^A-Za-z0-9]+/', '_', $actionName);
    }

    protected function getResponseFromCache($resource, $suffix = null)
    {
        $response = json_decode($this->trait_getResponseFromCache($resource, $suffix), true);

        return new Psr7\Response($response['status'], $response['headers'], $response['body'], $response['version'], $response['reason']);
    }

    protected function setResponseInCache($resource, $response, $suffix = null)
    {
        /* @var Psr7\Response $response */
        $content = json_encode(
            [
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()->getContents(),
                'version' => $response->getProtocolVersion(),
                'reason' => $response->getReasonPhrase(),
            ]
        );

        $response->getBody()->rewind();

        $this->trait_setResponseInCache($resource, $content, $suffix);
    }
}
