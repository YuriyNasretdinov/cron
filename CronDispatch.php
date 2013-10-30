<?php
class CronDispatch
{
    private $year, $month, $day, $hour, $minute;

    /** @var array Range for minute column (e.g. self::_cronMakeRange("1-33/2,55", 0, 59)) */
    private $minutes;
    /** @var array Range for hour column */
    private $hours;
    /** @var array Range for day of month column */
    private $days_of_month;
    /** @var array Range for month column */
    private $months;
    /** @var array Range for day of week column */
    private $days_of_week;
    /** @var array Combined range for day of week and month (must be updated for new month when needed) */
    private $days;

    /**
     * Get next launch time for specified cron string
     *
     * @param string $cron_string   First 5 columns of cron string, e.g. "1-5,8 * * * 4"
     * @param int    $time          Timestamp from which to start computation. Defaults to current timestamp
     * @return int
     */
    public function getNextLaunchTime($cron_string, $time = null)
    {
        $parts = preg_split('/\\s+/s', trim($cron_string));
        $this->minutes = self::makeRange($parts[0], 0, 59);
        $this->hours = self::makeRange($parts[1], 0, 23);
        $this->days_of_month = self::makeRange($parts[2], 1, 31);
        $this->months = self::makeRange($parts[3], 1, 12);
        $this->days_of_week = self::makeRange($parts[4], 0, 7);
        if (is_null($time)) $time = time();
        $new_ts = $this->doComputeNextLaunchTime($time);
        if ($new_ts <= $time) {
            $time += 60;
            $new_ts = $this->doComputeNextLaunchTime($time);
        }
        return $new_ts;
    }

    /**
     * Compute next launch timestamp based on cron settings
     *
     * @param $time int                Base timestamp from which to compute next timestamp
     * @return int
     */
    private function doComputeNextLaunchTime($time)
    {
        $this->year = date('Y', $time);
        $this->month = intval(date('m', $time));
        $this->day = null;
        $this->hour = null;
        $this->minute = null;

        if (!isset($this->months[$this->month])) {
            $this->incrementMonth();
        } else {
            $this->days = $this->getDays();
            $this->day = intval(date('d', $time));

            if (!isset($this->days[$this->day])) {
                $this->incrementDay();
            } else {
                $this->hour = intval(date('H', $time));
                if (!isset($this->hours[$this->hour])) {
                    $this->incrementHour();
                } else {
                    $this->minute = intval(date('i', $time));
                    if (!isset($this->minutes[$this->minute])) {
                        $this->incrementMinute();
                    }
                }
            }
        }

        if (is_null($this->day)) {
            $days = $this->getDays();
            $keys = array_keys($days);
            $this->day = $keys[0];
        }

        if (is_null($this->hour)) {
            $keys = array_keys($this->hours);
            $this->hour = $keys[0];
        }

        if (is_null($this->minute)) {
            $keys = array_keys($this->minutes);
            $this->minute = $keys[0];
        }

        return mktime($this->hour, $this->minute, 0, $this->month, $this->day, $this->year);
    }

    private function incrementMonth()
    {
        $this->month = self::closestNext($this->month, $this->months, $overflow);
        if ($overflow) $this->year++;
        $this->day = null;
        $this->hour = null;
        $this->minute = null;
    }

    private function incrementDay()
    {
        $this->day = self::closestNext($this->day, $this->days, $overflow);
        if ($overflow) {
            $this->incrementMonth();
        } else {
            $this->hour = null;
            $this->minute = null;
        }
    }

    private function incrementHour()
    {
        $this->hour = self::closestNext($this->hour, $this->hours, $overflow);
        if ($overflow) {
            $this->incrementDay();
        } else {
            $this->minute = null;
        }
    }

    private function incrementMinute()
    {
        $this->minute = self::closestNext($this->minute, $this->minutes, $overflow);
        if ($overflow) {
            $this->incrementHour();
        }
    }

    private static function closestNext($value, $options, &$overflow)
    {
        $overflow = false;

        foreach ($options as $key => $_) {
            if ($key > $value) {
                return $key;
            }
        }

        $overflow = true;

        foreach ($options as $first_key => $_) {
            break;
        }
        return $first_key;
    }

    /**
     * Get array(day => true) of days that match supplied days_of_month or days_of_week for a given month and year
     *
     * @return array
     */
    private function getDays()
    {
        $first_day_of_week = date('w', mktime(0, 0, 0, $this->month, 1, $this->year));
        $days_count = date('d', mktime(0, 0, 0, $this->month + 1, 0, $this->year));

        $days_of_week = $this->days_of_week;
        // Sunday can be set as either 0 or 7 in cron. For our purposes 0 is probably better
        if (isset($days_of_week[7])) {
            unset($days_of_week[7]);
            $days_of_week[0] = true;
        }

        $days_of_week_skipped = false;
        if (array_keys($days_of_week) === range(0, 6)) {
            $days_of_week = array();
            $days_of_week_skipped = true;
        }

        $days_of_month = $this->days_of_month;
        foreach ($days_of_month as $day => $_) {
            if ($day > $days_count || $day < 1) {
                unset($days_of_month[$day]);
            }
        }

        $days_of_month_skipped = false;
        if (array_keys($days_of_month) === range(1, $days_count)) {
            $days_of_month = array();
            $days_of_month_skipped = true;
        }

        if ($days_of_month_skipped && $days_of_week_skipped) {
            $result = array();
            for ($i = 1; $i <= $days_count; $i++) $result[$i] = true;
            return $result;
        }

        $result = $days_of_month;
        for ($i = 1; $i <= $days_count; $i++) {
            if (isset($days_of_week[($first_day_of_week + $i - 1) % 7])) {
                $result[$i] = true;
            }
        }

        ksort($result, SORT_NUMERIC);
        return $result;
    }

    /**
     * Convert "1-23/4" to array(1 => true, 5 => true, 9 => true, 13 => true, 17 => true, 21 => true)
     *
     * @param $col string      crontab column
     * @param $min_value int   minimum value if "*" is supplied (e.g. 0 for hour column, 1 for day of month, etc.)
     * @param $max_value int   maximum value if "*" is supplied (e.g. 23 for hour column, 31 for day of month, etc.)
     * @return array
     */
    private static function makeRange($col, $min_value, $max_value)
    {
        $result = array();

        // Split "1-4,6-8" into separate groups "1-4" and "6-8"
        foreach (explode(',', $col) as $range) {
            $parts = explode('/', $range);
            $step = 1;
            $range = $parts[0];
            if (isset($parts[1])) $step = max(1, intval($parts[1]));
            if ($range == '*')    $range = "$min_value-$max_value";

            $parts = explode('-', $range);
            $start = $end = max($min_value, $parts[0]);
            if (isset($parts[1])) $end = min($max_value, $parts[1]);

            for ($i = $start; $i <= $end; $i += $step) $result[$i] = true;
        }

        ksort($result, SORT_NUMERIC);
        return $result;
    }
}
