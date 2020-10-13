<?php

namespace App\Command;

use App\Service\UtilityServices;
use League\Csv\Reader;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\ExpressionHandler\OHLCV\Calculator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\ExpressionRepository;
use App\Validator\Expression as ExpressionConstraint;

class ExpressionImportCommand extends Command
{
    protected static $defaultName = 'th:expression:import';

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

    /**
     * @var \App\Entity\Expression
     */
    protected $expression;

    /**
     * @var Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @var App\Service\ExpressionHandler\OHLCV\Calculator
     */
    protected $calculator;


    public function __construct(
      RegistryInterface $doctrine,
      UtilityServices $utilities,
      Calculator $calculator,
      ValidatorInterface $validator

    ) {
        $this->em = $doctrine->getManager();
        $this->utilities = $utilities;
        $this->calculator = $calculator;
        $this->validator = $validator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Imports a list of expressions from a csv file or one expression.');

        $this->setHelp(
          <<<EOT
In the second form the csv file must have the following columns (heading included): interval, name, formula, 
criterion, description.
Criterion field must have a comparison operator and a value separated by space, like '> 0'. Same convention applies 
to criterion specified in the third form.
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
                $this->csvReader = Reader::createFromPath($input->getOption('file'), 'r');
                $this->csvReader->setHeaderOffset(0);
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
                    $criterion = explode(' ', array_shift($criterionArray));
                } else {
                    $criterion = null;
                }

                $this->expression = ExpressionRepository::createExpression($interval, $name, $formula, $criterion);
            }
        } catch(\Exception $e) {
            $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->utilities->pronounceStart($this, $output);

        $counter = 0;
        if ($this->csvReader) {
            $records = $this->csvReader->getRecords();
            foreach ($records as $value) {
                $this->expression = ExpressionRepository::createExpression(
                  $value['interval'],
                  $value['name'],
                  $value['formula'],
                  explode(' ', $value['criterion']),
                  $value['description']
                );
                if ($this->isValidExpression($this->expression, $output)) {
                    $this->em->persist($this->expression);
                    $counter++;
                }
            }
        } else {
            if ($this->isValidExpression($this->expression, $output)) {
                $this->em->persist($this->expression);
                $counter++;
            }
        }

        $this->em->flush();
        $output->writeln(sprintf('Persisted %d expressions', $counter));
        $this->expression = null;

        $this->utilities->pronounceEnd($this, $output);
    }

    protected function isValidExpression($expression, $output)
    {
        $violations = $this->validator->validate($expression, new ExpressionConstraint());
        if ($violations->count() > 0) {
            foreach ($violations as $violation) {
                $output->writeln(sprintf('<error>ERROR: </error>%s: %s', $expression->getName(),
                                         $violation->getMessage()));
            }
            return false;
        }
        return true;
    }
}
