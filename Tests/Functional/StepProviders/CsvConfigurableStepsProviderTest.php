<?php
namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Producers;

use Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableProtocol;
use Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Smartbox\Integration\FrameworkBundle\Core\Producers\AbstractConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;

class CsvConfigurableStepsProviderTest extends BaseTestCase
{
    /** @var CsvConfigurableProducer */
    protected $configurableProducer;

    /** @var CsvConfigurableStepsProvider */
    protected $stepsProvider;

    public function setUp()
    {
        parent::setUp();

        $this->optionsResolver = new OptionsResolver();

        $this->protocol = new ConfigurableWebserviceProtocol();
        $this->protocol->configureOptionsResolver($this->optionsResolver);

        $this->stepsProvider = self::getContainer()->get('smartesb.steps_provider.csv_file');
    }

    public function testProviderExists()
    {   
        $this->assertInstanceOf('Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableStepsProvider', $this->stepsProvider); 
    }

    public function testExecuteStepReturnsFalse()
    {
        $stepAction = [];
        $stepActionParams = [];
        $options = [];
        $context = [];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertFalse( $ans );
    }

    public function testCreateFile()
    {
        $file_name = md5(microtime()) . '.hello.world';

        $stepAction = 'create';
        $stepActionParams = [

        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => $file_name
        ];
        $context = [];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //The new file should exist
        $this->assertFileExists('/tmp/' . $file_name);

        //Tidy up
        @unlink('/tmp/' . $file_name);
    }

    public function testCreateFileWithPathSet()
    {
        $default_file_name = md5(microtime()) . '.goodbye.world';
        $file_name = md5(microtime()) . '.hello.world';

        $stepAction = 'create';
        $stepActionParams = [
            'file_path' => $file_name
        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => $default_file_name
        ];
        $context = [];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //The new file should exist
        $this->assertFileExists('/tmp/' . $file_name);

        //and the default file does not exisit!
        $this->assertFalse( file_exists('/tmp/' .  $default_file_name), 'The default file should not exist' );

        //Tidy up
        @unlink('/tmp/' . $file_name);
        @unlink('/tmp/' . $default_file_name);
    }

    public function testUnlinkFile()
    {
        $file_name = md5(microtime()) . '.hello.world';
        $full_path = '/tmp/' . $file_name;

        //create the file before the test
        file_put_contents($full_path, '');

        $stepAction = 'delete';
        $stepActionParams = [

        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => $file_name
        ];
        $context = [];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //The new file should not exist
        $this->assertFalse( file_exists($full_path) );

        //Tidy up
        @unlink($file_path);
    }

    public function testRenameFile()
    {
        $file_name = md5(microtime()) . '.goodbye.world';
        $new_file_name = md5(microtime()) . '.hello.world';

        //create the file before the test
        file_put_contents('/tmp/' . $file_name, '');

        $stepAction = 'rename';
        $stepActionParams = [
            'file_path' => $file_name,
            'new_file_path' => $new_file_name,
        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => 'delete.me'
        ];
        $context = [];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //The old file should not exist
        $this->assertFalse( file_exists('/tmp/' . $file_name) );

        //and the new should
        $this->assertFileExists('/tmp/' . $new_file_name);

        //Tidy up
        @unlink('/tmp/' . $file_name);
        @unlink('/tmp/' . $new_file_name);
    }

    public function testCopyFile()
    {
        $file_name = md5(microtime()) . '.hello.world';
        $new_file_name = md5(microtime()) . '.really.hello.world';

        //create the file before the test
        file_put_contents('/tmp/' . $file_name, '');

        $stepAction = 'copy';
        $stepActionParams = [
            'file_path' => $file_name,
            'new_file_path' => $new_file_name,
        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => 'delete.me'
        ];
        $context = [];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //The original file should be there
        $this->assertFileExists('/tmp/' . $file_name);

        //and the new should also
        $this->assertFileExists('/tmp/' . $new_file_name);

        //Tidy up
        @unlink('/tmp/' . $file_name);
        @unlink('/tmp/' . $new_file_name);
    }

