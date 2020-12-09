<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Studies\MGWebGroup\MarketSurvey\DataFixtures;

use App\Entity\Expression;
use App\Entity\Instrument;
use App\Repository\WatchlistRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use League\Csv\Reader;
use League\Csv\Exception as CSVException;
use Symfony\Component\Console\Output\ConsoleOutput;

class SectorsFixtures extends Fixture implements FixtureGroupInterface
{
    const SECTORS = ['XLC','XLY','XLP','XLE','XLF','XLV','XLI','XLB','XLRE','XLK','XLU'];

    const WATCHLIST_NAME = 'sectors_test';

    private $manager;

    /**
     * These are the only expressions used to formulate Actionable Symbols lists
     * @var string
     */
    private $expressions = 'P:delta P:delta P(5)';

    public static function getGroups(): array
    {
        return ['mgweb_sectors'];
    }

    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput();

        $expressions = [];
        $instruments = [];
        try {
            $exprNames = explode(':', $this->expressions);
            foreach ($exprNames as $exprName) {
                $expression = $manager->getRepository(Expression::class)->findOneBy(['name' => $exprName]);
                if (!$expression) {
                    throw new \Exception($exprName, $code = 1);
                }
                $expressions[] = $expression;
            }

            foreach (self::SECTORS as $symbol) {
                $instrument = $manager->getRepository(Instrument::class)->findOneBy(['symbol' => $symbol]);
                if (!$instrument) {
                    throw new \Exception($symbol, $code = 2);
                }
                $instruments[] = $instrument;
            }

            $watchlist = WatchlistRepository::createWatchlist(self::WATCHLIST_NAME, null, $expressions, $instruments);

            $manager->persist($watchlist);
            $manager->flush();

            $output->writeln(sprintf('Saved watchlist <info>%s</info> with <info>%d</info> instruments',
                                     self::WATCHLIST_NAME, count($instruments)));

        } catch (\Exception $e) {
            switch ($e->getCode()) {
                case 1:
                    $output->writeln(
                      sprintf('<error>ERROR: </error> Expression `%s` is missing from the system', $e->getMessage())
                    );
                    exit(1);
                    break;
                case 2:
                    $output->writeln(sprintf('<error>ERROR: </error> Symbol %s was not imported', $e->getMessage()));
                    exit(1);
                    break;
            }
        }
    }
}
