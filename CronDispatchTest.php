<?php
require_once('CronDispatch.php');

class CronDispatchTest extends PHPUnit_Framework_TestCase
{
    public function test_cron_minutely()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '* * * * *';

        $prev_ts = $start;
        for ($i = 0; $i < 10; $i++) {
            $time = $prev_ts + 10;
            $expected = $start + ($i + 1) * 60;
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public function test_cron_hourly()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '0 * * * *';

        $prev_ts = $start;
        for ($i = 0; $i < 10; $i++) {
            $time = $prev_ts + 10;
            $expected = $start + ($i + 1) * 3600;
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public function test_cron_hourly5()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '5 * * * *';

        $prev_ts = $start;
        for ($i = 0; $i < 10; $i++) {
            $time = $prev_ts + 10;
            $expected = $start + $i * 3600 + 300;
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public function test_cron_every5minutes()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '*/5 * * * *';

        $prev_ts = $start;
        for ($i = 0; $i < 20; $i++) {
            $time = $prev_ts + 10;
            $expected = $start + ($i + 1) * 300;
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public function test_cron_fizzbuzz()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '*/5,*/3 * * * *';

        $max = 100;
        $times = array();
        for ($i = 1; $i <= $max; $i++) {
            $times[] = $i * 3;
            $times[] = $i * 5;
        }

        sort($times, SORT_NUMERIC);
        $times = array_slice(array_unique($times), 0, $max);

        $prev_ts = $start;
        for ($i = 0; $i < $max; $i++) {
            $time = $prev_ts + 10;
            $expected = $start + $times[$i] * 60;
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public function test_cron_daily()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '0 0 * * *';

        $prev_ts = $start;
        for ($i = 0; $i < 10; $i++) {
            $time = $prev_ts + 10;
            $expected = $start + ($i + 1) * 86400;
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public function test_cron_weekly()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '0 0 * * 3';

        $expected = $start + 86400;
        $prev_ts = $start;
        for ($i = 0; $i < 15; $i++) {
            $time = $prev_ts + 10;
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");

            $expected += 7 * 86400;
        }
    }

    public function test_cron_monthly()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '0 0 1 * *';

        $prev_ts = $start;
        for ($i = 0; $i < 15; $i++) {
            $time = $prev_ts + 10;
            $expected = mktime(0, 0, 0, $i + 2, 1, 2013);
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public function test_cron_yearly()
    {
        $start = mktime(0, 0, 0, 1, 1, 2013);
        $cron = '0 0 2 1 *';

        $prev_ts = $start;
        for ($i = 0; $i < 15; $i++) {
            $time = $prev_ts + 10;
            $expected = mktime(0, 0, 0, 1, 2, 2013 + $i);
            $instance = new CronDispatch();
            $prev_ts = $instance->getNextLaunchTime($cron, $time);

            $this->assertTimestampEquals($expected, $prev_ts, "Incorrect next launch time for cron on iteration $i");
        }
    }

    public static function provider_cron_month_list()
    {
        return array(
            array('2013-01-01 00:00:00', '2013-01-02 00:10:00'),
            array('2013-01-02 00:09:05', '2013-01-02 00:10:00'),
            array('2013-01-02 13:00:00', '2013-03-02 00:10:00'),
            array('2013-01-02 00:00:00', '2013-01-02 00:10:00'),
            array('2013-01-03 00:00:00', '2013-03-02 00:10:00'),
            array('2013-02-02 00:00:00', '2013-03-02 00:10:00'),
            array('2013-03-02 00:00:00', '2013-03-02 00:10:00'),
            array('2013-04-02 00:00:00', '2013-05-02 00:10:00'),
            array('2013-05-02 00:00:00', '2013-05-02 00:10:00'),
            array('2013-06-02 00:00:00', '2013-07-02 00:10:00'),
            array('2013-07-02 00:00:00', '2013-07-02 00:10:00'),
            array('2013-08-02 00:00:00', '2014-01-02 00:10:00'),
            array('2013-09-02 00:00:00', '2014-01-02 00:10:00'),
            array('2013-10-02 00:00:00', '2014-01-02 00:10:00'),
            array('2013-11-02 00:00:00', '2014-01-02 00:10:00'),
            array('2013-12-03 00:00:00', '2014-01-02 00:10:00'),
        );
    }

    /**
     * @dataProvider provider_cron_month_list
     */
    public function test_cron_month_list($current_date, $expected_date)
    {
        $cron = '10 0 2 1/2,7,5,3 *';

        $current_ts = strtotime($current_date);
        $instance = new CronDispatch();
        $actual_ts = $instance->getNextLaunchTime($cron, $current_ts);
        $expected_ts = strtotime($expected_date);
        $this->assertTimestampEquals($expected_ts, $actual_ts, "Incorrect next launch time for cron on iteration");
    }

    public static function provider_cron_simultaneous()
    {
        return array(
            array('2013-03-01 00:00:00', '2013-05-01 00:10:00'),
            array('2013-05-01 00:00:00', '2013-05-01 00:10:00'),
            array('2013-05-01 00:10:00', '2013-05-02 00:10:00'),
            array('2013-05-02 00:10:00', '2013-05-04 00:10:00'),
            array('2013-05-03 00:10:00', '2013-05-04 00:10:00'),
            array('2013-05-04 00:10:00', '2013-05-05 00:10:00'),
            array('2013-05-05 00:10:00', '2013-05-08 00:10:00'),
            array('2013-05-08 00:10:00', '2013-05-09 00:10:00'),
            array('2013-05-09 00:10:00', '2013-05-11 00:10:00'),
            array('2013-05-11 00:10:00', '2013-05-12 00:10:00'),
            array('2013-05-12 00:10:00', '2013-05-15 00:10:00'),
            array('2013-05-20 00:10:00', '2013-05-21 00:10:00'),
        );
    }

    /**
     * @dataProvider provider_cron_simultaneous
     */
    public function test_cron_simultaneous($current_date, $expected_date)
    {
        $cron = '10 0 1-5/3,*/10 5,6 */4,*/3'; // man 5 crontab

        $current_ts = strtotime($current_date);
        $instance = new CronDispatch();
        $actual_ts = $instance->getNextLaunchTime($cron, $current_ts);
        $expected_ts = strtotime($expected_date);
        $this->assertTimestampEquals($expected_ts, $actual_ts, "Incorrect next launch time for cron on iteration");
    }

    protected function assertTimestampEquals($expected, $actual, $message)
    {
        if ($actual != $expected) {
            $this->fail(
                $message
                    . "\nExpected:  " . date('Y-m-d H:i:s', $expected)
                    . "\nActual:    " . date('Y-m-d H:i:s', $actual) . "\n"
            );
        }
    }
}
