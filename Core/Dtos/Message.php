<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Dtos;

class Message
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    public function __construct(string $body, array $headers = [])
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
