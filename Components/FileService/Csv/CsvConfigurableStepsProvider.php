<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;

use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\NoResultsException;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CsvConfigurableStepsProvider extends Service implements ConfigurableStepsProviderInterface
{
    use UsesConfigurableServiceHelper;

    // Steps
    const STEP_WRITE_TO_FILE = 'write';
    const STEP_APPEND_LINES_TO_FILE = 'append_lines';
    const STEP_READ_FROM_FILE = 'read';
    const STEP_READ_LINES_FROM_FILE = 'read_lines';
    const STEP_REMOVE_FILE = 'remove';
    const STEP_DELETE_FILE = 'delete';
    const STEP_UNLINK_FILE = 'unlink';
    const STEP_RENAME_FILE = 'rename';
    const STEP_MV_FILE = 'mv';
    const STEP_CREATE_FILE = 'create';
    const STEP_COPY_FILE = 'copy';
    const STEP_CLEAN_FILE_HANDLES = 'clean_file_handlers';
    const KEY_RESULTS = 'results';

    // Method Params
    const PARAM_FILE_NAME = 'filename';
    const PARAM_NEW_FILE_PATH = 'new_file_path';
    const PARAM_CSV_ROWS = 'rows';
    const PARAM_CSV_ROW = 'row';
    const PARAM_CONTEXT_RESULT_NAME = 'result_name';
    const PARAM_MAX_LINES = 'max_lines';
    const PARAM_HEADERS = 'headers';

    // Context constants
    const CONTEXT_FILE_HANDLE = 'file_handle';

    /** @var OptionsResolver */
    protected $configResolver;

    /** @var array */
    protected $openFileHandles = [];

    public function __construct()
    {
        parent::__construct();

        if (!$this->configResolver) {
            $protocol = new CsvConfigurableProtocol();
            $this->configResolver = new OptionsResolver();
            $protocol->configureOptionsResolver($this->configResolver);
        }
    }

    /**
     * Open a file handle and store the hash so we can reuse it.
     *
     * @param $fullPath
     * @param $mode
     *
     * @return resource
     *
     * @throws \Exception
     */
    protected function getFileHandle($fullPath, $mode)
    {
        $key = md5($fullPath.$mode);
        if (array_key_exists($key, $this->openFileHandles)) {
            $handle = $this->openFileHandles[$key];
        } else {
            $handle = fopen($fullPath, $mode);
            $this->openFileHandles[$key] = $handle;
        }

        if (get_resource_type($handle) !== 'stream') {
            throw new \Exception('The file handle is not a file stream!');
        }

        if (strpos($mode, 'w') !== false) {
            if (!is_writeable($fullPath) === 'stream') {
                throw new \Exception('The file handle in the context is not writeable!');
            }
        }

        return $handle;
    }

    /**
     * Close all open file handles.
     */
    protected function closeAllFileHandles()
    {
        foreach ($this->openFileHandles as $handle) {
            fclose($handle);
        }
        $this->openFileHandles = [];
    }

    /**
     * {@inheritdoc}
     */
    public function executeSteps(array $stepsConfig, array &$options, array &$context)
    {
        foreach ($stepsConfig as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $resolvedParams = $this->getConfHelper()->resolveArray($stepActionParams, $context);
                $this->executeStep($stepAction, $resolvedParams, $options, $context);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeStep($stepAction, array &$stepActionParams, array &$options, array &$context)
    {
        $handled = $this->getConfHelper()->executeStep($stepAction, $stepActionParams, $options, $context);

        if ($handled) {
            return true;
        }

        switch ($stepAction) {
            case self::STEP_CLEAN_FILE_HANDLES:
                $this->closeAllFileHandles();

                return true;
            case self::STEP_WRITE_TO_FILE:
                $this->writeToFile($stepActionParams, $options, $context);

                return true;

            case self::STEP_APPEND_LINES_TO_FILE:
                $this->appendLines($stepActionParams, $options, $context);

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
     * Write rows out to a csv file.
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CSV_ROWS
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_NAME
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     */
    protected function writeToFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired([
            self::PARAM_FILE_NAME,
            self::PARAM_CSV_ROWS,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $filePath = $params[self::PARAM_FILE_NAME];
        $fullPath = $this->getRootPath($endpointOptions).DIRECTORY_SEPARATOR.$filePath;

        $rows = $params[self::PARAM_CSV_ROWS];

        //open the file, reset the pointer to zero, create it if not already created
        $fileHandle = fopen($fullPath, 'w');

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $type = gettype($row);
                throw new \InvalidArgumentException("Row in Rows is not an array, {$type} given.");
            }

            fputcsv($fileHandle, $row, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER], $endpointOptions[CsvConfigurableProtocol::OPTION_ENCLOSURE], $endpointOptions[CsvConfigurableProtocol::OPTION_ESCAPE_CHAR]);
        }

        fclose($fileHandle);
    }

    /**
     * Write an array of lines to a csv file in to a context variable
     * it is assumed the file handle is in the context.
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CSV_ROWS
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     */
    protected function appendLines(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired([
            self::PARAM_CSV_ROWS,
        ]);
        $stepParamsResolver->setDefault(self::PARAM_FILE_NAME, $endpointOptions[CsvConfigurableProtocol::OPTION_PATH]);
        $stepParamsResolver->setDefault(self::PARAM_HEADERS, null);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $filePath = $params[self::PARAM_FILE_NAME];
        $fullPath = $this->getRootPath($endpointOptions).DIRECTORY_SEPARATOR.$filePath;

        $rows = $params[self::PARAM_CSV_ROWS];

        $headers = $params[self::PARAM_HEADERS];
        if (is_array($headers) && !file_exists($fullPath)) {
            $rows = array_merge([$headers], $rows);
        }

        $fileHandle = $this->getFileHandle($fullPath, 'a');

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $type = gettype($row);
                throw new \InvalidArgumentException("Row in Rows is not an array, {$type} given.");
            }

            fputcsv($fileHandle, $row, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER], $endpointOptions[CsvConfigurableProtocol::OPTION_ENCLOSURE], $endpointOptions[CsvConfigurableProtocol::OPTION_ESCAPE_CHAR]);
        }
    }

    /**
     * Read a csv file in to a context variable.
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CONTEXT_RESULT_NAME
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_NAME
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     */
    protected function readFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired([
            self::PARAM_FILE_NAME,
            self::PARAM_CONTEXT_RESULT_NAME,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $filePath = $params[self::PARAM_FILE_NAME];
        $fullPath = $this->getRootPath($endpointOptions).DIRECTORY_SEPARATOR.$filePath;

        $contextResultName = $params[self::PARAM_CONTEXT_RESULT_NAME];
        $maxLineLength = $endpointOptions[CsvConfigurableProtocol::OPTION_MAX_LENGTH];

        //open the file, reset the pointer to zero
        $fileHandle = fopen($fullPath, 'r');

        $rows = [];

        while (($row = fgetcsv($fileHandle, $maxLineLength, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER])) !== false) {
            $rows[] = $row;
        }

        fclose($fileHandle);

        $context[$contextResultName] = $rows;
    }

    /**
     * Read a line from a csv file in to a context variable
     * it is assumed the file handle is in the context.
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_CONTEXT_RESULT_NAME
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_MAX_LINES Defaults to 1
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     *
     * @throws NoResultsException if there are no more lines to consume
     */
    protected function readLines(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired([
            self::PARAM_CONTEXT_RESULT_NAME,
        ]);

        $stepParamsResolver->setDefault(self::PARAM_MAX_LINES, 1);
        $stepParamsResolver->setDefault(self::PARAM_FILE_NAME, null);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $fullPath = $this->getRootPath($endpointOptions);
        if ($params[self::PARAM_FILE_NAME] !== null) {
            if (substr($fullPath, -1) !== DIRECTORY_SEPARATOR) {
                $fullPath .= DIRECTORY_SEPARATOR;
            }
            $fullPath .= $params[self::PARAM_FILE_NAME];
        }

        $fileHandle = $this->getFileHandle($fullPath, 'r');

        $contextResultName = $params[self::PARAM_CONTEXT_RESULT_NAME];
        $maxLines = $params[self::PARAM_MAX_LINES];
        $maxLineLength = $endpointOptions[CsvConfigurableProtocol::OPTION_MAX_LENGTH];

        $rows = [];

        $i = 0;
        while ($i < $maxLines) {
            $row = fgetcsv($fileHandle, $maxLineLength, $endpointOptions[CsvConfigurableProtocol::OPTION_DELIMITER]);

            if ($row === false) {
                break;
            }

            $rows[] = $row;
            ++$i;
        }

        if (count($rows) === 0) {
            throw new NoResultsException("No more results from $$fullPath");
        }

        $context[self::KEY_RESULTS][$contextResultName] = $rows;
    }

    /**
     * Delete a file.
     *
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_NAME
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     */
    protected function unlinkFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $fullPath = $this->getFullPath($endpointOptions, $stepActionParams);

        unlink($fullPath);
    }

    /**
     * Rename the file for this producer.
     *
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_NAME
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     */
    protected function renameFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $rootPath = $this->getRootPath($endpointOptions, $stepActionParams);

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired([
            self::PARAM_FILE_NAME,
            self::PARAM_NEW_FILE_PATH,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $newFilePath = $params[self::PARAM_NEW_FILE_PATH];
        $newFullPath = $rootPath.DIRECTORY_SEPARATOR.$newFilePath;

        $originalFilePath = $params[self::PARAM_FILE_NAME];
        $originalFullPath = $rootPath.DIRECTORY_SEPARATOR.$originalFilePath;

        rename($originalFullPath, $newFullPath);
    }

    /**
     * Copy a file for this producer.
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_NEW_FILE_PATH
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_NAME
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     */
    protected function copyFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $rootPath = $this->getRootPath($endpointOptions, $stepActionParams);

        $stepParamsResolver = new OptionsResolver();
        $stepParamsResolver->setRequired([
            self::PARAM_FILE_NAME,
            self::PARAM_NEW_FILE_PATH,
        ]);
        $params = $stepParamsResolver->resolve($stepActionParams);

        $newFilePath = $params[self::PARAM_NEW_FILE_PATH];
        $newFullPath = $rootPath.DIRECTORY_SEPARATOR.$newFilePath;

        $originalFilePath = $params[self::PARAM_FILE_NAME];
        $originalFullPath = $rootPath.DIRECTORY_SEPARATOR.$originalFilePath;

        copy($originalFullPath, $newFullPath);
    }

    /**
     * Create a new blank file.
     *
     * Required Params:
     *     - CsvConfigurableProducer::PARAM_NEW_FILE_PATH
     * Optional Params:
     *     - CsvConfigurableProducer::PARAM_FILE_NAME
     *
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     */
    protected function createFile(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $fullPath = $this->getFullPath($endpointOptions, $stepActionParams);

        $fileHandle = fopen($fullPath, 'w');
        fclose($fileHandle);
    }

    /**
     * resolve the root path from the configuration.
     *
     * @param array $endpointOptions
     *
     * @return string The root path as in the configuration
     */
    public function getRootPath(array $endpointOptions)
    {
        return $endpointOptions[CsvConfigurableProtocol::OPTION_PATH];
    }

    /**
     * Resolve the file path from the configuration
     * use the configured default path if none is set.
     *
     * @param array $endpointOptions
     * @param array $stepActionParams
     *
     * @return string The file path to the file as in the configuration
     */
    protected function getFilePath(array $endpointOptions, $stepActionParams)
    {
        if (array_key_exists(self::PARAM_FILE_NAME, $stepActionParams)) {
            return $stepActionParams[self::PARAM_FILE_NAME];
        } else {
            return $endpointOptions[CsvConfigurableProtocol::OPTION_PATH];
        }
    }

    /**
     * resolve the full path to the file in the configuration.
     *
     * @param array $endpointOptions
     * @param array $stepActionParams
     *
     * @return string A full path to the file from the configuration
     */
    protected function getFullPath(array $endpointOptions, $stepActionParams)
    {
        return  $this->getRootPath($endpointOptions).DIRECTORY_SEPARATOR.$this->getFilePath($endpointOptions, $stepActionParams);
    }
}
