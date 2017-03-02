<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;

use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableProtocol;

class CsvConfigurableStepsProvider extends Service implements ConfigurableStepsProviderInterface
{
    use UsesConfigurableServiceHelper;

    // Steps
    const STEP_WRITE_TO_FILE = 'write';
    const STEP_WRITE_LINES_TO_FILE = 'write_lines';
    const STEP_READ_FROM_FILE = 'read';
    const STEP_READ_LINES_FROM_FILE = 'read_lines';
    const STEP_REMOVE_FILE = 'remove';
    const STEP_DELETE_FILE = 'delete';
    const STEP_UNLINK_FILE = 'unlink';
    const STEP_RENAME_FILE = 'rename';
    const STEP_MV_FILE = 'mv';
    const STEP_CREATE_FILE = 'create';
    const STEP_COPY_FILE = 'copy';

    // Method Params
    const PARAM_FILE_PATH = 'file_path';
    const PARAM_NEW_FILE_PATH = 'new_file_path';
    const PARAM_CSV_ROWS = 'rows';
    const PARAM_CSV_ROW = 'row';
    const PARAM_CONTEXT_RESULT_NAME = 'result_name';
    const PARAM_MAX_LINES = 'max_lines';

    // Context constants
    const CONTEXT_FILE_HANDLE = 'file_handle';

    /** @var OptionsResolver */
    protected $configResolver;

    public function __construct()
    {
        parent::__construct();

        if (!$this->configResolver) {
            $protocol = new CsvConfigurableProtocol();
            $this->configResolver = new OptionsResolver();
            $protocol->configureOptionsResolver($this->configResolver);
        }
    }

