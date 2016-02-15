<?php
namespace Smartbox\Integration\FrameworkBundle\Serialization;


use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ItineraryHandler implements SubscribingHandlerInterface, ContainerAwareInterface
{

    /** @var  Container */
    protected $container;

    public static function getSubscribingMethods()
    {
        $supportedFormats = ['json','xml','array','mongo_array'];

        $res = [];

        foreach($supportedFormats as $format){
            $res[] = [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => $format,
                'type' => Itinerary::class,
                'method' => 'serializeItinerary',
            ];

            $res[] = [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => $format,
                'type' => Itinerary::class,
                'method' => 'deserializeItinerary',
            ];
        }

        return $res;
    }

    /**
     * @param $visitor
     * @param Itinerary $itinerary
     * @param $originalType
     * @param SerializationContext $context
     * @return array
     */
    public function serializeItinerary($visitor, Itinerary $itinerary, $originalType, SerializationContext $context)
    {
        $processors = $itinerary->getProcessorIds();

        $data = array(
            'type' => Itinerary::class,
            'name' => $itinerary->getName(),
            'processors' => $processors
        );

        if(!$visitor->getRoot()){
            $visitor->setRoot($data);
        }

        return $data;
    }

    public function deserializeItinerary($visitor, $dataArray, array $originalType, DeserializationContext $context)
    {
        $itinerary = new Itinerary();

        if ($dataArray instanceof \SimpleXMLElement) {
            $processorsIds = $dataArray->{'processors'};
            $itinerary->setName((string)$dataArray->{'name'});
        } else {
            $processorsIds = @$dataArray['processors'];
            $itinerary->setName((string)@$dataArray['name']);
        }

        foreach ($processorsIds as $processorId) {
            if ($this->container->has($processorId)) {
                $processor = $this->container->get($processorId);
            }else{
                $processor = new InexistentProcessorMock();
                $processor->setId($processorId);
                $processor->setRuntimeBreakpoint(true);
                $processor->setEventDispatcher($this->container->get('event_dispatcher'));
                $processor->setMessageFactory($this->container->get('smartesb.message_factory'));
                $processor->setValidator($this->container->get('validator'));
            }

            $itinerary->addProcessor($processor);
        }

        return $itinerary;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}