<?php
namespace Smartbox\Integration\FrameworkBundle\Messages;

use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\SerializableInterface;


/**
 * Class Message
 * @package Smartbox\Integration\FrameworkBundle\Messages
 */
interface MessageInterface extends SerializableInterface
{
    /**
     * @return Context
     */
    public function getContext();

    /**
     * @param Context $context
     */
    public function setContext($context = null);

    /**
     * @return mixed
     */
    public function getHeaders();

    /**
     * @param array $headers
     * @throws \Exception
     */
    public function setHeaders(array $headers);

    /**
     * @param string $headerKey
     * @param string $headerValue
     * @return string
     */
    public function addHeader($headerKey, $headerValue);

    /**
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value);

    /**
     * @param string $key
     * @return string|null
     */
    public function getHeader($key);

    /**
     * @param SerializableInterface $body
     */
    public function setBody(SerializableInterface $body = null);

    /**
     * @return SerializableInterface
     */
    public function getBody();
}