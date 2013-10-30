<?php
require_once('CronDispatch.php');

if ($argc != 3) {
    fwrite(STDERR, "Usage: example.php <cron-string> <results-count>\n");
    exit(2);
}

$cron = $argv[1];
$results_count = intval($argv[2]);

$time = time();
for ($i = 0; $i < $results_count; $i++) {
    $instance = new CronDispatch();
    $time = $instance->getNextLaunchTime($cron, $time);
    echo date('Y-m-d H:i:s', $time) . "\n";
}