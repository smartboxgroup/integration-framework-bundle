<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;

/**
 * Class NoSQLDriverDataException.
 */
class NoSQLDriverDataException extends NoSQLDriverException
{
    /**
     * @JMS\Type("Smartbox\CoreBundle\Type\SerializableInterface")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     */
    protected $storageData;

    public function setStorageData(SerializableInterface $storageData)
    {
        $this->storageData = $storageData;
    }
}
