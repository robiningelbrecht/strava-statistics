<?php

// looser version, looks for any newline (optionally commented) with content leading up to printf "AUTO CRON" BUT not matching example in crontab comments
// https://regex101.com/r/eAu3gP/1
$AUTO_LINE = '/#?([^\n\r>]+)\s(printf "AUTO CRON"[^\n\r]+)/';

// stricter version that only finds well-formed cron schedules
// https://regex101.com/r/gp4mGN/2
#$AUTO_LINE = '/#?((?:[\d\*]\s){5})(printf "AUTO CRON"[^\n\r]+)/';

$CRON_ABC_PATH = '/config/crontabs/abc';

$content = file_get_contents($CRON_ABC_PATH);
$scheduleEnv = getenv('IMPORT_AND_BUILD_SCHEDULE');

$match = preg_match($AUTO_LINE, $content, $matches);
if($match !== 1) {
    echo 'Auto Cron: Did not find well-formed cron schedule with AUTO CRON flag, skipping generation...' . PHP_EOL;
    exit(0);
}
//$pos = strpos($content, $matches[0]);

if($scheduleEnv === false) {
    if($matches[0][0] === '#') {
            echo 'Auto Cron: Already disabled, nothing to do' . PHP_EOL;
            exit(0);
    }
    // comment out existing
    $commented = '#' . trim($matches[1]) . ' ' . $matches[2];
    $modified = preg_replace($AUTO_LINE, $commented, $content);
    file_put_contents($CRON_ABC_PATH, $modified);
    echo 'Auto Cron: Disabled because IMPORT_AND_BUILD_SCHEDULE was not set' . PHP_EOL;
} else {
    $active = trim($scheduleEnv) . ' ' . $matches[2];
    $modified = preg_replace($AUTO_LINE, $active, $content);
    file_put_contents($CRON_ABC_PATH, $modified);
    echo 'Auto Cron: Set schedule to ' . $scheduleEnv . PHP_EOL;
}

exit(0);