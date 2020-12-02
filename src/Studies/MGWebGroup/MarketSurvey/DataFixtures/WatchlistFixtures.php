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

class WatchlistFixtures extends Fixture implements FixtureGroupInterface
{
    const PATH = 'src/Studies/MGWebGroup/MarketSurvey/DataFixtures/watchlist_test.csv';

    const WATCHLIST_NAME = 'watchlist_test';

    private $manager;

    /**
     * These are the only expressions used to formulate Actionable Symbols lists
     * @var string
     */
    private $expressions = 'Pos on D:Neg on D:V';

    public static function getGroups(): array
    {
        return ['mgweb_watchlist'];
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $output = new ConsoleOutput();

        try {
            $csvReader = Reader::createFromPath(self::PATH);
            $expressionRepository = $this->manager->getRepository(Expression::class);
            $expressionCollection = new ArrayCollection();
            foreach (explode(':', $this->expressions) as $exprName) {
                $expression = $expressionRepository->findOneBy(['name' => $exprName]);
                if ($expression) {
                    $expressionCollection->add($expression);
                } else {
                    throw new \Exception($exprName, $code = 1);
                }
            }

            $csvReader->setHeaderOffset(0);
            $instrumentRepository = $this->manager->getRepository(Instrument::class);
            $instrumentsCollection = new ArrayCollection();
            $importedInstruments = 0;
            foreach ($csvReader->getRecords() as $record) {
                $symbol = $record['Symbol'];
                $instrument = $instrumentRepository->findOneBy(['symbol' => $symbol]);
                if ($instrument) {
                    $instrumentsCollection->add($instrument);
                    $importedInstruments++;
                } else {
                    throw new \Exception($symbol, $code = 2);
                }
            }

            $watchlist = WatchlistRepository::createWatchlist(self::WATCHLIST_NAME, null, $expressionCollection->toArray(), $instrumentsCollection->toArray());

            $this->manager->persist($watchlist);
            $this->manager->flush();

            $output->writeln(sprintf('Imported <info>%d</info> instruments', $importedInstruments));
        } catch (CSVException $e) {
            $output->writeln('<error>ERROR: </error> ' . $e->getMessage());
            exit(1);
        } catch (\Exception $e) {
            switch ($e->getCode()) {
                case 1:
                    $output->writeln(sprintf('<error>ERROR: </error> Expression `%s` is missing from the system',
            $e->getMessage()));
                    exit(1);
                    break;
                case 2:
                    $output->writeln(sprintf('<error>ERROR: </error> Symbol %s was not imported', $e->getMessage()));
                    exit(2);
                default:
                    $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            }
        }
    }
}
