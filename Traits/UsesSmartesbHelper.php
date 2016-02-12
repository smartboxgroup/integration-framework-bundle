<?php


namespace Smartbox\Integration\FrameworkBundle\Traits;


use Smartbox\Integration\FrameworkBundle\Helper\SmartesbHelper;

trait UsesSmartesbHelper
{
    /**
     * @var  SmartesbHelper
     */
    protected $helper;

    /**
     * Sets the helper
     *
     * @param SmartesbHelper|null $helper
     */
    public function setHelper(SmartesbHelper $helper = null)
    {
        $this->helper = $helper;
    }
}
