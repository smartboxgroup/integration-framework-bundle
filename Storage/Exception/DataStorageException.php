<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Exception;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;

/**
 * Class DataStorageException
 * @package Smartbox\Integration\FrameworkBundle\Storage\Exception
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