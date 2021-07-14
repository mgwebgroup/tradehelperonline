<?php

/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Expression;
use App\Entity\Instrument;
use App\Entity\Watchlist;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WatchlistAdd extends Command
{
    protected static $defaultName = 'th:watchlist:add';
    protected $em;
    private $logger;

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
        $this->setDescription('Adds or deletes an instrument or an expression from a watchlist');

        $this->setHelp(
            "Adds or deletes one instrument to/from a watchlist, and/or one expression to/from watchlist. Instrument or expression must already be imported into the system. To add/delete an expression only, use a known instrument in the system, i.e. 'SPY' - it will be used as a placeholder argument."
        );

        $this
          ->addArgument('symbol', InputArgument::REQUIRED, 'Symbol to add or delete')
          ->addArgument('name', InputArgument::REQUIRED, 'Name of the Watchlist')
          ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete flag to delete a symbol')
          ->addOption('addExpr', null, InputOption::VALUE_REQUIRED, 'Expression to Add')
          ->addOption('remExpr', null, InputOption::VALUE_REQUIRED, 'Expression to Remove')
        ;

        $this
          ->addUsage('TST my_watchlist')
          ->addUsage('-d TST my_watchlist')
          ->addUsage("--addExpr='Pos on D' --remExpr='Neg on D' SPY my_watchlist")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info(sprintf('Command %s is starting', $this->getName()));

        $name = $input->getArgument('name');

        /** @var Watchlist $watchlist */
        $watchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => $name]);

        try {
            if (!$watchlist) {
                throw new Exception(sprintf('Watch list `%s` was not was not found in the system.', $name));
            }

            $symbol = strtoupper($input->getArgument('symbol'));
            $instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol($symbol);

            if (!$instrument) {
                throw new Exception(sprintf('Instrument `%s` was not found in the system.', $symbol));
            }

            $message = null;

            if ($input->getOption('delete')) {
                $watchlist->removeInstrument($instrument);
                $message = sprintf(
                    'Instrument `%s` was removed from watchlist `%s`',
                    $instrument->getSymbol(),
                    $watchlist->getName()
                );
            } elseif (!$watchlist->getInstruments()->contains($instrument)) {
                $watchlist->addInstrument($instrument);
                $message = sprintf(
                    'Instrument `%s` was added to watchlist `%s`.',
                    $instrument->getSymbol(),
                    $watchlist->getName()
                );
            }

            $addExprName = $input->getOption('addExpr');
            if ($addExprName) {
                $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $addExprName]);
                if ($expression) {
                    $watchlist->addExpression($expression);
                    $message .= sprintf(' Added expression `%s`', $expression->getName());
                } else {
                    throw new Exception(sprintf('Expression `%s` was not found in the system.', $addExprName));
                }
            }

            $remExprName = $input->getOption('remExpr');
            if ($remExprName) {
                $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $remExprName]);
                if ($expression) {
                    $watchlist->removeExpression($expression);
                    $message .= sprintf(' Removed expression `%s`', $expression->getName());
                } else {
                    throw new Exception(sprintf('Expression `%s` was not found in the system.', $addExprName));
                }
            }

            $this->em->persist($watchlist);
            $this->em->flush();

            $this->logger->notice($message);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return 1;
        }

        $this->logger->info(sprintf('Command %s finished', $this->getName()));

        return 0;
    }
}
