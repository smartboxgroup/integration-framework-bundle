<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\FlowsVersionAware;

/**
 * Class MessageFactory.
 */
class MessageFactory implements MessageFactoryInterface
{
    use FlowsVersionAware;

    /**
     * @param SerializableInterface $body
     * @param array                 $headers
     * @param Context               $context
     *
     * @return Message
     */
    public function createMessage(SerializableInterface $body = null, $headers = array(), Context $context = null)
    {
        if (!$context) {
            $context = new Context([
                Context::FLOWS_VERSION => $this->getFlowsVersion(),
            ]);
        }

        return new Message($body, $headers, $context);
    }
}
