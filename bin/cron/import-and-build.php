#!/usr/bin/env php
<?php

use Crunz\Schedule;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 2).'/vendor/autoload_runtime.php';

$schedule = new Schedule();
$task = $schedule
    ->run(function () {
        $process = new Process(
            command: ['/var/www/bin/console', 'app:strava:build-files'],
            timeout: null
        );
        $process->run();

        echo $process->getOutput();
    })
    ->description('Import Strava data and build HTML files')
    ->cron('* * * * *')
    ->preventOverlapping();

return $schedule;
