<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class UtilityServices
{
    /**
     * @var Psr/Log/LoggerInterface
     */
    protected $logger;

    /**
     * On screen chatter level
     * @var int
     */
    protected $chatter;

    /**
     * @var integer
     */
    protected $runningTime;

    public function __construct(
      LoggerInterface $logger,
      $chatter
    )
    {
        $this->logger = $logger;
        $this->chatter = $chatter;
    }

    /**
     * @param Symfony\Component\Console\Output\OutputInterface $output
     * @param string $logMsg
     * @param string $screenMsg
     */
    public function logAndSay($output, $logMsg, $screenMsg) {
        $this->logger->log(LogLevel::DEBUG, $logMsg);
        if ($output->getVerbosity() >= $this->chatter ) {
            $output->writeln($screenMsg);
        }
    }

    /**
     * @param Symfony\Component\Console\Command\Command
     * @param Symfony\Component\Console\Output\OutputInterface
     */
    public function pronounceStart($command, $output)
    {
        $this->runningTime = time();
        $logMsg = sprintf('%1$s %3$s starting `%4$s` %2$s', str_repeat('>', 5), str_repeat('<', 5), date('c'), $command->getName());
        $screenMsg = $logMsg;
        $this->logAndSay($output, $logMsg, $screenMsg);
    }

    /**
     * @param Symfony\Component\Console\Command\Command
     * @param Symfony\Component\Console\Output\OutputInterface
     */
    public function pronounceEnd($command, $output)
    {
        $this->runningTime = time() - $this->runningTime;
        $logMsg = sprintf('%1$s %3$s ended `%4$s` %2$s', str_repeat('<', 5), str_repeat('>', 5), date('c'),
                          $command->getName()) . PHP_EOL;
        $durationHours = round($this->runningTime/ 3600, 0);
        $durationMinutes = round(($this->runningTime - $durationHours*3600) / 60, 0);
        $durationSeconds = $this->runningTime - $durationHours*3600 - $durationMinutes*60;

        $logMsg .= sprintf('%1$s Running time: %2$s h %3$s m %4$1.4f s %1$s', str_repeat('*', 5), $durationHours,
                           $durationMinutes, $durationSeconds);
        $screenMsg = $logMsg;

        $this->logAndSay($output, $logMsg, $screenMsg);
    }
}