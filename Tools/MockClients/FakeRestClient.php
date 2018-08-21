<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use GuzzleHttp\Client;
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

        $mocksEnabled = getenv('MOCKS_ENABLED');
        $displayRequest = getenv('DISPLAY_REQUEST');
        $recordResponse = getenv('RECORD_RESPONSE');

        if ('true' === $mocksEnabled) {
            $mocksMessage = 'MOCKS/';
        } else {
            $mocksMessage = '';
        }

        if ('true' === $displayRequest) {
            $requestUri = $request->getUri()->getScheme().'://'.$request->getUri()->getAuthority().$request->getUri()->getPath();
            if ($request->getUri()->getQuery()) {
                $requestUri .= '?'.$request->getUri()->getQuery();
            }
            $requestMethod = $request->getMethod();

            $requestBody = '';
            if (isset($options['body'])) {
                $requestBody = $options['body'];
            }

            $requestHeaders = [];
            if (isset($options['headers']) && is_array($options['headers'])) {
                foreach ($options['headers'] as $headerName => $headerValue) {
                    $requestHeaders[] = $headerName.' => '.$headerValue;
                }
            }

            $requestQuery = [];
            if (isset($options['query']) && is_array($options['query'])) {
                foreach ($options['query'] as $queryName => $queryValue) {
                    $requestQuery[] = $queryName.' => '.$queryValue;
                }
            }

            echo "\nREQUEST (".$mocksMessage.'REST)  for '.$requestUri.' / '.$requestMethod;
            echo "\n=====================================================================================================";
            echo "\nHEADERS:\n".implode($requestHeaders, "  \n");
            echo "\nQUERY:\n".implode($requestQuery, "  \n");
            echo "\nRAW BODY:\n".$requestBody;
            echo "\nPRETTY SEXY BODY:\n".$this->prettyJson($requestBody);
            echo "\n=====================================================================================================";
            echo "\n\n";
        }

        if ('true' === $mocksEnabled) {
            try {
                $response = $this->getResponseFromCache($this->actionName, self::CACHE_SUFFIX);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        } else {
            $response = parent::send($request, $options);
        }

        if ('true' === $displayRequest) {
            $content = $this->getResponseContent($response);
            $body = $this->getBodyContent($response);
            echo "\nRESPONSE (".$mocksMessage.'REST)  for '.$requestUri.' / '.$requestMethod;
            echo "\n=====================================================================================================";
            echo "\nRAW RESPONSE:\n".$content;
            echo "\nPRETTY SEXY RESPONSE:\n".$this->prettyJson($content);
            echo "\nPRETTY SEXY BODY:\n".$this->prettyJson($body);
            echo "\n=====================================================================================================";
            echo "\n\n";
        }

        if ('true' === $recordResponse) {
            $this->setResponseInCache($this->actionName, $response, self::CACHE_SUFFIX);
        }

        return $response;
    }

    /** Return a much nicer json string.
     *
     * @param $uglyJson
     *
     * @return string
     */
    private function prettyJson($uglyJson)
    {
        $json = json_decode($uglyJson);

        return json_encode($json, JSON_PRETTY_PRINT);
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $uri = null, array $options = [])
    {
        $this->checkInitialisation();
        $this->actionName = $this->prepareActionName($method, $uri);

        if ('true' === getenv('MOCKS_ENABLED')) {
            try {
                return $this->getResponseFromCache($this->actionName, self::CACHE_SUFFIX);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        $response = parent::request($method, $uri, $options);

        if ('true' === getenv('RECORD_RESPONSE')) {
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

    /**
     * @param $resource
     * @param Psr7\Response $response
     * @param null          $suffix
     */
    protected function setResponseInCache($resource, $response, $suffix = null)
    {
        $rawRecordedResponse = getenv('RAW_RECORDED_RESPONSE');

        $content = $this->getResponseContent($response);

        if ('true' !== $rawRecordedResponse) { // By default, we record a pretty response.
            $content = $this->prettyJson($content);
        }

        $this->trait_setResponseInCache($resource, $content, $suffix);
    }

    /**
     * @param Psr7\Response $response
     *
     * @return string
     */
    protected function getResponseContent($response)
    {
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

        return $content;
    }

    /**
     * @param Psr7\Response $response
     *
     * @return string
     */
    protected function getBodyContent($response)
    {
        $body = $response->getBody()->getContents();
        $response->getBody()->rewind();

        return $body;
    }
}
