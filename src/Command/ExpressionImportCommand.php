<?php

namespace App\Command;

use App\Entity\Expression;
use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use App\Exception\PriceHistoryException;
use App\Service\UtilityServices;
use League\Csv\Reader;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\ExpressionHandler\OHLCV\Calculator;

class ExpressionImportCommand extends Command
{
    protected static $defaultName = 'th:expression:import';

    const NAME_MAX_LENGTH = 255;
    const FORMULA_MAX_LENGTH = 65535;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var App\Service\Utilities
     */
    protected $utilities;

    /**
     * @var Symfony\Component\Filesystem\Filesystem
     */
    protected $fileSystem;

    /**
     * @var League/Csv/Reader
     */
    protected $csvReader;

    /**
     * @var \DateInterval
     */
    protected $interval;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $criterion;

    /**
     * @var App\Service\ExpressionHandler\OHLCV\Calculator
     */
    protected $calculator;


    public function __construct(
      RegistryInterface $doctrine,
      UtilityServices $utilities,
      Filesystem $fileSystem,
      Calculator $calculator
    ) {
        $this->em = $doctrine->getManager();
        $this->utilities = $utilities;
        $this->fileSystem = $fileSystem;
        $this->calculator = $calculator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Imports a list of expressions from a csv file or one expression.');

        $this->setHelp(
          <<<EOT
In the first form the csv file must have the following columns (heading included): interval, name, formula, 
criterion, description.
Criterion field must have a comparison operator and a value separated by space, like '> 0'. Same convention applies 
to criterion specified in the second form.
For the list of available comparison operators see Doctrine\Common\Collections\Expr.
Shell splits input into arguments using spaces. Therefore you must enclose entire argument in single quotes to avoid 
splitting by shell as well as to avoid shell expansion. 
Example: th:expression:import daily my_formula 'Close(0)-Close(1)' '> 0'.
If you get an error on invalid formula when using CLI, you may want to try again, as a random symbol chosen for 
evaluation may not have valid historical data.
EOT

        );

        $this->addUsage('--file data/studies/formulas.csv');
        $this->addUsage('daily|weekly|monthly \'name\' \'formula\' \'criterion\'');

        $this->addOption('file', null, InputOption::VALUE_REQUIRED, 'csv file to read expressions from');

        $this->addArgument('interval', InputArgument::OPTIONAL, 'Time Interval to apply the expression for')
          ->addArgument('name', InputArgument::OPTIONAL, 'Criterion for the formula')
          ->addArgument('formula', InputArgument::OPTIONAL, 'Formula to evaluate')
          ->addArgument('criterion', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Criterion for the formula')
        ;
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            if ($input->getOption('file')) {
                if (!$this->fileSystem->exists($input->getOption('file'))) {
                    throw new \Exception(sprintf('File with expressions was not found. Looked for `%s`', $input->getOption('file')));
                } else {
                    $this->csvReader = Reader::createFromPath($input->getOption('file'), 'r');
                    $this->csvReader->setHeaderOffset(0);
                }
            } else {
                if ($input->getArgument('interval')) {
                    $interval = $input->getArgument('interval');
                } else {
                    throw new \Exception(sprintf('Interval must be specified.'));
                }

                if ($input->getArgument('name')) {
                    $name = $input->getArgument('name');
                } else {
                    throw new \Exception(sprintf('Name for the expression must be specified.'));
                }

                if ($input->getArgument('formula')) {
                    $formula = $input->getArgument('formula');
                } else {
                    throw new \Exception(sprintf('Formula for the expression must be specified.'));
                }

                if (is_array($input->getArgument('criterion'))) {
                    $criterionArray = $input->getArgument('criterion');
                    $criterion = array_shift($criterionArray);
                } else {
                    $criterion = null;
                }

                $this->validateInput($interval, $name, $formula, $criterion);

            }
        } catch(\Exception $e) {
            $logMsg = $e->getMessage();
            $screenMsg = $logMsg;
            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
            exit(1);
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check if CSV File is open, read formulas from it
        if ($this->csvReader) {
            $records = $this->csvReader->getRecords();
            foreach ($records as $value) {
                $this->validateInput($value['interval'], $value['name'], $value['formula'], $value['criterion']);
                if (!$this->expressionExists($this->name)) {
                    $this->persistExpression($this->interval, $this->name, $this->formula, $this->criterion);
                }
            }
        } else {
            if (!$this->expressionExists($this->name)) {
                $this->persistExpression($this->interval, $this->name, $this->formula, $this->criterion);
            }
        }

        $this->em->flush();

//        $io = new SymfonyStyle($input, $output);
//        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }

    /**
     * This function is used to validate formulas.
     * Evaluates a given formula on a random symbol from stored instruments
     * @param string $formula
     * @param \DateInterval $interval
     * @return bool true on success, false on failure
     * @throws \Exception
     */
    protected function evaluate($formula, $interval)
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        if (empty($list)) {
            throw new \Exception('Could not find any instruments');
        }

        $instrument = $list[array_rand($list)];

        $priceHistory = $this->em->getRepository(History::class)->findBy(
          ['instrument' => $instrument, 'timeinterval' => $interval],
          ['timestamp' => 'desc'],
          1
        );

        $latestRecord = array_shift($priceHistory);
        $date = clone $latestRecord->getTimestamp();

        try {
            $result = $this->calculator->evaluate($formula, [
              'instrument' => $instrument,
              'interval' => $interval,
              'date' => $date
            ]);
        } catch (PriceHistoryException $e) {
            throw new \Exception($e->getMessage());
        }

        if (is_float($result) || is_bool($result)) {
            return true;
        }

        return false;
    }

    protected function persistExpression($interval, $name, $formula, $criterion, $description = null)
    {
        $expression = new Expression();
        $expression->setCreatedAt(new \DateTime())
          ->setTimeinterval($interval)
          ->setName($name)
          ->setFormula($formula)
          ->setCriteria($criterion)
          ->setDescription($description)
        ;
        $this->em->persist($expression);
    }

    protected function expressionExists($name)
    {
        if (!empty($this->em->getRepository(Expression::class)->findOneBy(['name' => $name]))) {
            return true;
        }
        return false;
    }

    protected function validateInput($interval, $name, $formula, $criterion = null)
    {
        // interval
        switch (strtolower($interval)) {
            case 'daily':
                $this->interval = new \DateInterval('P1D');
                break;
            case 'weekly':
                $this->interval = new \DateInterval('P7D');
                break;
            case 'monthly':
                $this->interval = new \DateInterval('P1M');
                break;
            default:
                throw new \Exception(sprintf('Unknown interval `%s`.', $input->getArgument('interval')));
        }

        // name
        if (strlen($name) > self::NAME_MAX_LENGTH ) {
            throw new \Exception(sprintf('Name of expression cannot exceed %d chars', self::NAME_MAX_LENGTH));
        }

        // TODO: check for illegal chars here:
        // ...

        $this->name = $name;

        // formula
        if (strlen($formula) > self::FORMULA_MAX_LENGTH ) {
            throw new \Exception(sprintf('Name of expression cannot exceed %d chars', self::FORMULA_MAX_LENGTH));
        }

        if (!$this->evaluate($formula, $this->interval)) {
            throw new \Exception(sprintf('Supplied formula is not valid.'));
        }

        $this->formula = $formula;

        // criterion
        if ($criterion) {
            $this->criterion = explode(' ', $criterion);
        }

    }
}
