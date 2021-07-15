<?php

/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\WatchlistRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;
use App\Entity\Watchlist;
use App\Entity\Instrument;
use App\Entity\Expression;

class WatchlistImportCommand extends Command
{
    protected static $defaultName = 'th:watchlist:import';

    protected $em;
    protected $csvReader;
    protected $watchlist;
    private $logger;

    /**
     * @array [App\Entity\Expression]
     */
    protected $expressions = [];

    public function __construct(
        RegistryInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->em = $doctrine->getManager();
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Imports a list of symbols from a csv file into a watch list. Also deletes watch list.');

        $this->setHelp(
            "Takes a path/to/csv/file and imports symbols in column 'Symbol' into a watch list under NAME. All symbols must be already imported into the system via `th:instruments:import` command. If a watchlist is missing, it will be created. Title of column 'Symbol' is case sensitive, symbol names are not. Optional list of expressions must be column-separated. Only instruments and/or expressions that do not already exist in the watchlist will be added."
        );

        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the Watchlist')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Csv file with symbols')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Flag to delete a watch list')
            ->addOption('expr', null, InputOption::VALUE_REQUIRED, 'Column delimited list of expressions')
        ;

        $this->addUsage('-d -- watchlist_name');
        $this->addUsage("--file=data/watchlist.csv [--expr='expr1:expr2:...:exprN'] watchlist_name");
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $name = $input->getArgument('name');
            $this->watchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => $name]);

            if (false === $input->getOption('delete')) {
                if ($input->getOption('file')) {
                    $this->csvReader = Reader::createFromPath($input->getOption('file'));
                    $this->csvReader->setHeaderOffset(0);

                    if (!$this->watchlist) {
                        $this->watchlist = WatchlistRepository::createWatchlist($name);
                    }

                    if ($input->getOption('expr')) {
                        $exprNames = explode(':', $input->getOption('expr'));

                        foreach ($exprNames as $exprName) {
                            $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $exprName]);
                            if ($expression) {
                                $this->expressions[] = $expression;
                            } else {
                                throw new Exception(
                                    sprintf(
                                        'Could not find expression `%s` in the system. Please import it before adding to watch list.',
                                        $exprName
                                    )
                                );
                            }
                        }
                    }
                } else {
                    throw new Exception('Please specify path/file to the watchlist to import (--file option)');
                }
            } else {
                if (!$this->watchlist) {
                    $this->logger->error(sprintf('Watch list named `%s` does not exist', $name));
                    exit(2);
                }
            }
        } catch (Exception $e) {
            $logMsg = $e->getMessage();
            $this->logger->error($logMsg);
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->logger->info(sprintf('Command %s is starting', $this->getName()));

        try {
            if ($input->getOption('delete')) {
                $this->em->remove($this->watchlist);
                $message = sprintf(
                    'Removed watch list `%s` (id=%d)',
                    $this->watchlist->getName(),
                    $this->watchlist->getId(
                    )
                );
            } else {
                $records = $this->csvReader->getRecords();
                $addedInstrument = 0;
                foreach ($records as $value) {
                    $instrument = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => strtoupper($value['Symbol'])]);
                    if ($instrument) {
                        if (false === $this->watchlist->getInstruments()->contains($instrument)) {
                            $this->watchlist->addInstrument($instrument);
                            $addedInstrument++;
                        }
                    } else {
                        throw new Exception(sprintf('Instrument `%s` was not added to the watchlist because it is missing from the system', $value['Symbol']));
                    }
                }

                $addedExpressions = 0;
                foreach ($this->expressions as $expression) {
                    if (false === $this->watchlist->getExpressions()->contains($expression)) {
                        $this->watchlist->addExpression($expression);
                        $addedExpressions++;
                    }
                }

                $this->em->persist($this->watchlist);

                $message = sprintf(
                    'Added %d instruments and %d expressions to %s watch list `%s`',
                    $addedInstrument,
                    $addedExpressions,
                    $this->watchlist->getId() ? 'existing' : 'new',
                    $this->watchlist->getName()
                );
            }

            $this->em->flush();

            $output->writeln($message);
        } catch (Exception $e) {
            $logMsg = $e->getMessage();
            $this->logger->error($logMsg);
            exit(1);
        }

        $this->logger->info(sprintf('Command %s finished', $this->getName()));

        return 0;
    }
}
