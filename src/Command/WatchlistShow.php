<?php

/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\ExpressionHandler\OHLCV\Calculator;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Watchlist;

class WatchlistShow extends Command
{
    protected static $defaultName = 'th:watchlist:show';
    protected $em;
    protected $watchlist;
    protected $calculator;
    private $logger;


    public function __construct(
        RegistryInterface $doctrine,
        LoggerInterface $logger,
        Calculator $calculator
    ) {
        $this->em = $doctrine->getManager();
        $this->logger = $logger;
        $this->calculator = $calculator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Lists instruments and expressions. Recalculates expressions and saves results');

        $this->setHelp(
            "Lists instruments and optionally results of calculated expressions associated with a watchlist as a comma-separated list. Calculated expressions will always be output if saved in database in the watchlist even if the --calc option is absent. Caveat is that the saved expressions for the watchlist and expressions pre-calculated (stored in database) sometimes may not match. This may happen if you update expressions, but do not recalculate anything, keeping old calculations. In this case, no values will be displayed and you would need to use the --calc option to update the calcs. If you set the --calc option, all expressions will be recalculated (but not saved) and output to screen."
        );

        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the Watchlist')
            ->addOption('calc', 'c', InputOption::VALUE_REQUIRED, 'Calculate values of associated expressions using date')
            ->addOption('save', 's', InputOption::VALUE_NONE, 'Saves recalculated results')
        ;

        $this->addUsage('[--calc=DATE [-s]] watchlist_name');
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $name = $input->getArgument('name');
            $this->watchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => $name]);

            if (!$this->watchlist) {
                throw new Exception(sprintf('Watchlist `%s` does not exist', $name));
            }

            if ($input->getOption('calc')) {
                $date = new DateTime($input->getOption('calc'));
                $this->watchlist->update($this->calculator, $date);

                if ($input->getOption('save')) {
                    $this->em->persist($this->watchlist);
                    $this->em->flush();
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->logger->info(sprintf('Command %s is starting', $this->getName()));

        $header = ['id', 'instrument'];
        try {
            $expressions = $this->watchlist->getExpressions();
            $storedExprOrder = [];
            if (!$expressions->isEmpty()) {
                foreach ($expressions as $expression) {
                    $header[] = $expression->getName();
                    $storedExprOrder[] = $expression->getName();
                }
            }

            $output->writeln(implode(',', $header));

            foreach ($this->watchlist->getInstruments() as $instrument) {
                $line = [];
                $symbol = $instrument->getSymbol();
                $line[] = $instrument->getId();
                $line[] = $symbol;
                $calculatedFormulas = $this->watchlist->getCalculatedFormulas();
                if (!empty($calculatedFormulas) && !empty($storedExprOrder)) {
                    $calcExprOrder = array_keys($calculatedFormulas[$symbol]);
                    if ($storedExprOrder === $calcExprOrder) {
                        $line = array_merge($line, $calculatedFormulas[$symbol]);
                    } else {
                        $line = array_merge($line, array_fill(0, $expressions->count(), ''));
                    }
                } else {
                    $line = array_merge($line, array_fill(0, $expressions->count(), ''));
                }

                $output->writeln(implode(',', $line));
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return 1;
        }

        $this->logger->info(sprintf('Command %s finished', $this->getName()));

        return 0;
    }
}
