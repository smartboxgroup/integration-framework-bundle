<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class EventDispatcher.
 */
class EventDispatcher extends ContainerAwareEventDispatcher
{
    /**
     * @var bool
     */
    protected $deferringEnabled = true;

    /**
     * @return bool
     */
    public function isDeferringEnabled()
    {
        return $this->deferringEnabled;
    }

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->deferringEnabled = $container->getParameter('smartesb.enable_events_deferring');
    }

    /**
     * @param bool $deferringEnabled
     */
    public function setDeferringEnabled($deferringEnabled)
    {
        $this->deferringEnabled = $deferringEnabled;
    }

    protected function shouldDefer(\Symfony\Component\EventDispatcher\Event $event)
    {
        if (!$this->deferringEnabled) {
            return false;
        }

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
            $exchange = new Exchange(new EventMessage($event, [], new Context([Context::FLOWS_VERSION => $flowsVersion])));
            $endpoint->produce($exchange);
        }
    }

    /** {@inheritdoc} */
    public function dispatch($eventName, \Symfony\Component\EventDispatcher\Event $event = null)
    {
        $event = parent::dispatch($eventName, $event);

        $isDeferred = strpos($eventName, '.deferred') !== false;

        if (!$isDeferred && $this->shouldDefer($event)) {
            $this->deferEvent($event);
        }

        return $event;
    }
}
