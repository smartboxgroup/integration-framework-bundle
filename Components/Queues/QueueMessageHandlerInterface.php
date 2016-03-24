<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;

/**
 * This interface is used to indicate that this handler can handle messages of type QueueMessageInterface directly.
 *
 * Interface QueueMessageHandlerInterface
 */
interface QueueMessageHandlerInterface extends HandlerInterface
{
}
