<?php

/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2021  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Instrument;
use App\Entity\Watchlist;
use App\Service\UtilityServices;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WatchlistAdd extends Command
{
    protected static $defaultName = 'th:watchlist:add';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var UtilityServices
     */
    protected $utilities;

    public function __construct(
        RegistryInterface $doctrine,
        UtilityServices $utilities
    ) {
        $this->em = $doctrine->getManager();
        $this->utilities = $utilities;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Adds or deletes an instrument from a watchlist');

        $this->setHelp(
            "Adds or deletes one instrument from a watchlist"
        );

        $this
          ->addArgument('symbol', InputArgument::REQUIRED, 'Symbol to add or delete')
          ->addArgument('name', InputArgument::REQUIRED, 'Name of the Watchlist')
          ->addOption('delete', 'd', InputOption::VALUE_NONE, 'symbol')
        ;

        $this->addUsage('TST my_watchlist');
        $this->addUsage('-d TST my_watchlist');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        //TO DO: Initialize content goes here
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->utilities->pronounceStart($this, $output);

        $name = $input->getArgument('name');

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

            if ($input->getOption('delete')) {
                $watchlist->removeInstrument($instrument);
                $message = sprintf(
                    '<comment>Instrument `%s` was removed from watchlist `%s`.</comment>',
                    $instrument->getSymbol(),
                    $watchlist->getName()
                );
            } else {
                $watchlist->addInstrument($instrument);
                $message = sprintf(
                    '<info>Instrument `%s` was added to watchlist `%s`.</info>',
                    $instrument->getSymbol(),
                    $watchlist->getName()
                );
            }

            $this->em->persist($watchlist);
            $this->em->flush();

            $output->writeln($message);
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            return 1;
        }

        $this->utilities->pronounceEnd($this, $output);

        return 0;
    }
}