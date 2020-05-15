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
use App\Entity\OHLCVHistory;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\UtilityServices;

class ExportOHLCV extends Command
{
    const LIST_PATH = 'data/source/y_universe.csv';

    const EXPORT_PATH = 'data/source/ohlcv';

    const INTERVAL_DAILY = 'P1D';

    const START_DATE = '2011-01-01';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \App\Service\UtilityServices
     */
    protected $utilities;

    /**
     * @var string
     */
    protected $provider;

    public function __construct(
        Filesystem $filesystem,
        RegistryInterface $doctrine,
        UtilityServices $utilities
    )
    {
        $this->filesystem = $filesystem;
        $this->em = $doctrine;
        $this->utilities = $utilities;

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

        $this->addUsage('[-v] [--from-date] [--to-date] [--offset=int] [--chunk=int] [--interval=P1D] [--provider=YAHOO] [data/source/y_universe.csv] [data/source/ohlcv]');

        $this->addArgument('input_path', InputArgument::OPTIONAL, 'Path/to/file.csv with list of symbols to work on', self::LIST_PATH);
        $this->addArgument('export_path', InputArgument::OPTIONAL, 'Path/to/directory for csv files to export', self::EXPORT_PATH);
        $this->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Time interval to export OHLCV data for', self::INTERVAL_DAILY);
        $this->addOption('from-date', null, InputOption::VALUE_REQUIRED, 'Start date of stored history', self::START_DATE);
        $this->addOption('to-date', null, InputOption::VALUE_REQUIRED, 'End date of stored history');
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk');
        $this->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Starting offset, which includes header count. Header has offset=0');
        $this->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Name of Price Provider. Must match that in PriceProvider class');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($provider = $input->getOption('provider')) {
            $this->provider = $provider;
        } else {
            $this->provider = null;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->utilities->pronounceStart($this, $output);

        $inputFile = $input->getArgument('input_path');
        $exportPath = $input->getArgument('export_path');
        $interval = $input->getOption('interval');

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
            $priceRepository = $this->em->getRepository(OHLCVHistory::class);
            foreach ($records as $key => $record) {
                $instrument = $repository->findOneBySymbol($record['Symbol']);
                $logMsg = sprintf('%s: ', $record['Symbol']);
                $screenMsg = sprintf('%3.3d ', $key) . $logMsg;

                if ($instrument) {
                    // will only return price records which were marked for $this->provider name
//                    fwrite($fh, sprintf('%4.4s,%s'.PHP_EOL, __LINE__, memory_get_usage()));
//                    $history = $this->priceProvider->retrieveHistory($instrument, $fromDate, $toDate, ['interval' => $interval]);
                    $history = $priceRepository->retrieveHistory($instrument, new \DateInterval($interval), $fromDate, $toDate, $this->provider);
//                    fwrite($fh, sprintf('%4.4s,%s'.PHP_EOL, __LINE__, memory_get_usage()));
                    if (!empty($history)) {
                        // backup existing csv file for the given period
                        $exportFile = sprintf('%s/%s_%s.csv', $exportPath, $instrument->getSymbol(), $period);
                        if ($this->filesystem->exists($exportFile)) {
                            $backupFileName = $exportFile . '.bak';
                            $this->filesystem->copy($exportFile, $backupFileName);
                            $logMsg .= sprintf('backed up original file into %s', $backupFileName);
                            $screenMsg .= 'backed_up ';
                        }
//                        fwrite($fh, sprintf('%4.4s,%s'.PHP_EOL, __LINE__, memory_get_usage()));
//                        $this->priceProvider->exportHistory($history, $exportFile);
                        $csvWriter = Writer::createFromPath($exportFile, 'w');
                        $header = ['Date', 'Open', 'High', 'Low', 'Close', 'Volume'];
                        $csvWriter->insertOne($header);
                        $csvWriter->insertAll(array_map(function($v) {
                            return [$v->getTimestamp()->format('Y-m-d'), $v->getOpen(), $v->getHigh(), $v->getLow(), $v->getClose(), $v->getVolume()];
                        }, $history));

                        $logMsg .= sprintf('exported PH %s ', $interval);
                        $screenMsg .= sprintf('exported %s', $interval);
                        unset($history);
//                        fwrite($fh, sprintf('%4.4s,%s'.PHP_EOL, __LINE__, memory_get_usage()));
                    } else {
                        $logMsg .= 'No price history is stored';
                        $screenMsg .= 'no_PH ';
                    }
                } else {
                    $logMsg .= sprintf('Could not find instrument=%s', $record['Symbol']);
                    $screenMsg .=  'no_instr ';
                }

                $this->utilities->logAndSay($output, $logMsg, $screenMsg);
            }
        $this->utilities->pronounceEnd($this, $output);
    }
}