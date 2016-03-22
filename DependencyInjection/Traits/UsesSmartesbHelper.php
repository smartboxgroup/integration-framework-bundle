<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;

/**
 * Trait UsesSmartesbHelper.
 */
trait UsesSmartesbHelper
{
    /**
     * @var SmartesbHelper
     */
    protected $helper;

    /**
     * Sets the helper.
     *
     * @param SmartesbHelper|null $helper
     */
    public function setSmartesbHelper(SmartesbHelper $helper = null)
    {
        $this->helper = $helper;
    }
}
