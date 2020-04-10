<?php
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
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

    public function __construct(
        \App\Service\PriceHistory\OHLCV\Yahoo $priceProvider,
        \Symfony\Component\Filesystem\Filesystem $filesystem,
        \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
    )
    {
        $this->priceProvider = $priceProvider;
        $this->filesystem = $filesystem;
        $this->em = $doctrine;

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
 file data/source/y_universe.csv. All arguments are optional. See Typical usage for details.
EOT
        );

        $this->addUsage('data/source/y_universe.csv data/source/ohlcv P1D 2019-05-01');

        $this->addArgument('input_path', InputArgument::OPTIONAL, 'Path/to/file.csv with list of symbols to work on', self::LIST_PATH);
        $this->addArgument('export_path', InputArgument::OPTIONAL, 'Path/to csv files to export', self::EXPORT_PATH);
        $this->addArgument('interval', InputArgument::OPTIONAL, 'Time interval to export OHLCV data for', self::INTERVAL_DAILY);
        $this->addArgument('from_date', InputArgument::OPTIONAL, 'Start date of stored history', self::START_DATE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getArgument('input_path');
        $exportPath = $input->getArgument('export_path');
        $interval = $input->getArgument('interval');
        $fromDate = new \DateTime($input->getArgument('from_date'));

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

        $csvReader = Reader::createFromPath($inputFile, 'r');
        $csvReader->setHeaderOffset(0);
        $records = $csvReader->getRecords();

        foreach ($records as $record) {
            $instrument = $repository->findOneBySymbol($record['Symbol']);

            if ($instrument) {
                // will only return price records which were marked for $this->provider name
                $history = $this->priceProvider->retrieveHistory($instrument, $fromDate, null, ['interval' => $interval]);

                if (!empty($history)) {
                    // backup existing csv file for the given period
                    $exportFile = sprintf('%s/%s_%s.csv', $exportPath, $instrument->getSymbol(), $period);
                    if ($this->filesystem->exists($exportFile)) {
                        $this->filesystem->copy($exportFile, $exportFile . '.bak');
                    }

                    $this->priceProvider->exportHistory($history, $exportFile);
//                    $csvWriter = Writer::createFromPath($exportFile, 'w');
//                    $csvWriter->insertOne(['Date', 'Open', 'High', 'Low', 'Close', 'Volume']);
//                    $csvWriter->insertAll(array_map(function($v) {
//                        return [
//                            $v->getTimestamp()->format('Y-m-d'),
//                            $v->getOpen(),
//                            $v->getHigh(),
//                            $v->getLow(),
//                            $v->getClose(),
//                            $v->getVolume()
//                            ];
//                    }, $history));
                }
            } else {
                throw new \Exception(sprintf('Could not find instrument=%s', $record['Symbol']));
            }
        }
    }
}