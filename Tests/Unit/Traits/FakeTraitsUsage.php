<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Traits\UsesValidator;

class FakeTraitsUsage
{
    use UsesEvaluator;
    use UsesEventDispatcher;
    use UsesSerializer;
    use UsesValidator;
}