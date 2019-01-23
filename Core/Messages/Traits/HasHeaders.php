<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages\Traits;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Trait HasHeaders.
 */
trait HasHeaders
{
    /**
     * @Assert\NotNull
     * @JMS\Type("array<string,string>")
     * @JMS\Groups({"headers", "logs"})
     * @JMS\Expose
     *
     * @var array
     */
    protected $headers = [];

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string                $headerKey
     * @param string|int|float|bool $headerValue
     *
     * @return string
     */
    public function addHeader($headerKey, $headerValue)
    {
        if (!is_string($headerKey)) {
            throw new \InvalidArgumentException('Expected headerKey to be a string');
        }

        if (!is_scalar($headerValue)) {
            throw new \InvalidArgumentException('Expected headerValue to be a scalar');
        }

        $oldValue = null;
        if (array_key_exists($headerKey, $this->headers)) {
            $oldValue = $this->headers[$headerKey];
        }

        $this->headers[$headerKey] = $headerValue;

        return $oldValue;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getHeader($key)
    {
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        } else {
            return;
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value)
    {
        $this->addHeader($key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasHeader($key)
    {
        return \array_key_exists($key, $this->headers);
    }

    /**
     * @param array $headers
     *
     * @throws \Exception
     */
    public function setHeaders(array $headers)
    {
        $this->headers = [];

        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }
    }

    /**
     * @param array $headers
     *
     * @throws \Exception
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }
    }
}
