<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait ParseHeadersTrait.
 */
trait ParseHeadersTrait
{
    /**
     * @param $headerContent
     *
     * @return array
     */
    public function parseHeadersToArray($headerContent)
    {
        $headerLines = [];
        $headers = array_filter(preg_split("(\r\n|\r|\n)", $headerContent));

        foreach ($headers as $line) {
            if (!$this->hasHttpMethod($line)) {
                $values = explode(':', $line, 2);

                if (isset($values[1]) && isset($values[0]) && !empty($values[0] && !empty($values[1]))) {
                    $headerLines[$values[0]] = trim(str_replace('"', '', $values[1]));
                }
            }
        }

        return $headerLines;
    }

    /**
     * @param $line
     *
     * @return array
     */
    private function hasHttpMethod($line)
    {
        return preg_match('/('.implode('|', $this->getHttpMethods()).')/', $line);
    }

    /**
     * @return array
     */
    public function getHttpMethods()
    {
        return [
            Request::METHOD_POST,
            Request::METHOD_CONNECT,
            Request::METHOD_DELETE,
            Request::METHOD_GET,
            Request::METHOD_HEAD,
            Request::METHOD_OPTIONS,
            Request::METHOD_PURGE,
            Request::METHOD_PATCH,
            Request::METHOD_PUT,
            Request::METHOD_TRACE,
        ];
    }
}
