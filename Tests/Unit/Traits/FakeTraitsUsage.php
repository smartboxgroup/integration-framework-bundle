<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesValidator;

class FakeTraitsUsage
{
    use UsesEvaluator;
    use UsesEventDispatcher;
    use UsesSerializer;
    use UsesValidator;
}