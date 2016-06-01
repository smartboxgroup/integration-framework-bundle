<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Helper;

class DateTimeHelper
{
    /**
     * @param mixed $timestamp
     * @return \DateTime
     */
    public static function createDateTimeFromTimestampWithMilliseconds($timestamp)
    {
        $datetime = \DateTime::createFromFormat("U.u", $timestamp, new \DateTimeZone('UTC'));

        if ($datetime == false) {
            $datetime = \DateTime::createFromFormat("U", $timestamp, new \DateTimeZone('UTC'));

            if ($datetime == false) {
                throw new \RuntimeException("Could not create datetime: " . print_r(\DateTime::getLastErrors(), true));
            }
        }

        return $datetime;
    }

    /**
     * @return \DateTime
     */
    public static function createDateTimeFromCurrentMicrotime()
    {
        try {
            return self::createDateTimeFromTimestampWithMilliseconds(microtime(true));
        } catch (\Exception $ex) {
            return new \DateTime();
        }
    }
}