<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\UtilityServices;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOHLCV extends Command
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var App\Service\Utilities
     */
    protected $utilities;

    public function __construct(
      \App\Service\UtilityServices $utilities,
      \Symfony\Bridge\Doctrine\RegistryInterface $doctrine
    ) {
        $this->em = $doctrine;
        $this->utilities = $utilities;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('price:import');

        $this->setDescription(
          'Brief description. This will be output in command summary when you use bin/console [list].'
        );

        $this->setHelp(
          <<<'EOT'
Detailed help with possible several paragraphs.
EOT
        );

        $this->addUsage('omit bin/console area::mycommand, include args and options');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        //TO DO: Initialize content goes here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo 'Hello';
    }
}