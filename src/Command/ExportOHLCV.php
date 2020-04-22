<?php /** @noinspection DuplicatedCode */

/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Instrument;
use League\Csv\Reader;
use League\Csv\Statement;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportOHLCV extends Command
{
    const LIST_PATH = 'data/source/y_universe.csv';

    const EXPORT_PATH = 'data/source/ohlcv';

    const INTERVAL_DAILY = 'P1D';

    const START_DATE = '2011-01-01';

    /**
     * @var \App\Service\PriceHistory\OHLCV\Yahoo
     */
    protected $priceProvider;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var Psr/Log/LoggerInterface
     */
    protected $logger;

    /**
     * On screen chatter level
     * @var int
     */
    protected $chatter;

    public function __construct(
        \App\Service\PriceHistory\OHLCV\Yahoo $priceProvider,
        \Symfony\Component\Filesystem\Filesystem $filesystem,
        \Symfony\Bridge\Doctrine\RegistryInterface $doctrine,
        \Psr\Log\LoggerInterface $logger,
        $chatter
    )
    {
        $this->priceProvider = $priceProvider;
        $this->filesystem = $filesystem;
        $this->em = $doctrine;
        $this->logger = $logger;
        $this->chatter = $chatter;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('price:export');

        $this->setDescription('Exports prices stored on OHLCV format from database into csv files');

        $this->setHelp(
            <<<'EOT'
Uses a csv file with header to define list of symbols to work on. Symbols found in the csv file should have OHLCV data
saved in database to be able to export them into csv files. Header must contain column titles contained in the current
 file data/source/y_universe.csv. If a file is found with .csv prices it will be backed up with .bak extension.
EOT
        );

        $this->addArgument(
            'input_path',
            InputArgument::OPTIONAL,
            'Path/to/file.csv with list of symbols to work on',
            self::LIST_PATH
        );
        $this->addArgument('export_path', InputArgument::OPTIONAL, 'Path/to/directory for csv files to export', self::EXPORT_PATH);
        $this->addArgument(
            'interval',
            InputArgument::OPTIONAL,
            'Time interval to export OHLCV data for',
            self::INTERVAL_DAILY
        );
        $this->addOption('from-date', null, InputOption::VALUE_REQUIRED, 'Start date of stored history', self::START_DATE);
        $this->addOption('to-date', null, InputOption::VALUE_REQUIRED, 'End date of stored history');
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk');
        $this->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Starting offset, which includes header count. Header has offset=0');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getArgument('input_path');
        $exportPath = $input->getArgument('export_path');
        $interval = $input->getArgument('interval');

        $fromDate = new \DateTime($input->getOption('from-date'));
        $toDate = $input->getOption('to-date')? new \DateTime($input->getOption('to-date')) : null;

        switch ($interval) {
            case 'P1D':
                $period = 'd';
                break;
            case 'P1W':
                $period = 'w';
                break;
            case 'P1M':
                $period = 'm';
                break;
            default:
                throw new \Exception(sprintf('Period %s is not serviced', $interval));
        }

        $repository = $this->em->getRepository(Instrument::class);

        $csv = Reader::createFromPath($inputFile, 'r');
        $csv->setHeaderOffset(0);
        $statement = new Statement();
        if ($input->getOption('offset')) {
            $offset = (int)$input->getOption('offset') - 1;
        } else {
            $offset = 0;
        }
        $statement = $statement->offset($offset);
        if ($chunk = $input->getOption('chunk')) {
            $statement = $statement->limit($chunk);
        }

        $records = $statement->process($csv);

        while ($records->count() > 0) {
            foreach ($records as $key => $record) {
                $instrument = $repository->findOneBySymbol($record['Symbol']);
                $logMsg = sprintf('%s: ', $record['Symbol']);
                $screenMsg = sprintf('%3.3d ', $key) . $logMsg;

                if ($instrument) {
                    // will only return price records which were marked for $this->provider name
                    $history = $this->priceProvider->retrieveHistory($instrument, $fromDate, $toDate, ['interval' => $interval]);

                    if (!empty($history)) {
                        // backup existing csv file for the given period
                        $exportFile = sprintf('%s/%s_%s.csv', $exportPath, $instrument->getSymbol(), $period);
                        if ($this->filesystem->exists($exportFile)) {
                            $backupFileName = $exportFile . '.bak';
                            $this->filesystem->copy($exportFile, $backupFileName);
                            $logMsg .= sprintf('backed up original file into %s', $backupFileName);
                            $screenMsg .= 'backed_up ';
                        }
                        $this->priceProvider->exportHistory($history, $exportFile);
                        $logMsg .= 'saved PH into file ';
                        $screenMsg .= 'exported ';
                    } else {
                        $logMsg .= 'No price history is stored';
                        $screenMsg .= 'no_PH ';
                    }
                } else {
                    $logMsg .= sprintf('Could not find instrument=%s', $record['Symbol']);
                    $screenMsg .=  'no_instr ';
                }

                $this->logAndSay($output, $logMsg, $screenMsg);
            }

            if ($chunk > 0) {
                $offset += $chunk;
            } else {
                $offset += $key;
            }
            $statement = $statement->offset($offset);
            $records = $statement->process($csv);
        }
    }

    /**
     * @param Symfony\Component\Console\Output\OutputInterface $output
     * @param string $logMsg
     * @param string $screenMsg
     */
    private function logAndSay($output, $logMsg, $screenMsg) {
        $this->logger->log(LogLevel::DEBUG, $logMsg);
        if ($output->getVerbosity() >= $this->chatter ) {
            $output->writeln($screenMsg);
        }
    }
}