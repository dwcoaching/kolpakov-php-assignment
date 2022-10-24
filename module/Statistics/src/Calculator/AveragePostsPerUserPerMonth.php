<?php

declare(strict_types = 1);

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

class AveragePostsPerUserPerMonth extends AbstractCalculator
{
    protected const UNITS = 'posts';

    /**
     * @var int
     */
    private $postCount = 0;

    /**
     * @var Array
     */
    private $authorIds = [];

    /**
     * @inheritDoc
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $this->postCount++;
        $this->authorIds[] = $postTo->getAuthorId();
    }

    /**
     * @inheritDoc
     */
    protected function doCalculate(): StatisticsTo
    {
        $uniqueAuthorIds = array_unique($this->authorIds);
        $authorCount = count($uniqueAuthorIds);

        $value = $this->postCount > 0
            ? $this->postCount / $authorCount
            : 0;

        return (new StatisticsTo())->setValue(round($value, 2));
    }
}
