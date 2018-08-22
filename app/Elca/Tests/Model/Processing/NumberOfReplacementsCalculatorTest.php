<?php
/**
 * Created by PhpStorm.
 * User: pronoia
 * Date: 22.12.17
 * Time: 06:16
 */

namespace Elca\Tests\Model\Processing;

use Elca\Model\ProcessConfig\UsefulLife;
use Elca\Model\Processing\NumberOfReplacementsCalculator;
use PHPUnit\Framework\TestCase;

class NumberOfReplacementsCalculatorTest extends TestCase
{
    /**
     * @var NumberOfReplacementsCalculator
     */
    private $calculator;

    protected function setUp()
    {
        $this->calculator = new NumberOfReplacementsCalculator(50);
    }

    /**
     * @dataProvider numberReplacementsProvider
     */
    public function test_compute(bool $isExtant, int $delayInYears, int $inYears, int $numberReplacements)
    {
        $result = $this->calculator->compute(
            new UsefulLife($inYears, $delayInYears),
            $isExtant
        );

        $this->assertSame($numberReplacements, $result);
    }

    public function numberReplacementsProvider()
    {
        return [
            // isExtant, lifeTimeDelay, componentLifeTime, resulting numReplacements
            'noReplacements' => [false, 0, 50, 0],
            'oneReplacement' => [false, 0, 40, 1],
            'lastReplIsIgnored1' => [false, 0, 25, 1],
            'twoReplacements' => [false, 0, 20, 2],
            'lastReplIsIgnored2' => [false, 0, 1, 49],
            'lifeTimeDelayIsIgnored' => [false, 10, 1, 49],

            'extantNoReplacements' => [true, 0, 50, 0],
            'extantDelayedNoReplacements' => [true, 10, 50, 0],
            'extantTwoReplacements' => [true, 0, 40, 2],
            'extantLastReplIsIgnored' => [true, 10, 40, 1], // edge case
            'extantDelayedTwoReplacements' => [true, 5, 40, 2],
            'extantDelayed1' => [true, 0, 10, 5],
            'extantDelayed2' => [true, 5, 10, 5],
        ];
    }
}
