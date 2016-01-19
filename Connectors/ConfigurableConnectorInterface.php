<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;


interface ConfigurableConnectorInterface extends ConnectorInterface {

    public function setMethodsConfiguration(array $configuration);

    public function setMappings(array $mappings);

}