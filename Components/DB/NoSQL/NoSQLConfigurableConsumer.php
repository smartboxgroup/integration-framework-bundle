<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;

class NoSQLConfigurableConsumer extends NoSQLConfigurableService implements ConfigurableConsumerInterface
{
    const CONTEXT_MSG = 'msg';
    const CONTEXT_BODY = 'body';
    const CONTEXT_HEADERS = 'headers';

    use IsStopableConsumer;
    use UsesSmartesbHelper;

    /**
     * Reads a message from the NoSQL database executing the configured steps
     *
     * @param EndpointInterface $endpoint
     * @return \Smartbox\Integration\FrameworkBundle\Core\Messages\Message
     */
    protected function readMessage(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $method = $options[NoSQLConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableConsumerInterface::CONFIG_QUERY_STEPS];

        $context = [
        ];

        $this->executeSteps($steps, $options, $context);

        $result = $this->getConfHelper()->resolve(
            $config[ConfigurableConsumerInterface::CONFIG_QUERY_RESULT],
            $context
        );

        if($result == null){
            return null;
        }elseif(is_array($result)){
            $result = new SerializableArray($result);
        }

        return $this->smartesbHelper->getMessageFactory()->createMessage($result);
    }

    /**
     * Executes the necessary actions after the message has been consumed
     *
     * @param EndpointInterface $endpoint
     * @param MessageInterface $message
     */
    protected function onConsume(EndpointInterface $endpoint, MessageInterface $message)
    {
        $options = $endpoint->getOptions();
        $method = $options[NoSQLConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableConsumerInterface::CONFIG_ON_CONSUME];

        $context = [
            self::CONTEXT_MSG => $message,
            self::CONTEXT_HEADERS => $message->getHeaders(),
            self::CONTEXT_BODY => $message->getBody()
        ];

        $this->executeSteps($steps, $options, $context);
    }

    public function consume(EndpointInterface $endpoint)
    {
        while (!$this->shouldStop()) {
            try {
                // Receive
                $message = $this->readMessage($endpoint);

                // Process
                if ($message) {
                    --$this->expirationCount;

                    $endpoint->handle($message);

                    $this->onConsume($endpoint, $message);
                }
            } catch (\Exception $ex) {
                if (!$this->stop) {
                    throw $ex;
                }
            }
        }
    }
}