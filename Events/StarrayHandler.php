<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;

class StarrayHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'starray',
                'method' => 'serializeStringOrArrayToArray',
            ),
        );
    }

    /**
     * The de-serialization function, which will return always an array.
     * This is a temporary solution to a problem.
     *
     * @param VisitorInterface $visitor
     * @param string|array     $data
     * @param array            $type
     *
     * @return array
     */
    public function serializeStringOrArrayToArray(VisitorInterface $visitor, $data, array $type, Context $context)
    {
        if (is_string($data)) {
            return array($data);
        } elseif (is_array($data)) {
            return $data;
        } else {
            return array();
        }
    }
}
