<?php

/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use Exception;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
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
use App\Entity\Instrument;

class ExpressionImportCommand extends Command
{
    protected static $defaultName = 'th:expression:import';

    protected $em;
    protected $csvReader;
    protected $expression;
    protected $validator;
    protected $calculator;
    protected $validationOptions;
    private $logger;

    public function __construct(
        RegistryInterface $doctrine,
        LoggerInterface $logger,
        Calculator $calculator,
        ValidatorInterface $validator
    ) {
        $this->em = $doctrine->getManager();
        $this->logger = $logger;
        $this->calculator = $calculator;
        $this->validator = $validator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Imports a list of expressions from a csv file or one expression.');

        $this->setHelp(
            <<<EOT
In the second form the csv file must have the following columns (heading included): Interval, Name, Formula, 
Criterion, Description.
Criterion field must have a comparison operator and a value separated by space, like '> 0'. Same convention applies 
to criterion specified in the third form.
For the list of available comparison operators see Doctrine\Common\Collections\Expr\Comparison.
Shell splits input into arguments using spaces. Therefore you must enclose entire argument in single quotes to avoid 
splitting by shell as well as to avoid shell expansion. 
Example: th:expression:import daily my_formula 'Close(0)-Close(1)' '> 0'.
All formulas get validated against price history for an instrument before import. If you don's specify --symbol 
option, a random symbol will be picked from imported instruments, which may or may not have price history, thus 
failing your import for the latter case. If you are not sure that all of your imported instruments have price history
 with known time frames, select a symbol that does, like so: --symbol=LIN.
EOT
        );

        $this->addUsage('--file data/studies/formulas.csv');
        $this->addUsage('daily|weekly|monthly \'name\' \'formula\' \'criterion\'');

        $this->addOption('file', null, InputOption::VALUE_REQUIRED, 'csv file to read expressions from');
        //TODO: replace this option to 'test'
        $this->addOption('symbol', 's', InputOption::VALUE_REQUIRED, 'instrument to test the expression on');

        $this->addArgument('interval', InputArgument::OPTIONAL, 'Time Interval to apply the expression for')
          ->addArgument('name', InputArgument::OPTIONAL, 'Expression name')
          ->addArgument('formula', InputArgument::OPTIONAL, 'Formula to evaluate')
          ->addArgument('criterion', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Criterion for the formula')
        ;
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            if ($input->getOption('symbol')) {
                $instrument = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => $input->getOption('symbol')]);
                if ($instrument) {
                    $this->validationOptions = ['payload' => $instrument];
                } else {
                    throw new Exception(sprintf('Specified instrument `%s` in --symbol option was not found', $input->getOption('symbol')));
                }
            }
            if ($input->getOption('file')) {
                $this->csvReader = Reader::createFromPath($input->getOption('file'));
                $this->csvReader->setHeaderOffset(0);
            } else {
                if ($input->getArgument('interval')) {
                    $interval = $input->getArgument('interval');
                } else {
                    throw new Exception(sprintf('Interval must be specified.'));
                }

                if ($input->getArgument('name')) {
                    $name = $input->getArgument('name');
                } else {
                    throw new Exception(sprintf('Name for the expression must be specified.'));
                }

                if ($input->getArgument('formula')) {
                    $formula = $input->getArgument('formula');
                } else {
                    throw new Exception(sprintf('Formula for the expression must be specified.'));
                }

                if (is_array($input->getArgument('criterion'))) {
                    $criterionArray = $input->getArgument('criterion');
                    $criterion = explode(' ', array_shift($criterionArray));
                } else {
                    $criterion = null;
                }

                $this->expression = ExpressionRepository::createExpression($interval, $name, $formula, $criterion);
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

        $counter = 0;
        //TODO: Check if expression name already exists!
        if ($this->csvReader) {
            $records = $this->csvReader->getRecords();
            foreach ($records as $value) {
                $this->expression = ExpressionRepository::createExpression(
                    $value['Interval'],
                    $value['Name'],
                    $value['Formula'],
                    explode(' ', $value['Criterion']),
                    $value['Description']
                );
                if ($this->isValidExpression($this->expression, $this->validationOptions)) {
                    $this->em->persist($this->expression);
                    $counter++;
                }
            }
        } else {
            if ($this->isValidExpression($this->expression, $this->validationOptions)) {
                $this->em->persist($this->expression);
                $counter++;
            }
        }

        $this->em->flush();
        $this->logger->notice(sprintf('Persisted %d expressions', $counter));
        $this->expression = null;

        $this->logger->info(sprintf('Command %s finished', $this->getName()));

        return 0;
    }

    protected function isValidExpression($expression, $options = null): bool
    {
        $violations = $this->validator->validate($expression, new ExpressionConstraint($options));
        if ($violations->count() > 0) {
            foreach ($violations as $violation) {
                $this->logger->error(sprintf(
                    '<error>ERROR:</error> %s: %s',
                    $expression->getName(),
                    $violation->getMessage()
                ));
            }
            return false;
        }
        return true;
    }
}
