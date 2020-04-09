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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;

class SyncPrice extends Command
{
    const DEFAULT_PATH = 'data/source/y_universe.csv';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var League\Csv\Reader
     */
    protected $csv;

    protected $priceProvider;

    protected function configure()
    {
        $this->setName('price:sync');

        $this->setDescription('Syncs up prices for a list of stocks from a price provider');

        $this->setHelp(
            <<<'EOT'
Uses a csv file with header and list of symbols to define list of symbols to work on. Symbols found in the csv file must
 also be imported as instruments. You can use data fixtures to import all instruments. Header must contain column titles
 contained in the current file data/source/y_universe.csv.
EOT
        );

        $this->addUsage('data/source/y_universe.csv');

        $this->addArgument('path', InputArgument::OPTIONAL, 'path/to/file.csv with list of symbols to work on', self::DEFAULT_PATH);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getKernel()->getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->priceProvider = $container->get(\App\Service\PriceHistory\OHLCV\Yahoo::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->em->getRepository(Instrument::class);

        $csvFile = $input->getArgument('path');
        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        foreach ($records as $key => $record) {
            $instrument = $repository->findOneBySymbol($record['Symbol']);

            // history exists? Download if missing.

            // gaps exist between today and last day of history? download missing history

            // add quote if market open

            // add closing price if market closed

            $output->writeln(sprintf('%3d %5.5s %-30.40s', $key, $record['Symbol'], $record['Name']));
        }
    }
}