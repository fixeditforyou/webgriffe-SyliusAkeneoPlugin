<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilderInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class EnqueueCommand extends Command
{
    use LockableTrait;

    public const SINCE_OPTION_NAME = 'since';

    public const SINCE_FILE_OPTION_NAME = 'since-file';

    private const ALL_OPTION_NAME = 'all';

    private const IMPORTER_OPTION_NAME = 'importer';

    protected static $defaultName = 'webgriffe:akeneo:enqueue';

    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var FactoryInterface */
    private $queueItemFactory;

    /** @var DateTimeBuilderInterface */
    private $dateTimeBuilder;

    /** @var ImporterRegistryInterface */
    private $importerRegistry;

    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository,
        FactoryInterface $queueItemFactory,
        DateTimeBuilderInterface $dateTimeBuilder,
        ImporterRegistryInterface $importerRegistry
    ) {
        $this->queueItemRepository = $queueItemRepository;
        $this->queueItemFactory = $queueItemFactory;
        $this->dateTimeBuilder = $dateTimeBuilder;
        $this->importerRegistry = $importerRegistry;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Populate the Queue with Akeneo\'s entities that has been modified since a specified date/datetime'
        );
        $this->addOption(
            self::SINCE_OPTION_NAME,
            's',
            InputOption::VALUE_REQUIRED,
            'Date or datetime with format Y-m-d H:i:s'
        );
        $this->addOption(
            self::SINCE_FILE_OPTION_NAME,
            'sf',
            InputOption::VALUE_REQUIRED,
            'Relative or absolute path to a file containing a datetime'
        );
        $this->addOption(
            self::ALL_OPTION_NAME,
            'a',
            InputOption::VALUE_NONE,
            'Enqueue all identifiers regardless their last modified date.'
        );
        $this->addOption(
            self::IMPORTER_OPTION_NAME,
            'i',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Enqueue items only for specified importers'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sinceFilePath = null;
        if ($sinceOptionValue = $input->getOption(self::SINCE_OPTION_NAME)) {
            try {
                Assert::string($sinceOptionValue);
                /** @var string $sinceOptionValue */
                $sinceDate = new \DateTime($sinceOptionValue);
            } catch (\Throwable $t) {
                throw new \InvalidArgumentException(
                    sprintf('The "%s" argument must be a valid date', self::SINCE_OPTION_NAME)
                );
            }
        } elseif ($sinceFilePath = $input->getOption(self::SINCE_FILE_OPTION_NAME)) {
            Assert::string($sinceFilePath);
            /** @var string $sinceFilePath */
            $sinceDate = $this->getSinceDateByFile($sinceFilePath);
        } elseif ($input->getOption(self::ALL_OPTION_NAME) === true) {
            $sinceDate = (new \DateTime())->setTimestamp(0);
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'One of "--%s", "--%s" or "--%s" option must be specified',
                    self::SINCE_OPTION_NAME,
                    self::SINCE_FILE_OPTION_NAME,
                    self::ALL_OPTION_NAME
                )
            );
        }

        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $runDate = $this->dateTimeBuilder->build();
        foreach ($this->getImporters($input) as $importer) {
            $identifiers = $importer->getIdentifiersModifiedSince($sinceDate);
            if (count($identifiers) === 0) {
                $output->writeln(
                    sprintf(
                        'There are no <info>%s</info> entities modified since <info>%s</info>',
                        $importer->getAkeneoEntity(),
                        $sinceDate->format('Y-m-d H:i:s')
                    )
                );

                continue;
            }
            foreach ($identifiers as $identifier) {
                if ($this->isEntityAlreadyQueuedToImport($importer->getAkeneoEntity(), $identifier)) {
                    continue;
                }
                $queueItem = $this->queueItemFactory->createNew();
                Assert::isInstanceOf($queueItem, QueueItemInterface::class);
                /** @var QueueItemInterface $queueItem */
                $queueItem->setAkeneoEntity($importer->getAkeneoEntity());
                $queueItem->setAkeneoIdentifier($identifier);
                $queueItem->setCreatedAt(new \DateTime());
                $this->queueItemRepository->add($queueItem);
                $output->writeln(
                    sprintf(
                        '<info>%s</info> entity with identifier <info>%s</info> enqueued.',
                        $importer->getAkeneoEntity(),
                        $identifier
                    )
                );
            }
        }

        if ($sinceFilePath) {
            $this->writeSinceDateFile($sinceFilePath, $runDate);
        }

        $this->release();

        return 0;
    }

    private function getSinceDateByFile(string $filepath): \DateTime
    {
        if (!file_exists($filepath)) {
            throw new \InvalidArgumentException(
                sprintf('The file "%s" does not exists', $filepath)
            );
        }
        if (!is_readable($filepath)) {
            throw new \InvalidArgumentException(
                sprintf('The file "%s" is not readable', $filepath)
            );
        }
        if (!is_writable($filepath)) {
            throw new \InvalidArgumentException(
                sprintf('The file "%s" is not writable', $filepath)
            );
        }

        try {
            $content = file_get_contents($filepath);
            Assert::string($content);
            /** @var string $content */
            $sinceDate = new \DateTime(trim($content));
        } catch (\Throwable $t) {
            throw new \RuntimeException(sprintf('The file "%s" must contain a valid datetime', $filepath), 0, $t);
        }

        return $sinceDate;
    }

    private function writeSinceDateFile(string $filepath, \DateTime $runDate): void
    {
        file_put_contents($filepath, $runDate->format('c'));
    }

    private function isEntityAlreadyQueuedToImport(string $akeneoEntity, string $akeneoIdentifier): bool
    {
        $queueItem = $this->queueItemRepository->findOneToImport($akeneoEntity, $akeneoIdentifier);
        if ($queueItem) {
            return true;
        }

        return false;
    }

    /**
     * @return ImporterInterface[]
     */
    private function getImporters(InputInterface $input): array
    {
        $allImporters = $this->importerRegistry->all();
        if (count($allImporters) === 0) {
            throw new \RuntimeException('There are no importers in registry.');
        }
        $importersCodes = array_map(
            static function (ImporterInterface $importer) {
                return $importer->getAkeneoEntity();
            },
            $allImporters
        );

        $importersToUse = $input->getOption(self::IMPORTER_OPTION_NAME);
        Assert::isArray($importersToUse);

        if (count($importersToUse) === 0) {
            return $allImporters;
        }

        $allImporters = array_combine($importersCodes, $allImporters);
        Assert::isArray($allImporters);

        $importers = [];
        foreach ($importersToUse as $importerToUse) {
            if (!array_key_exists($importerToUse, $allImporters)) {
                throw new \InvalidArgumentException(sprintf('Importer "%s" does not exists.', $importerToUse));
            }
            $importers[] = $allImporters[$importerToUse];
        }

        return $importers;
    }
}
