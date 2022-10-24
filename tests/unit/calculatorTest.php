<?php

declare(strict_types = 1);

namespace Tests\unit;

use DateTime;
use Traversable;
use Statistics\Enum\StatsEnum;
use PHPUnit\Framework\TestCase;
use Statistics\Dto\StatisticsTo;
use Statistics\Builder\ParamsBuilder;
use SocialPost\Service\SocialPostService;
use Statistics\Service\StatisticsService;
use SocialPost\Hydrator\FictionalPostHydrator;
use Statistics\Calculator\AveragePostsPerUserPerMonth;
use Statistics\Service\Factory\StatisticsServiceFactory;

/**
 * Class CalculatorTest
 *
 * @package Tests\unit
 */
class CalculatorTest extends TestCase
{
    protected static $statsService;
    protected static $stats;

    public static function setUpBeforeClass(): void
    {
        self::$statsService = StatisticsServiceFactory::create();
        self::$stats = self::getStats();
    }

    /**
     * @return Traversable
     */
    protected static function getPosts(): Traversable
    {
        $response = json_decode(
            file_get_contents($_SERVER['DOCUMENT_ROOT'] . 'tests/data/social-posts-response.json'),
            true
        );

        $posts = $response['data']['posts'];

        $fictionalPostHydrator = new FictionalPostHydrator();

        foreach ($posts as $postData) {
            yield $fictionalPostHydrator->hydrate($postData);
        }
    }

    /**
     * @return StatisticsTo
     */
    protected static function getStats(): StatisticsTo
    {
        $date = DateTime::createFromFormat('F, Y', 'August, 2018');
        $paramsTo = ParamsBuilder::reportStatsParams($date);

        return self::$statsService->calculateStats(self::getPosts(), $paramsTo);
    }


    /**
     * @return float|null
     */
    protected function getStatsValueByName($name): float|null
    {
        foreach (self::$stats->getChildren() as $statisticsTo) {
            if ($statisticsTo->getName() == $name) {
                return $statisticsTo->getValue();
            }
        }

        return null;
    }

    /**
     * @return float|null
     */
    protected function getTotalPostsBySplitPeriod($totalPostsPerWeek, $splitPeriod): float|null
    {
        foreach ($totalPostsPerWeek as $weekData) {
            if ($weekData->getSplitPeriod() == $splitPeriod) {
                return $weekData->getValue();
            }
        }

        return null;
    }

    /**
     * @return Array
     */
    protected function getTotalPostsPerWeek(): Array
    {
        foreach (self::$stats->getChildren() as $statisticsTo) {
            if ($statisticsTo->getName() == StatsEnum::TOTAL_POSTS_PER_WEEK) {
                return $statisticsTo->getChildren();
            }
        }

        return [];
    }

    public function testAveragePostLength()
    {
        $averagePostLength = $this->getStatsValueByName(StatsEnum::AVERAGE_POST_LENGTH);
        $this->assertSame(501.5, $averagePostLength);
    }

    public function testMaxPostLength()
    {
        $maxPostLength = $this->getStatsValueByName(StatsEnum::MAX_POST_LENGTH);
        $this->assertSame(638.0, $maxPostLength);
    }

    public function testAveragePostNumberPerUser()
    {
        $averagePostNumberPerUser = $this->getStatsValueByName(StatsEnum::AVERAGE_POST_NUMBER_PER_USER);
        $this->assertSame(1.5, $averagePostNumberPerUser);
    }

    public function testTotalPostsPerWeek()
    {
        $totalPostsPerWeek = $this->getTotalPostsPerWeek();

        $expectedValues = [
            [
                'splitPeriod' => 'Week 32, 2018',
                'value' => 5.0,
            ],
            [
                'splitPeriod' => 'Week 33, 2018',
                'value' => 1.0,
            ]
        ];

        foreach ($expectedValues as $expectedValue) {
            $value = $this->getTotalPostsBySplitPeriod($totalPostsPerWeek, $expectedValue['splitPeriod']);
            $this->assertSame($expectedValue['value'], $value);
        }
    }
}
