#!/usr/bin/env php
<?php

use Crunz\Schedule;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 2).'/vendor/autoload_runtime.php';

$schedule = new Schedule();

if (!$cronExpression = getenv('IMPORT_AND_BUILD_SCHEDULE')) {
    return $schedule;
}

$task = $schedule
    ->run(function () {
        $processesToRun = [
            new Process(['/var/www/bin/console', 'app:strava:import-data']),
            new Process(['/var/www/bin/console', 'app:strava:build-files']),
        ];

        foreach ($processesToRun as $process) {
            try {
                $process->setTimeout(null);
                $process->mustRun();
                echo $process->getOutput();
            } catch (ProcessFailedException $exception) {
                echo $exception->getMessage();
                break;
            }
        }
    })
    ->description('Import Strava data and build HTML files')
    ->cron($cronExpression)
    ->preventOverlapping();

return $schedule;
