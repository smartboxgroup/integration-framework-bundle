<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class EventDispatcher.
 */
class EventDispatcher extends ContainerAwareEventDispatcher
{
    protected function shouldDefer(\Symfony\Component\EventDispatcher\Event $event)
    {
        if ($event instanceof \Smartbox\Integration\FrameworkBundle\Events\Event) {
            $filtersRegistry = $this->getContainer()->get('smartesb.registry.event_filters');
            $filters = $filtersRegistry->getDeferringFilters();

            /** @var EventFilterInterface $filter */
            foreach ($filters as $filter) {
                if ($filter->filter($event)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Event $event
     */
    protected function deferEvent(Event $event)
    {
        $deferToURI = $this->getContainer()->getParameter(SmartboxIntegrationFrameworkExtension::PARAM_DEFERRED_EVENTS_URI);

        if (!empty($deferToURI)) {
            $endpoint = $this->getContainer()->get('smartesb.endpoint_factory')->createEndpoint($deferToURI);
            $flowsVersion = $this->getContainer()->getParameter('smartesb.flows_version');
            $exchange = new Exchange(new EventMessage($event, [], new Context([Context::VERSION => $flowsVersion])));
            $endpoint->produce($exchange);
        }
    }

    /** {@inheritdoc} */
    public function dispatch($eventName, \Symfony\Component\EventDispatcher\Event $event = null)
    {
        $event = parent::dispatch($eventName, $event);

        $isDeferred = strpos($eventName, '.deferred') !==  false;

        if (!$isDeferred && $this->shouldDefer($event)) {
            $this->deferEvent($event);
        }

        return $event;
    }
}
