<?php
/**
 *  Checks configuration values and install settings, will kill entire
 *  script process if there are any hard failures.
 */

$curdir = dirname(__FILE__);
require $curdir . '/config.php';
require $curdir . '/functions.php';

// NOTE: JSON should come preinstalled with PHP starting with 5.2
if (!function_exists('json_decode')) {
    error('JSON not installed');
}

// Check configuration settings
$checks = array(
    'tracking_rules',
    'repo_location',
    'repo_user',
    'admin_email'
);

foreach ($checks as $check) {
    if (empty($$check)) {
        error('no $' . $check . ' - check your config.php');
    }
}

unset($checks);

// Check tracking rules
foreach ($tracking_rules as $tracking_rule => $tracking_response) {
    if (!file_exists($curdir . '/' . $tracking_response . '.php')) {
        debug('tracking_response ' . $tracking_response 
            . '.php does not exist.');

        unset($tracking_rules[$tracking_rule]);
    }
}
