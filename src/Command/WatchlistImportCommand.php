<?php

namespace App\Command;

use App\Repository\WatchlistRepository;
use App\Service\UtilityServices;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;
use App\Entity\Watchlist;
use App\Entity\Instrument;
use App\Entity\Expression;


class WatchlistImportCommand extends Command
{
    protected static $defaultName = 'th:watchlist:import';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var App\Service\Utilities
     */
    protected $utilities;

    /**
     * @var League/Csv/Reader
     */
    protected $csvReader;

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
        $this->setDescription('Imports a list of symbols from a csv file into a watchlist');

        $this->setHelp(
          <<<EOT
Takes a csv file and imports all symbols into a watchlist under NAME. All symbols must be already imported into the 
system via `th:instruments:import` command. If a watchlist is missing, it will be created. If symbol already exists in a 
watchlist, it will be overwritten with new expressions from the csv file. 
The watchlist file must have the following columns: Symbol, Expression Names.
Note the case on the columns and use of space in `Expression Names`. It must match exactly as shown here.
Column `expression_names` can be null or can be a column-delimited list of expression_names to evaluate against 
the symbol. If expressions are already associated in a given watchlist with a symbol, new expressions will be added, 
and old ones will remain.
EOT
        );

        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Csv file with symbols')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the Watchlist')
        ;
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->csvReader = Reader::createFromPath($input->getArgument('file'), 'r');
            $this->csvReader->setHeaderOffset(0);
        } catch(\Exception $e) {
            $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->utilities->pronounceStart($this, $output);

        $name = $input->getArgument('name');

        $watchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => $name]);
        if (!$watchlist) {
            $watchlist = WatchlistRepository::createWatchlist($name);
        }

        $records = $this->csvReader->getRecords();
        foreach ($records as $value) {
            // find instrument
            $instrument = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => $value['Symbol']]);
            if($instrument) {
                $watchlist->addInstrument($instrument);
                // determine if expressions exist
                if (!empty($value['Expression Names'])) {
                    $exprNames = explode(':', $value['Expression Names']);
                    foreach ($exprNames as $exprName) {
                        $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $exprName]);
                        if ($expression) {
                            $watchlist->addExpression($expression);
                        } else {
                            $output->writeln(sprintf('<error>ERROR: </error>Expression `%s` was not added to the watchlist because it is missing from the system', $exprName));
                        }
                    }
                }
            } else {
                $output->writeln(sprintf('<error>ERROR: </error>Instrument `%s` was not added to the watchlist because it is missing from the system',
                                         $value['symbol']));
            }
        }

        $this->em->persist($watchlist);
        $this->em->flush();

        $this->utilities->pronounceEnd($this, $output);
    }
}
