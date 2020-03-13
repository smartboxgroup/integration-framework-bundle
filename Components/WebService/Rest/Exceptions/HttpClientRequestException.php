<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientRequestException extends RequestException
{
    protected static $truncateResponseSize = 0;

    public static function setTruncateResponseSize(int $truncateRresponseSize)
    {
        self::$truncateResponseSize = $truncateRresponseSize;
    }
    
    /**
     * Override the current getResponseBodySummary from RequestException
     * to avoid the truncation done default by guzzle at 120 chars
     *
     * @param ResponseInterface $response
     * @return false|string|null
     */
    public static function getResponseBodySummary(ResponseInterface $response)
    {
        $body = $response->getBody();

        if (!$body->isSeekable()) {
            return null;
        }

        $size = $body->getSize();

        if ($size === 0) {
            return null;
        }

        $fullBody = utf8_decode($body->read($size));

        if(self::$truncateResponseSize > 0) {
            $fullBody = substr($fullBody, 0, self::$truncateResponseSize);
        }

        $summary = utf8_encode($fullBody);

        $body->rewind();

        if (self::$truncateResponseSize > 0 && $size > self::$truncateResponseSize) {
            $summary .= ' (truncated...)';
        }
        
        if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/', $summary)) {
            return null;
        }

        return $summary;
    }
}
