<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Command;

use FINDOLOGIC\PlentyMarketsRestExporter\Config;
use FINDOLOGIC\PlentyMarketsRestExporter\Exporter\Exporter;
use FINDOLOGIC\PlentyMarketsRestExporter\Utils;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Log4Php\Configurators\LoggerConfigurationAdapterXML;
use Log4Php\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class ExportCommand extends Command
{
    private const IMPORT_LOG_PATH = __DIR__ . '/../../logs/import.log';

    private const LOGGER_CONFIG = __DIR__ . '/../../config/logger.xml';

    protected static $defaultName = 'export:start';

    /** @var LoggerInterface */
    private $internalLogger;

    /** @var LoggerInterface */
    private $customerLogger;

    /** @var Exporter|null */
    private $exporter;

    /** @var Client|null */
    private $client;

    public function __construct(
        ?LoggerInterface $internalLogger = null,
        ?LoggerInterface $customerLogger = null,
        ?Exporter $exporter = null,
        ?Client $client = null
    ) {
        parent::__construct();

        $this->exporter = $exporter;
        $this->client = $client;

        $this->configureLoggers();
        $this->internalLogger = $internalLogger ?? Logger::getLogger('import.php');
        $this->customerLogger = $customerLogger ?? Logger::getLogger('import.php');
    }

    protected function configure()
    {
        $this->setDescription('Starts an export.')
            ->setHelp('This commands starts the export of product data of a specific shop.');

        $this->addArgument(
            'shopkey',
            InputArgument::OPTIONAL,
            'Optionally add the shopkey of a specific service. Note that this requires' .
            ' the config "customerLoginUri" to be set in config/config.yml.',
        );

        $this->addOption(
            'type',
            't',
            InputOption::VALUE_OPTIONAL,
            sprintf(
                'Changes the export format. Possible values are CSV (%d) or XML (%d).',
                Exporter::TYPE_CSV,
                Exporter::TYPE_XML
            ),
            Exporter::TYPE_CSV
        );

        $this->addOption(
            'ignore-export-warning',
            'w',
            InputOption::VALUE_NONE,
            'Ignores the export question when there is already data inside the export directory.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $shopkey = Utils::validateAndGetShopkey($input->getArgument('shopkey'));
        $config = Utils::getExportConfiguration($shopkey, Config::DEFAULT_CONFIG_FILE, $this->client);

        $exporterType = (int)$input->getOption('type');
        $this->exporter = $this->getExporter($exporterType, $config);

        if (!$this->shouldStartExportIfFileAlreadyExists($input, $output, $exporterType)) {
            $io->note('You can remove the existing files all the time by running "bin/console export:clear".');
            $io->comment('Export has not been started. Files will not be deleted.');

            return Command::SUCCESS;
        }

        try {
            $this->exporter->export();
        } catch (Throwable $e) {
            $io->error(sprintf('Something went wrong. Message: %s', $e->getMessage()));
            $this->internalLogger->trace($e->getTraceAsString());

            return Command::FAILURE;
        }

        $path = realpath($this->exporter->getWrapper()->getExportPath());
        $io->note(sprintf('Exported file(s) can be found here: %s', $path));
        $io->success(sprintf('Export finished successfully. Export time: %s.', $this->exporter->getExportTime()));

        return Command::SUCCESS;
    }

    private function configureLoggers(): void
    {
        // Empty log before each new import.
        if (file_exists(self::IMPORT_LOG_PATH)) {
            file_put_contents(self::IMPORT_LOG_PATH, '');
        }

        $configurationAdapter = new LoggerConfigurationAdapterXML();
        $configuration = $configurationAdapter->convert(self::LOGGER_CONFIG);
        $configuration['appenders']['default']['params']['file'] = self::IMPORT_LOG_PATH;

        Logger::configure($configuration);
    }

    private function shouldStartExportIfFileAlreadyExists(
        InputInterface $input,
        OutputInterface $output,
        int $exporterType
    ): bool {
        if ($input->getOption('ignore-export-warning')) {
            return true;
        }

        $exportFileLocation = getenv('export_location') ?? Exporter::DEFAULT_LOCATION;
        $isCsvExporter = $exporterType === Exporter::TYPE_CSV;
        if (!$isCsvExporter || !file_exists($exportFileLocation . '/findologic.csv')) {
            return true;
        }

        $io = new SymfonyStyle($input, $output);
        $io->note('You may pass --ignore-export-warning (-w) to ignore this message.');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Export data already exists at the export path. Do you want to continue? (y/n) ',
            false
        );

        return $helper->ask($input, $output, $question);
    }

    /**
     * @codeCoverageIgnore Creating an instance here would run an actual export.
     */
    private function getExporter(int $type, Config $config): Exporter
    {
        if ($this->exporter) {
            return $this->exporter;
        }

        return Exporter::buildInstance($type, $config, $this->internalLogger, $this->customerLogger);
    }
}
