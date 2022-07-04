<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\Evaluator;

use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ApcuParserCache;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * @group legacy
 */
class ApcuParserCacheTest extends \PHPUnit\Framework\TestCase
{
    private $cache_key = 'apcu_key_for_testing_purposes';

    protected function setUp(): void
    {
        if (!$this->apcuEnabled()) {
            $this->markTestSkipped('There is no APCu extension enabled.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->apcuEnabled()) {
            \apcu_delete($this->cache_key);
        }
    }

    public function testFetchForNotCachedData()
    {
        $apcuParserCache = new ApcuParserCache();

        $notExistingData = $apcuParserCache->fetch($this->cache_key);

        $this->assertNull(
            $notExistingData,
            \sprintf(
                'ApcuParserCache should return null value for not existing key, hence %s given for the key "%s".',
                \var_export($notExistingData, true),
                $this->cache_key
            )
        );
    }

    public function testSaveAndFetch()
    {

        $this->markTestSkipped('To be reviewed before reinstating..');
        $apcuParserCache = new ApcuParserCache();

        $data = new ParsedExpression('a', new Node(['a' => 'test']));
        $apcuParserCache->save($this->cache_key, $data);

        $this->assertEquals(
            $data,
            $apcuParserCache->fetch($this->cache_key),
            'The retrieved value should be the same as cached one.'
        );
    }

    private function apcuEnabled()
    {
        return \extension_loaded('apcu');
    }
}
