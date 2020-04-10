<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Serialization;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\SerializerInterface;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Serializers\QueueSerializerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\MessageDecodingFailedException;

class Serializer implements QueueSerializerInterface
{
    const FORMAT_JSON = 'json';

    private $serializer;
    private $format;

    public function __construct(SerializerInterface $serializer, string $format = self::FORMAT_JSON)
    {
        $this->serializer = $serializer;
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $encodedMessage): MessageInterface
    {
        if (empty($encodedMessage['body']) || !is_array($encodedMessage['headers'])) {
            throw new MessageDecodingFailedException('Encoded message should have at least a "body" and some "headers"');
        }

        try {
            $message = $this->serializer->deserialize($encodedMessage['body'], SerializableInterface::class, $this->format);
        } catch (RuntimeException $e) {
            throw new MessageDecodingFailedException(sprintf('Could not decode message: %s.', $e->getMessage()), $e->getCode(), $e);
        } catch (InvalidArgumentException $e) {
            // @TODO Remove when JsonDeserializationVisitor is able to catch invalid messages like "123" or "null"
            throw new MessageDecodingFailedException(sprintf('Could not decode message: %s.', $e->getMessage()), $e->getCode(), $e);
        }

        foreach ($encodedMessage['headers'] as $header => $value) {
            // @TODO Remove this when Message and Exchange allow array as header value
            if (is_scalar($value)) {
                $message->setHeader($header, $value);
            }
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(MessageInterface $message): array
    {
        return [
            'body' => $this->serializer->serialize($message, $this->format),
            'headers' => $message->getHeaders(),
        ];
    }
}
