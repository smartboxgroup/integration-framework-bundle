<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BaseKernelTestCase
 * @package Smartbox\Integration\FrameworkBundle\Tests
 */
class BaseKernelTestCase extends KernelTestCase {

    public static function getKernelClass(){
        return AppKernel::class;
    }

    public function setUp(){
        $this->bootKernel();
    }

    public function tearDown(){
        parent::tearDown();
    }

    public function getContainer(){
        return self::$kernel->getContainer();
    }

    /**
     * @param SerializableInterface $body
     * @param array $headers
     * @param Context $context
     * @return \Smartbox\Integration\FrameworkBundle\Messages\Message
     */
    protected function createMessage(SerializableInterface $body = null, $headers = array(), Context $context = null){
        return $this->getContainer()->get('smartesb.message_factory')->createMessage($body,$headers,$context);
    }
}
