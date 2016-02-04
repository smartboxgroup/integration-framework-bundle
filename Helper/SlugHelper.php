<?php


namespace Smartbox\Integration\FrameworkBundle\Helper;


use Smartbox\Integration\FrameworkBundle\Connectors\Connector;
use Symfony\Component\DependencyInjection\ContainerAware;

class SlugHelper extends ContainerAware
{
    static public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '_', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'na';
        }

        return $text;
    }
}