    public function executeSteps(array $stepsConfig, array &$options, array &$context)
    {
        foreach ($stepsConfig as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $options, $context);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeStep($stepAction, array &$stepActionParams, array &$options, array &$context)
    {
        switch ($stepAction) {
            case self::STEP_WRITE_TO_FILE:
                $this->writeToFile($stepActionParams, $options, $context);
                return true;

            case self::STEP_WRITE_LINES_TO_FILE:
                $this->writeLines($stepActionParams, $options, $context);
                return true;

            case self::STEP_READ_FROM_FILE:
                $this->readFile($stepActionParams, $options, $context);
                return true;

            case self::STEP_READ_LINES_FROM_FILE:
                $this->readLines($stepActionParams, $options, $context);
                return true;

            case self::STEP_REMOVE_FILE:
            case self::STEP_DELETE_FILE:
            case self::STEP_UNLINK_FILE:
                $this->unlinkFile($stepActionParams, $options, $context);
                return true;

            case self::STEP_RENAME_FILE:
            case self::STEP_MV_FILE:
                $this->renameFile($stepActionParams, $options, $context);
                return true;

            case self::STEP_COPY_FILE:
                $this->copyFile($stepActionParams, $options, $context);
                return true;

            case self::STEP_CREATE_FILE:
                $this->createFile($stepActionParams, $options, $context);
                return true;

        }

        return false;
    }

    /**
     * Write rows out to a csv file
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CSV_ROWS
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_PATH
     * 
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function writeToFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $root_path = $this->getRootPath( $endpointOptions, $stepActionParams);

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired( [
            self::PARAM_FILE_PATH,
            self::PARAM_CSV_ROWS,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $file_path = $params[self::PARAM_FILE_PATH];
        $full_path = $this->getRootPath( $endpointOptions ) . DIRECTORY_SEPARATOR . $file_path;

        $rows = $params[self::PARAM_CSV_ROWS];

        //open the file, reset the pointer to zero, create it if not already created
        $file_handle = fopen( $full_path, 'w' );

        foreach ($rows as $row) {
            fputcsv($file_handle, $row, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER], $endpointOptions[CsvConfigurableProtocol::OPTION_ENCLOSURE], $endpointOptions[CsvConfigurableProtocol::OPTION_ESCAPE_CHAR] );
        }

        fclose($file_handle);
    }

    /**
     * Write an array of lines to a csv file in to a context variable
     * it is assumed the file handle is in the context
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CSV_ROWS
     * 
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function writeLines(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        if( !isset($context[ self::CONTEXT_FILE_HANDLE ]) ){
            throw new \Exception( 'The file handle does not exist in the context' );
        }

        $file_handle = $context[ self::CONTEXT_FILE_HANDLE ];

        if( get_resource_type($file_handle) !== 'stream' ){
            throw new \Exception( 'The file handle in the context is not a file stream!' );
        }

        $meta = stream_get_meta_data($file_handle);
        $file_path = $meta['uri'];
        if( !is_writeable($file_path) === 'stream' ){
            throw new \Exception( 'The file handle in the context is not writeable!' );
        }

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired( [
            self::PARAM_CSV_ROWS,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $rows = $params[self::PARAM_CSV_ROWS];

        foreach ($rows as $row) {
            fputcsv($file_handle, $row, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER], $endpointOptions[CsvConfigurableProtocol::OPTION_ENCLOSURE], $endpointOptions[CsvConfigurableProtocol::OPTION_ESCAPE_CHAR] );
        }
    }

    /**
     * Read a csv file in to a context variable
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CONTEXT_RESULT_NAME
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_PATH
     * 
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function readFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $root_path = $this->getRootPath( $endpointOptions, $stepActionParams);

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired( [
            self::PARAM_FILE_PATH,
            self::PARAM_CONTEXT_RESULT_NAME,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $file_path = $params[self::PARAM_FILE_PATH];
        $full_path = $this->getRootPath( $endpointOptions ) . DIRECTORY_SEPARATOR . $file_path;
        
        $context_result_name = $params[self::PARAM_CONTEXT_RESULT_NAME];
        $max_line_length = $endpointOptions[CsvConfigurableProtocol::OPTION_MAX_LENGTH];

        //open the file, reset the pointer to zero
        $file_handle = fopen( $full_path, 'r' );

        $rows = [];

        while (( $row = fgetcsv($file_handle, $max_line_length, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER])) !== FALSE) {
            $rows[] = $row;
        }

        fclose($file_handle);

        $context[$context_result_name] = $rows;
    }

    /**
     * Read a line from a csv file in to a context variable
     * it is assumed the file handle is in the context
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CONTEXT_RESULT_NAME
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_MAX_LINES Defaults to 1
     * 
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function readLines(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        if( !isset($context[ self::CONTEXT_FILE_HANDLE ]) ){
            throw new \Exception( 'The file handle does not exist in the context' );
        }

        $file_handle = $context[ self::CONTEXT_FILE_HANDLE ];
        if( get_resource_type($file_handle) !== 'stream' ){
            throw new \Exception( 'The file handle in the context is not a file stream!' );
        }

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired( [
            self::PARAM_CONTEXT_RESULT_NAME,
        ]);
        $stepParamsResolver->setDefault( self::PARAM_MAX_LINES, 1 );

        $params = $stepParamsResolver->resolve($stepActionParams);
        $context_result_name = $params[self::PARAM_CONTEXT_RESULT_NAME];
        $max_lines = $params[self::PARAM_MAX_LINES];
        $max_line_length = $endpointOptions[CsvConfigurableProtocol::OPTION_MAX_LENGTH];

        $rows = [];

        $i = 0;
        while (( $row = fgetcsv($file_handle, $max_line_length, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER])) !== FALSE && $i < $max_lines) {
            $rows[] = $row;
            $i++;
        }

        $context[$context_result_name] = $rows;
    }

    /**
     * Delete a file
     *
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_PATH
     *
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function unlinkFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $full_path = $this->getFullPath( $endpointOptions, $stepActionParams);

        unlink($full_path);
    }

    /**
     * Rename the file for this producer
     *
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_PATH
     * 
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function renameFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $root_path = $this->getRootPath( $endpointOptions, $stepActionParams);

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired( [
            self::PARAM_FILE_PATH,
            self::PARAM_NEW_FILE_PATH,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $new_file_path = $params[self::PARAM_NEW_FILE_PATH];
        $new_full_path = $this->getRootPath( $endpointOptions ) . DIRECTORY_SEPARATOR . $new_file_path;

        $original_file_path = $params[self::PARAM_FILE_PATH];
        $original_full_path = $this->getRootPath( $endpointOptions ) . DIRECTORY_SEPARATOR . $original_file_path;


        rename( $original_full_path, $new_full_path );
    }

    /**
     * Copy a file for this producer
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_NEW_FILE_PATH
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_PATH
     * 
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function copyFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $root_path = $this->getRootPath( $endpointOptions, $stepActionParams);

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired( [
            self::PARAM_FILE_PATH,
            self::PARAM_NEW_FILE_PATH,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $new_file_path = $params[self::PARAM_NEW_FILE_PATH];
        $new_full_path = $this->getRootPath( $endpointOptions ) . DIRECTORY_SEPARATOR . $new_file_path;

        $original_file_path = $params[self::PARAM_FILE_PATH];
        $original_full_path = $this->getRootPath( $endpointOptions ) . DIRECTORY_SEPARATOR . $original_file_path;

        copy( $original_full_path, $new_full_path );
    }

    /**
     * Create a new blank file
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_NEW_FILE_PATH
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_PATH
     * 
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     */
    protected function createFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $full_path = $this->getFullPath( $endpointOptions, $stepActionParams);

        $file_handle = fopen( $full_path, 'w' );
        fclose($file_handle);
    }

    /**
     * resolve the root path from the configuration
     * 
     * @param array                       $endpointOptions
     * @param array                       $stepActionParams
     */
    public function getRootPath( array $endpointOptions )
    {
        return $endpointOptions[CsvConfigurableProtocol::OPTION_ROOT_PATH];
    }

    /**
     * resolve the file path from the configuration
     * use the configured default path if none is set
     * 
     * @param array                       $endpointOptions
     * @param array                       $stepActionParams
     */
    protected function getFilePath( array $endpointOptions, $stepActionParams )
    {
        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setDefault( self::PARAM_FILE_PATH, $endpointOptions[CsvConfigurableProtocol::OPTION_DEFAULT_PATH] );
        $params = $stepParamsResolver->resolve($stepActionParams);
        return $params[self::PARAM_FILE_PATH];
    }

    /**
     * resolve the full path to the file in the configuration
     * 
     * @param array                       $endpointOptions
     * @param array                       $stepActionParams
     */
    protected function getFullPath( array $endpointOptions, $stepActionParams )
    {
        return  $this->getRootPath( $endpointOptions ) . DIRECTORY_SEPARATOR . $this->getFilePath( $endpointOptions, $stepActionParams);
    }
}
