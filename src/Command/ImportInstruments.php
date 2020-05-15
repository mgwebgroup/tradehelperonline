<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Instrument;
use App\Service\Exchange\Equities\NASDAQ;
use App\Service\Exchange\Equities\NYSE;
use App\Service\UtilityServices;
use League\Csv\Statement;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;

class ImportInstruments extends Command
{
    /**
     * Default list to import
     * List of current company listings can be downloaded from NASDAQ website:
     * https://www.nasdaq.com/screening/company-list.aspx
     */
    const MAIN_FILE = 'data/source/y_universe.csv';

    /**
     * These two designate which exchange a particular symbol belongs to
     */
    const NASDAQ_FILE = 'data/source/nasdaqlisted.csv';
    const NYSE_FILE = 'data/source/otherlisted.csv';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Clear existing database of all instruments
     * @var bool
     */
    protected $clearDb;

    /**
     * If instrument is already imported overwrite with new values
     * @var bool
     */
    protected $overwrite;

    /**
     * @var App\Service\ExchangeInterface
     */
    private $NASDAQ;

    /**
     * @var App\Service\ExchangeInterface
     */
    private $NYSE;

    /**
     * @var UtilityServices
     */
    private $utilities;

    public function __construct(
      RegistryInterface $doctrine,
      UtilityServices $utilities,
      NASDAQ $NASDAQ,
      NYSE $NYSE
    ) {
        $this->utilities = $utilities;
        $this->em = $doctrine->getManager();
        $this->NASDAQ = $NASDAQ;
        $this->NYSE = $NYSE;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('instruments:import');

        $this->setDescription('Imports list of instruments into database');

        $this->setHelp(
          <<<'EOT'
This command uses several files to import stock symbols. The main file with list of all instruments on which this app 
operates is called y_universe. It is saved in data/source/ directory. Each instrument is traded on either NASDAQ or 
NYSE. Two additional files are saved for each exchange individually in the same directory as y_universe. They are 
called nasdaqlisted.csv and otherlisted.csv. They will be consulted to determine which exchange the instrument trades 
at. If an instrument is listed on several exchanges, last one imported will prevail. It is rarely that stocks are 
dually listed. If you find a one that is listed on a wrong exchange after import, you can manually change a record in
 the instruments table. Main file data/source/y_universe.csv must have the following headers:
Symbol,Name,Industry. Order of columns is not important. Other two files that designate exchange must have only one 
column titled Symbol.
EOT
        );

        $this->addUsage('[-v] [--clear-db=false] [--overwrite=false] [data/source/y_universe.csv]');

        $this->addArgument('path', InputArgument::OPTIONAL, 'path/to/file.csv with list of symbols to work on',
                           self::MAIN_FILE);
        $this->addOption('clear-db',null, InputOption::VALUE_OPTIONAL, 'Will clear all instruments from database before import', false);
        $this->addOption('overwrite', null, InputOption::VALUE_OPTIONAL, 'If instrument is already imported will override its values', false);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->clearDb = $input->getOption('clear-db') ? true : false;
        $this->overwrite = $input->getOption('overwrite') ? true : false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->utilities->pronounceStart($this, $output);

        $repository = $this->em->getRepository(Instrument::class);

        if ($this->clearDb) {
            $repository->deleteInstruments();
        }

        $csvMainFile = $input->getArgument('path');

        try {
            $csvMainReader = Reader::createFromPath($csvMainFile, 'r');
            $csvMainReader->setHeaderOffset(0);
            $records = $csvMainReader->getRecords();

            $nasdaqReader = Reader::createFromPath(self::NASDAQ_FILE);
            $nyseReader = Reader::createFromPath(self::NYSE_FILE);

            foreach ($records as $key => $record) {
                $logMsg = sprintf('%3.3d %s: ', $key, $record['Symbol']);
                $screenMsg = $logMsg;

                // TODO: include check for symbol validity with the price provider here
                // ...

                $statement = Statement::create(function($value, $key, $iterator) use ($record) {
                    // $value is line in nasdaq or nyse reader. Numerically indexed.
                    return $value[0] == $record['Symbol'];
                });
                $inNasdaq = $statement->process($nasdaqReader);
                $inNyse = $statement->process($nyseReader);
                if ($inNasdaq->count() > 0) {
                    $exchange = $this->NASDAQ;
                } elseif ($inNyse->count() > 0) {
                    $exchange = $this->NYSE;
                } else {
                    $exchange = null;
                }

                if ($exchange) {
                    if ($this->overwrite) {
                        $instrument = $repository->findOneBySymbol($record['Symbol']);
                    }

                    if (!isset($instrument)) {
                        $instrument = new Instrument();
                        $action = 'imported';
                    } else {
                        $action = 'overwritten';
                    }

                    $instrument->setSymbol(strtoupper($record['Symbol']));
                    $instrument->setExchange($exchange->getExchangeName());
                    $instrument->setName($record['Name']);

                    $this->em->persist($instrument);

                    $logMsg .= $action;
                    $screenMsg = $logMsg;

                    unset($instrument);
                } else {
                    $logMsg .= 'No Exchange found! Will not be imported.';
                    $screenMsg = $logMsg;
                }
                $this->utilities->logAndSay($output, $logMsg, $screenMsg);
            }
            $this->em->flush();
        } catch (\Exception $e) {
            $logMsg = $e->getMessage();
            $screenMsg = $logMsg;
            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
        }

        $this->utilities->pronounceEnd($this, $output);
    }
}