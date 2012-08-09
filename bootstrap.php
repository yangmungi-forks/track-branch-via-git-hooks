<?php
/**
 *  Checks configuration values and install settings, will kill entire
 *  script process if there are any hard failures.
 */

$curdir = dirname(__FILE__);

if (!file_exists($curdir . '/config.php')) {
    error_log('git_post_receive.php: no configuration file');
    die(1);
}

require $curdir . '/config.php';
require $curdir . '/functions.php';
require $curdir . '/gitlib.php';

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

// Check and set default tracking rules
// Also shortcut for next steps
foreach ($tracking_rules as $tracking_rule => $trackcfg) {
    if (!isset($trackcfg['type']) || $trackcfg['type'] != 'tag') {
        $trackcfg['type'] = 'heads';
    } else {
        $trackcfg['type'] = 'tags';
    }

    // TODO Fully fledge this feature
    $trackcfg['remote'] = 'origin';

    if (!isset($trackcfg['action'])) {
        $action = 'gitpull';
    } else {
        $action = $trackcfg['action'];
    }

    if (!function_exists($action)) {
        debug('tracking_response ' . $action . ' does not exist.');

        unset($tracking_rules[$tracking_rule]);
        continue;
    } else {
        $trackcfg['action'] = $action;
    }

    $trackcfg['target'] = $tracking_rule;

    $tracking_rules[$tracking_rule] = $trackcfg;
}
