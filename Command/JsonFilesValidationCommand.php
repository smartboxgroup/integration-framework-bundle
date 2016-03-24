<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use JMS\Serializer\DeserializationContext;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\EntityInterface;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class JsonFilesValidationCommand.
 */
class JsonFilesValidationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('smartbox:validate:json')
            ->setDescription('Validation of fixture files for JsonLoaderProducer')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to directory with fixture files in format: @BundleName/path/inside/this/bundle/'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getContainer()->get('kernel');
        $serializer = $this->getContainer()->get('jms_serializer');

        $path = $input->getArgument('path');
        $absolutePath = $kernel->locateResource($path);

        $finder = new Finder();
        $iterator = $finder->files()->in($absolutePath);
        $validationErrors = [];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $realPath = $file->getRealPath();

            $context = new DeserializationContext();
            $context->setGroups(array(EntityInterface::GROUP_METADATA, EntityInterface::GROUP_PUBLIC));

            try {
                $entity = $serializer->deserialize(file_get_contents($realPath), SerializableInterface::class, 'json', $context);

                if (get_class($entity) == Entity::class) {
                    $validationErrors[$realPath] = sprintf(
                        'This object cant\'t be of "%s" type.',
                        Entity::class
                    );
                } else {
                    $errors = $this->getContainer()->get('validator')->validate($entity);
                    if (count($errors) > 0) {
                        $validationErrors[$realPath] = $errors;
                    }
                }

                if (!array_key_exists($realPath, $validationErrors)) {
                    $output->write('<info>.</info>');
                } else {
                    $output->write('<error>.</error>');
                }
            } catch (\RuntimeException $e) {
                $validationErrors[$realPath] = $e->getMessage();
            }
        }

        if (!empty($validationErrors)) {
            $output->writeln('');
            $output->writeln('');
            $output->writeln(sprintf('<error>Some fixture files in "%s" directory have invalid format.</error>', $path));
            foreach ($validationErrors as $file => $error) {
                $output->writeln('File: '.str_replace($absolutePath.'/', '', $file).' :');

                if ($error instanceof ConstraintViolationList) {
                    foreach ($error as $violation) {
                        $output->writeln("\t".$violation->getPropertyPath().' : '.$violation->getMessage());
                    }
                } else {
                    $output->writeln("\t".$error);
                }
                $output->writeln('');
            }
        } else {
            $output->writeln('<info>Everything is OK.</info>');
        }
    }
}
