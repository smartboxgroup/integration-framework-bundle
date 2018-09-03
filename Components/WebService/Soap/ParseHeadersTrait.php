<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap;

/**
 * Trait ParseHeadersTrait.
 */
trait ParseHeadersTrait
{
    /**
     * @param $headerContent
     *
     * @return array
     */
    private function parseHeadersToArray($headerContent)
    {
        $headerLines = [];
        $headers = array_filter(preg_split("(\r\n|\r|\n)", $headerContent));

        foreach ($headers as $line) {
            if (false === strpos($line, 'POST')) {
                $values = explode(':', $line, 2);

                if (isset($values[1]) && isset($values[0]) && !empty($values[0] && !empty($values[1]))) {
                    $headerLines[$values[0]] = trim(str_replace('"', '', $values[1]));
                }
            }
        }

        return $headerLines;
    }
}
