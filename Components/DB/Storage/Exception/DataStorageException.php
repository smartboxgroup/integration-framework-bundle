<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Exception;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;

/**
 * Class DataStorageException.
 */
class DataStorageException extends StorageException
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
