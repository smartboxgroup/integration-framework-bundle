<?php

namespace Smartbox\Integration\FrameworkBundle\Configurability\Routing;


use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ItinerariesRoutesLoader extends Loader{

    use ContainerAwareTrait;

    /** {@inheritdoc} */
    public function load($resource, $type = null)
    {
        if(strpos($resource,'@') === 0) {
            $resource = substr($resource, 1);
        }

        /** @var ItinerariesMap $itineraryRegistry */
        $itineraryRegistry = $this->container->get($resource);


        if(!$itineraryRegistry instanceof ItinerariesMap){
            throw new \InvalidArgumentException("ItinerariesRoutesLoader: Expected ItineraryRegistry as a resource");
        }

        $concreteRoutes = new RouteCollection();
        $genericRoutes = new RouteCollection();

        $routes = new RouteCollection();

        foreach ($itineraryRegistry->getItineraries() as $uriFrom => $itineraryRef) {

            /** @var Itinerary $itinerary */
            $itinerary = $this->container->get($itineraryRef);

            $name = $itinerary->getName();

            $defaults = array(
                InternalRouter::KEY_ITINERARY => '@'.$itineraryRef,
            );

            $route = new Route($uriFrom, $defaults);

            if(strpos($uriFrom,'{') === FALSE){
                $concreteRoutes->add($name,$route);
            }else{
                $genericRoutes->add($name,$route);
            }
        }

        $routes->addCollection($concreteRoutes);
        $routes->addCollection($genericRoutes);

        return $routes;
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $type == 'itineraries';
    }
}