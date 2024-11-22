<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BaseKernelTestCase.
 */
class BaseKernelTestCase extends KernelTestCase
{
    /** @var SmartesbHelper */
    protected $helper;

    protected function setUp(): void
    {
        $this->bootKernel();
        $this->getContainer()->set('doctrine', $this->createMock(RegistryInterface::class));
        $this->helper = $this->getContainer()->get('smartesb.helper');
    }

    public function getContainer(): ContainerInterface
    {
        return self::$kernel->getContainer();
    }

    /**
     * @param SerializableInterface $body
     * @param array                 $headers
     * @param Context               $context
     *
     * @return \Smartbox\Integration\FrameworkBundle\Core\Messages\Message
     */
    protected function createMessage(SerializableInterface $body = null, $headers = [], Context $context = null)
    {
        return $this->getContainer()->get('smartesb.message_factory')->createMessage($body, $headers, $context);
    }
}
