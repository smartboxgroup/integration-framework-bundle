<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasType;
use Smartbox\Integration\FrameworkBundle\Messages\Traits\HasHeaders;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Message
 * @package Smartbox\Integration\FrameworkBundle\Messages
 */
class Message implements MessageInterface
{
    use HasType;
    use HasHeaders;

    const HEADER_EXPIRES = 'expires';
    const HEADER_QUEUE = 'queue';
    const HEADER_FROM = 'from';

    /**
     * @var Context
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Messages\Context")
     * @JMS\Groups({"context", "logs"})
     * @JMS\Expose
     */
    protected $context;


    /**
     * @Assert\Valid
     * @JMS\Type("Smartbox\CoreBundle\Type\SerializableInterface")
     * @JMS\Groups({"body", "logs"})
     * @JMS\Expose
     *
     * @var SerializableInterface
     */
    protected $body;

    /**
     * @param SerializableInterface|null $body
     * @param array $headers
     * @param Context $context
     */
    public function __construct(SerializableInterface $body = null, $headers = array(), Context $context = null)
    {
        $this->addHeaders($headers);
        $this->setBody($body);
        $this->setContext($context);
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Context $context
     */
    public function setContext($context = null)
    {
        $this->context = $context;
    }

    /**
     * @param SerializableInterface $body
     */
    public function setBody(SerializableInterface $body = null)
    {
        $this->body = $body;
    }

    /**
     * @return Entity
     */
    public function getBody()
    {
        return $this->body;
    }
}
