<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use App\Entity\Instrument;
// use Symfony\Component\Console\Style\SymfonyStyle;


class InstrumentFixtures extends Fixture
{
    /**
     * List of current company listings can be downloaded from NASDAQ website:
     * https://www.nasdaq.com/screening/company-list.aspx
     */
	const FILE = 'data/source/y_universe.csv';
    const NYSE_SYMBOLS = 'data/source/nyse_companylist.csv';
    const NASDAQ_SYMBOLS = 'data/source/nasdaq_companylist.csv';
    const AMEX_SYMBOLS = 'data/source/amex_companylist.csv';


    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput();
        // output verbosity is not readable from thie method.

        // var_dump($output->getVerbosity()); exit();
        $output->getFormatter()->setStyle('info-init', new OutputFormatterStyle('white', 'blue'));
        $output->getFormatter()->setStyle('info-end', new OutputFormatterStyle('green', 'blue'));
        // $formatter = new FormatterHelper();
        // $output->writeln('<fg=yellow>Symbol</>,<fg=yellow>Name</>,Weight,<fg=yellow>Industry</>,Shares Held,SPDR Fund,Beta,E1,E2,E3,E4'); exit();
        $output->writeln(sprintf('<info-init>Will import list of instruments from %s file</>', self::FILE));

        $output->writeln('The seeder uses several files to load the stock symbols. The main file with list of all instruments on which');
        $output->writeln('this app operates is called y_universe. Each instrument is traded on either NASDAQ, NYSE or AMEX. Three ');
        $output->writeln('additional files are saved for each exchange individually in the same directory as y_universe. They will be ');
        $output->writeln('looked up to determine which exchange the instrument is listed in. If an instrument is listed on several ');
        $output->writeln('exchanges, last one loaded will prevail. It is rarely that stocks are dually listed. If you find a one that');
        $output->writeln('is listed on a wrong exchange after import, you can manually change a record in the instruments table.');
        $output->writeln('The main file must be saved in data/source/y_universe.csv with the following headers:');
        $output->writeln('<fg=yellow>Symbol</>,<fg=yellow>Name</>,Weight,<fg=yellow>Industry</>,Shares Held,SPDR Fund,Beta,E1,E2,E3,E4');
        $output->writeln('Headers that must be present are highlighted in <fg=yellow>color</>. Count of columns ');
        $output->writeln('is important, however you can skip columns that are beyond the required ones, i.e. Shares Held, etc.');
            
        $nyseSymbols = [];
        foreach($this->getLines(self::NYSE_SYMBOLS) as $line) {
            $fields = explode(',', $line);
            $nyseSymbols[] = trim($fields[0], '"');
        }
        $output->writeln(sprintf('Read in %d symbols for NYSE', count($nyseSymbols)));

        $nasdaqSymbols = [];
        foreach($this->getLines(self::NASDAQ_SYMBOLS) as $line) {
            $fields = explode(',', $line);
            $nasdaqSymbols[] = trim($fields[0], '"');
        }
        $output->writeln(sprintf('Read in %d symbols for NASDAQ', count($nasdaqSymbols)));

        $amexSymbols = [];
        foreach($this->getLines(self::AMEX_SYMBOLS) as $line) {
            $fields = explode(',', $line);
            $amexSymbols[] = trim($fields[0], '"');
        }
        $output->writeln(sprintf('Read in %d symbols for AMEX', count($amexSymbols)));

        $numberOfLines = 0;
        $symbols = $this->getLines(self::FILE);
    	foreach ($symbols as $line) {
            $fields = explode(',', $line);
        	$instrument = new Instrument();
            $symbol = strtoupper($fields[0]);
        	$instrument->setSymbol($symbol);

            if (in_array($symbol, $nyseSymbols)) {
                $instrument->setExchange('NYSE');
            } elseif (in_array($symbol, $nasdaqSymbols)) {
                $instrument->setExchange('NASDAQ');
            } elseif (in_array($symbol, $amexSymbols)) {
                $instrument->setExchange('AMEX');
            }

        	$instrument->setName($fields[1]);
        	$manager->persist($instrument);

            // $this->addReference($symbol, $instrument);

            $output->writeln(sprintf('Imported symbol=%s', $symbol));
    	}

        $manager->flush();

        $numberOfLines = $symbols->getReturn(); 
        if ($numberOfLines > 0 ) { 
            $message = sprintf('<info-end>Imported %d symbols into Instruments table.</>', $numberOfLines);
        } else {
            $message = sprintf('<info-end>No records found in the %s</>', self::FILE);
        }

        $output->writeln($message.PHP_EOL);
    }


    /**
    * Generator
    * Skips first line as header
    * https://www.php.net/manual/en/language.generators.overview.php
    */
    private function getLines($file) {
    	$f = fopen($file, 'r');
        $counter = 0;
	    try {
	        while ($line = fgets($f)) {
	            // will skip first line as header
                if ($counter > 0) yield $line;
                $counter++;
	        }
	    } finally {
	        fclose($f);
	    }

        return $counter-1;
	}
}
