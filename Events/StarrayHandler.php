<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Handler\ArrayCollectionHandler;

class StarrayHandler extends ArrayCollectionHandler
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {

        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'starray',
                'method' => 'serializeToArray',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'starray',
                'method' => 'deserializeToArray',
            ),
        );
    }



    /**
     * The de-serialization function, which will return always an array.
     * This is a temporary solution to a problem.
     *
     * @param VisitorInterface          $visitor
     * @param mixed                     $data
     * @param array                     $type
     * @param Context                   $context
     *
     * @return array
     */
    public function serializeToArray(VisitorInterface $visitor, $data, array $type, Context $context)
    {
        $data = new ArrayCollection((array)$data);

        return parent::serializeCollection($visitor, $data, $type, $context);
    }

    /**
     * The de-serialization function, which will return always an array.
     * This is a temporary solution to a problem.
     *
     * @param VisitorInterface  $visitor
     * @param mixed             $data
     * @param array             $type
     * @param Context           $context
     *
     * @return array
     */
    public function deserializeToArray(VisitorInterface $visitor, $data, array $type, Context $context)
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
