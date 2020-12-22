<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Studies\MGWebGroup\MarketSurvey\Command;

use App\Entity\Study\Study;
use App\Exception\PriceHistoryException;
use App\Service\Exchange\Equities\TradingCalendar;
use App\Studies\MGWebGroup\MarketSurvey\StudyBuilder;
use MathPHP\Exception\BadDataException;
use MathPHP\Exception\OutOfBoundsException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\Watchlist;
use App\Studies\MGWebGroup\MarketSurvey\Exception\StudyException;

class StudyManager extends Command
{
    const ROLLING_PERIOD = 20;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Create (true) or delete (false) a study
     * Null when action is undefined
     * @var bool | null
     */
    protected $action = null;

    /**
     * Name of study
     * @var string
     */
    protected $name;

    /**
     * Version of study
     * @var string
     */
    protected $version;

    /**
     * @var App\Entity\Watchlist
     */
    protected $watchlist;

    /**
     * @var App\Service\Exchange\Equities
     */
    protected $tradingCalendar;

    /**
     * Date to delete a study for
     * @var \DateTimeInterface
     */
    protected $deleteDate;

    /**
     * Date to create a study for
     * @var \DateTimeInterface
     */
    protected $studyDate;

    /**
     * @var StudyBuilder
     */
    protected $studyBuilder;