    public function testWriteToFile()
    {
        $file_name = md5(microtime()) . '.hello.world';

        $stepAction = 'write';
        $stepActionParams = [
            'file_path' => $file_name,
            'rows' => [
                [ "x1", "y1", "z1" ],
                [ "x2", "y2", "z2" ],
                [ "x3", "y3", "z3" ],
                [ "x4", "y4", "z4" ],
                [ "x5", "y5", "z5" ],
                [ "x6", "y6", "z6" ],
                [ "x7", "y7", "z7" ],
            ]
        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => 'delete.me',
            'delimiter' => '|',
            'enclosure' => '+',
            'escape_char' =>'\\',
        ];
        $context = [];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //The original file should be there
        $this->assertFileExists('/tmp/' . $file_name);
        $lines = file('/tmp/' . $file_name);
        $this->assertEquals( count($lines), 7 ); //should be 7 rows
        $this->assertEquals( count( explode('|', $lines[0])) , 3 ); //should be 3 cols

        //Tidy up
        @unlink('/tmp/' . $file_name);
    }

    public function testAppendLinesToFile()
    {
        $file_name = md5(microtime()) . '.hello.world';

        $handle = fopen('/tmp/' . $file_name, 'w');

        $stepAction = 'append_lines';
        $stepActionParams = [
            'rows' => [
                [ "a1", "b1", "c1" ],
                [ "a2", "b2", "c2" ],
                [ "a3", "b3", "c3" ],
                [ "a4", "b4", "c4" ],
                [ "a5", "b5", "c5" ],
                [ "a6", "b6", "c6" ],
                [ "a7", "b7", "c7" ],
            ],
            'file_path' => $file_name,
        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => 'delete.me',
            'delimiter' => '|',
            'enclosure' => '+',
            'escape_char' =>'\\',
        ];
        $context = [
            'vars'=>[]
        ];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //The original file should be there
        $this->assertFileExists('/tmp/' . $file_name);
        $lines = file('/tmp/' . $file_name);
        $this->assertEquals( count($lines), 7 ); //should be 7 rows
        $this->assertEquals( count( explode('|', $lines[0])) , 3 ); //should be 3 cols

        //Tidy up
        fclose($handle);
        @unlink('/tmp/' . $file_name);
    }

    public function testReadFile()
    {
        $file_name = md5(microtime()) . '.hello.world';

        $stepAction = 'read';
        $stepActionParams = [
            'file_path' => $file_name,
            'result_name' => 'resultness',
        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => 'delete.me',
            'delimiter' => '|',
            'enclosure' => '+',
            'escape_char' =>'\\',
            'max_length' => 1000,
        ];
        $context = [];

        file_put_contents('/tmp/' . $file_name, "a1|b1|c1\na2|b2|c2\na3|b3|c3\na4|b4|c4\na5|b5|c5\na6|b6|c6\na7|b7|c7");

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //Check we have rows in the context
        $this->assertSame( 'a1', $context['resultness'][0][0] ); 

        //Tidy up
        @unlink('/tmp/' . $file_name);
    }

    public function testReadLinesFromFile()
    {
        $file_name = md5(microtime()) . '.hello.world';
        file_put_contents('/tmp/' . $file_name, "a1|b1|c1\na2|b2|c2\na3|b3|c3\na4|b4|c4\na5|b5|c5\na6|b6|c6\na7|b7|c7");

        $handle = fopen('/tmp/' . $file_name, 'r');

        $stepAction = 'read_lines';
        $stepActionParams = [
            'max_lines' => 2,
            'result_name' => 'resultness',
            'file_path' => $file_name,
        ];
        $options = [
            'root_path' => '/tmp',
            'default_path' => 'delete.me',
            'delimiter' => '|',
            'enclosure' => '+',
            'escape_char' =>'\\',
            'max_length' => 1000,
        ];
        $context = [
        ];

        $ans = $this->stepsProvider->executeStep($stepAction, $stepActionParams, $options, $context);
        $this->assertTrue( $ans );

        //Check we have rows in the context
        $this->assertSame( 'a1', $context['results']['resultness'][0][0] );
        $this->assertCount( 2, $context['results']['resultness'] );

        //Tidy up
        @unlink('/tmp/' . $file_name);
    }

}
