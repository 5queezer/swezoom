<?php declare(strict_types=1);
require __DIR__ . "/../src/TimeFrame.php";

use PHPUnit\Framework\TestCase;
final class TimeFrameTest extends TestCase
{
    public function testCanBeCreatedFromValidTimeFrame(): void
    {
        $begin = '04.03.2022 04:45';
        $end   = '31.05.2023 13:45';
        $tf    = new TimeFrame($begin, $end);
        $this->assertInstanceOf(TimeFrame::class, $tf);
    }
    public function testDailyTimeFrame(): void
    {
        $begin = '01.03.2022 00:00';
        $end   = '31.03.2022 23:59';
        $tf    = new TimeFrame($begin, $end);
        $this->assertEquals($tf->getQueryParameters('n'), 31);
        $this->assertEquals($tf->getQueryParameters('s'), '1');
    }
    public function testHourlyTimeFrame(): void
    {
        $begin = '21.03.2022 00:00';
        $end   = '23.03.2022 23:59';
        $tf    = new TimeFrame($begin, $end);
        $this->assertEquals($tf->getQueryParameters('n'), 24*3);
        $this->assertEquals($tf->getQueryParameters('s'), '60m');
    }
    public function testMinutelyTimeFrame(): void
    {
        $begin = '21.03.2022 16:00';
        $end   = '21.03.2022 17:00';
        $tf    = new TimeFrame($begin, $end);
        $this->assertEquals( 60, $tf->getQueryParameters('n'));
        $this->assertEquals('1m', $tf->getQueryParameters('s'));
    }
    public function testSecondlyTimeFrame(): void
    {
        $begin = '21.03.2022 16:00';
        $end   = '21.03.2022 16:05';
        $tf    = new TimeFrame($begin, $end);
        $this->assertEquals( 60*5, $tf->getQueryParameters('n'));
        $this->assertEquals('1s', $tf->getQueryParameters('s'));
    }
    public function testFindOneAspectMercuryJupiter(): void
    {
        $begin = '01.03.2022 00:00';
        $end   = '31.03.2022 23:59';
        $tf    = new TimeFrame($begin, $end);
        $res = $tf->findAspect('2','5', 0.0);
        $this->assertEquals(new DateTime('2022-03-21 06:06:18 UTC'), $res[0][0]['time']);
    }
    public function testFindMultipleAspectsMercurySun(): void
    {
        $begin = '01.01.2022 00:00';
        $end   = '31.12.2022 23:59';
        $tf    = new TimeFrame($begin, $end);
        $res = $tf->findAspect('2','0', 0.0);
        print_r($res);
        $this->assertEquals(new DateTime('2022-01-23 10:28:07 UTC'), $res[0][0]['time']);
    }
}