    public function __construct(
      RegistryInterface $doctrine,
      TradingCalendar $tradingCalendar,
      StudyBuilder $studyBuilder
    ) {
        $this->em = $doctrine->getManager();
        $this->tradingCalendar = $tradingCalendar;
        $this->studyBuilder = $studyBuilder;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mgweb:studymanager');

        $this->setDescription(
          'Manages Studies authored by mgwebgroup.'
        );

        $this->setHelp(
          <<<'EOT'
This command creates and deletes studies created by the MGWebGroup/MarketSurvey bundle.
The -f flag will create Market Score tables. This requires at least 20 studies already saved. The saved studies 
must have the Market Breadth array attribute and Market Score float attribute associated with each.
EOT
        );

        $this->addUsage('[-v] [--name=market_study] [--ver=20201201] [--date=DATE] [-f] y_universe [spdr_sectors]');
        $this->addUsage('[-v] -d [--name=STUDY_NAME] \'2020-12-15\'');

        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the Study', 'market_study');
        $this->addOption('ver', null, InputOption::VALUE_REQUIRED, 'Version of the Study');
        $this->addArgument('identificator', InputArgument::REQUIRED, 'Either a watchlist name (when creating study) or study date (when deleting study)');
        $this->addArgument('spdr_sectors', InputArgument::OPTIONAL, 'Name of watchlist with spdr sectors', 'spdr_sectors');
        $this->addOption('delete', 'd', InputOption::VALUE_NONE, 'Flag to delete a study');
        $this->addOption('date', null, InputOption::VALUE_REQUIRED, 'Date to create a study for');
        $this->addOption('full', 'f', InputOption::VALUE_NONE, 'Will create market score tables. This requires 20 studies already saved.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->name = $input->getOption('name');
        $this->version = $input->getOption('ver')? : null;
        $identificator = $input->getArgument('identificator');
        try {
            $date = new \DateTime($identificator);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Failed to parse time string')) {
                if ($input->getOption('delete')) {
                    $output->writeln('<error>ERROR: </error>Invalid date argument supplied with -d flag to delete a study');
                    exit(1);
                }
                // look for watchlist
                $watchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => $identificator]);
                if ($watchlist) {
                    $this->watchlist = $watchlist;
                } else {
                    $output->writeln(sprintf('<error>ERROR: </error>Unable to find watch list with the specified name `%s`', $identificator));
                    exit(1);
                }
            } else {
                $output->writeln(sprintf('<error>ERROR: </error> %s', $e->getMessage()));
                exit(1);
            }
        }

        if (isset($date) && $input->getOption('delete')) {
            $this->action = false;
            $this->deleteDate = $date;
        }

        if ($this->watchlist) {
            $this->action = true;
        }

        if (null === $this->action) {
            $output->writeln('<error>ERROR: </error>Command action is undefined, exiting...');
            exit(1);
        }

        try {
            if ($input->getOption('date')) {
                $date = new \DateTime($input->getOption('date'));
            } else {
                $date = new \DateTime();
            }
            // nearest working day in past
            $this->tradingCalendar->getInnerIterator()->setStartDate($date)->setDirection(-1);
            $this->tradingCalendar->getInnerIterator()->rewind();
            while (false === $this->tradingCalendar->accept()) {
                $this->tradingCalendar->next();
            }
            $this->studyDate = $this->tradingCalendar->getInnerIterator()->current();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Failed to parse time string')) {
                $output->writeln('<error>ERROR: </error> Invalid date specified for the --date option.');
                exit(1);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->action) {
            $study  = $this->em->getRepository(Study::class)->findOneBy(['date' => $this->studyDate, 'name' => $this->name]);

            if ($study) {
                $output->writeln(sprintf('Study for date %s with name %s already exists. Exiting...', $this->studyDate->format('Y-m-d'), $this->name));
                exit(2);
            }

            $sectorWatchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => $input->getArgument('spdr_sectors')]);
            if (!$sectorWatchlist) {
                $output->writeln(sprintf('<error>ERROR: </error>Could not find spdr sectors watchlist named `%s`', $input->getArgument('spdr_sectors')));
                exit(2);
            }

            $output->writeln(sprintf('Will create new study named `%s` for date `%s`', $this->name, $this->studyDate->format('Y-m-d')));

            $this->studyBuilder->initStudy($this->studyDate, $this->name);

            $this->tradingCalendar->getInnerIterator()->setStartDate($this->studyDate)->setDirection(-1);
            $this->tradingCalendar->getInnerIterator()->rewind();
            $this->tradingCalendar->next();
            $prevT = $this->tradingCalendar->getInnerIterator()->current();

            $pastStudy = $this->em->getRepository(Study::class)->findOneBy(['date' => $prevT, 'name' => $this->name]);

            $output->write('Calculating Market Breadth...');
            $startTimestamp = time();
            $this->studyBuilder->calculateMarketBreadth($this->watchlist);
            $endTimestamp = time();
            $output->write(sprintf('done %d s', $endTimestamp - $startTimestamp), true);
            $this->studyBuilder->calculateScoreDelta($pastStudy);
            $output->writeln('Calculated Score Delta');
            if ($pastStudy) {
                $output->write('Calculating Inside Bar Breakouts/Breakdowns...');
                $startTimestamp = time();
                $this->studyBuilder->figureInsideBarBOBD($pastStudy, $this->studyDate);
                $endTimestamp = time();
                $output->write(sprintf('done %d s', $endTimestamp - $startTimestamp), true);
                $output->write('Calculating Actionable Symbols List Breakouts/Breakdowns...');
                $startTimestamp = time();
                $this->studyBuilder->figureASBOBD($pastStudy, $this->studyDate);
                $endTimestamp = time();
                $output->write(sprintf('done %d s', $endTimestamp - $startTimestamp), true);
            } else {
                $output->writeln(sprintf('Could not calculate Inside Bar Breakouts/Breakdowns, because past study for date = %s was not found', $prevT->format('Y-m-d')));
                $output->writeln(sprintf('Could not calculate Actionable Symbols Breakouts/Breakdowns, because past study for date = %s was not found', $prevT->format('Y-m-d')));
            }
            $output->write('Creating Actionable Symbols Watch List...');
            $startTimestamp = time();
            $this->studyBuilder->buildActionableSymbolsWatchlist();
            $endTimestamp = time();
            $output->write(sprintf('done %d s', $endTimestamp - $startTimestamp), true);
            if ($input->getOption('full')) {
                try {
                    $output->write('Creating Market Score Table for Rolling Period...');
                    $startTimestamp = time();
                    $this->studyBuilder->buildMarketScoreTableForRollingPeriod(self::ROLLING_PERIOD);
                    $endTimestamp = time();
                    $output->write(sprintf('done %d s', $endTimestamp - $startTimestamp), true);
                    $output->write('Creating Market Score Table for Month to Date...');
                    $startTimestamp = time();
                    $this->studyBuilder->buildMarketScoreTableForMTD();
                    $endTimestamp = time();
                    $output->write(sprintf('done %d s', $endTimestamp - $startTimestamp), true);
                }  catch (StudyException $e) {
                    $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
                    exit(2);
                } catch (PriceHistoryException $e) {
                } catch (BadDataException $e) {
                } catch (OutOfBoundsException $e) {
                }
            }

            $output->write('Building Sector Table...');
            $startTimestamp = time();
            $this->studyBuilder->buildSectorTable($sectorWatchlist, $this->studyDate);
            $endTimestamp = time();
            $output->write(sprintf('done %d s', $endTimestamp - $startTimestamp), true);

            $this->em->persist($this->studyBuilder->getStudy());
            $this->em->flush();
            $output->writeln('Study saved');
        } else {
            $study  = $this->em->getRepository(Study::class)->findOneBy(['date' => $this->deleteDate, 'name' => $this->name]);

            if ($study) {
                $output->writeln(sprintf('Will delete existing study named `%s` for date `%s`. Associated watch lists will be deleted also.',
                                         $this->name, $this->deleteDate->format('Y-m-d')));
                $studyWatchlists = $study->getWatchlists();
                if (!$studyWatchlists->isEmpty()) {
                    foreach ($studyWatchlists as $watchlist) {
                        $output->writeln(sprintf('Deleting watchlist %s...', $watchlist->getName()));
                        $this->em->remove($watchlist);
                    }
                }
                $this->em->remove($study);
                $this->em->flush();
                $output->writeln('Study deleted.');
            } else {
                $output->writeln(sprintf('Could not find study for date %s with name %s to delete. Nothing to do.', $this->deleteDate->format('Y-m-d'), $this->name));
                exit(2);
            }
        }
    }
}