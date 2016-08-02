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
        // manage negative timestamps (before unix epoch)
        if ($timestamp < 0) {
            $datetime = new \DateTime();
            $datetime = $datetime->setTimestamp($timestamp);
        } else {
            $datetime = \DateTime::createFromFormat("U.u", $timestamp, new \DateTimeZone('UTC'));

            if ($datetime == false) {
                $datetime = \DateTime::createFromFormat("U", $timestamp, new \DateTimeZone('UTC'));
            }
        }

        if ($datetime == false) {
            throw new \RuntimeException(
                sprintf('Could not create datetime from "%s": %s',
                    $timestamp,
                    print_r(\DateTime::getLastErrors(), true)
                )
            );
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
