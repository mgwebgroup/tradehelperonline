<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use App\Entity\OHLCVHistory;
use Symfony\Component\Finder\Finder;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use League\Csv\Reader;


class OHLCVFixtures_QT extends Fixture implements FixtureGroupInterface
{
	const DIRECTORY = 'data/source/ohlcv';
    const SUFFIX_DAILY = '_d';
    const SUFFIX_WEEKLY = '_w';

    private $manager;

    private $timeStart;

    public static function getGroups(): array
    {
        return ['OHLCV','Q-T'];
    }

    public function load(ObjectManager $manager)
    {
        $this->timeStart = time();
        $this->manager = $manager;
        $letters = 'Q-T';
        $output = new ConsoleOutput();
        $output->getFormatter()->setStyle('info-init', new OutputFormatterStyle('white', 'blue'));
        $output->getFormatter()->setStyle('info-end', new OutputFormatterStyle('green', 'blue'));
        $output->getFormatter()->setStyle('letters', new OutputFormatterStyle('yellow', 'black'));

        $output->writeln(sprintf('<info-init>Will import OHLCV daily and weekly price history from %s </>', self::DIRECTORY));
        $output->writeln(null);

        $output->writeln('This seeder will read csv files stored in the ohlcv directory and if their symbol was imported by the InstrumentFixtures seeder will import daily and weekly price history.');
        $output->writeln(null);
        $output->writeln(sprintf('Letters <letters>%s</>:', $letters));
        $output->writeln(null);

        // load daily
        $output->writeln('Daily:');
        $suffix = self::SUFFIX_DAILY.'.csv';

        $importedFiles = $this->importFiles($suffix, $output, $letters);

        $output->writeln(sprintf('<info-end>Imported %d daily files</>', $importedFiles));

        // load weekly
        $output->writeln('Weekly:');
        $suffix = self::SUFFIX_WEEKLY.'.csv';

        $importedFiles = $this->importFiles($suffix, $output, $letters);

        $output->writeln(sprintf('<info-end>Imported %d weekly files</info-end>', $importedFiles));

        $minutes = (time() - $this->timeStart)/60;
        $output->writeln(null);
        $output->writeln(sprintf('<info-end>Execution time: %s minutes</info-end>', $minutes));
    }

    /**
     * Imports csv files from the defined data directory.
     * Columns in files must follow strict order: Date, Open, High, Low, Close, Volume. Rest of the columns will be ignored.
     * File names must be similar to: AAPL_d.csv or ABX_w.csv
     * @param string $suffix for the file name, i.e. _d.csv
     * @param $output
     * @return integer number of files imported
     * @throws \Exception
     */
    private function importFiles($suffix, $output, $letters = 'A-Z')
    {
        $repository = $this->manager->getRepository(\App\Entity\Instrument::class);

        $finder = new Finder();
        $finder->in(self::DIRECTORY)->files()->name(sprintf('/^[%s][A-Z]*%s/', $letters, $suffix))->sortByName();
        $fileCount = $finder->count();
        $output->writeln(sprintf('Found %d files ending in %s', $fileCount, $suffix));

        $importedFiles = 0;
        // foreach file select the symbol
        foreach ($finder as $file) {
            $symbol = strtoupper($file->getBasename($suffix));
            $importedRecords = 0;

            $instrument = $repository->findOneBy(['symbol' => $symbol]);
            if ($instrument) {
                $fileName = $file->getPath().'/'.$file->getBasename();
                $csv = Reader::createFromPath($fileName, 'r');
                $csv->setHeaderOffset(0);

                /** @var League\Csv\MapIterator $lines */
                $lines = $csv->getRecords();

                foreach ($lines as $line) {
                    $OHLCVHistory = new OHLCVHistory();
                    $OHLCVHistory->setTimestamp(new \DateTime($line['Date']));
                    $OHLCVHistory->setOpen($line['Open']);
                    $OHLCVHistory->setHigh($line['High']);
                    $OHLCVHistory->setLow($line['Low']);
                    $OHLCVHistory->setClose($line['Close']);
                    $OHLCVHistory->setVolume((int)$line['Volume']);
                    $OHLCVHistory->setInstrument($instrument);
                    switch ($suffix) {
                        case self::SUFFIX_DAILY . '.csv':
                            $OHLCVHistory->setTimeinterval(new \DateInterval('P1D'));
                            break;
                        case self::SUFFIX_WEEKLY . '.csv':
                            $OHLCVHistory->setTimeinterval(new \DateInterval('P1W'));
                            break;
                        default:
                            throw new \Exception('Unexpected value');
                    }

                    $this->manager->persist($OHLCVHistory);

                    $importedRecords++;
                }
                $this->manager->flush();
                $importedFiles++;
                $message = sprintf('%3d %s: will import %d price records', $importedFiles, $file->getBasename(), $importedRecords);
            } else {
                $message = sprintf('%s: instrument record was not imported, skipping file', $file->getBasename());
            }
            $output->writeln($message);
        }

        return $importedFiles;
    }
}
