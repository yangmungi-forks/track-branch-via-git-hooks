<?php
/**
 *  Checks configuration values and install settings, will kill entire
 *  script process if there are any hard failures.
 */

$curdir = dirname(__FILE__);

if (!file_exists($curdir . '/config.php')) {
    error_log('git_post_receive.php: no configuration file');
    echo "No configuration file!\n";
    die(1);
}

require $curdir . '/config.php';
require $curdir . '/functions.php';
require $curdir . '/gitlib.php';

// NOTE: JSON should come preinstalled with PHP starting with 5.2
if (!function_exists('json_decode')) {
    error('JSON not installed');
}

if (!isset($debug)) {
    $debug = false;
}

// Check configuration settings
$checks = array(
    'tracking_rules',
);

foreach ($checks as $check) {
    if (empty(${$check})) {
        error('no $' . $check . ' - check your config.php');
    }
}

unset($checks);

$global_checks = array(
    'repo_user', 'repo_location', 'admin_email', 'admin_name'
);

// Check and set default tracking rules
// Also shortcut for next steps
foreach ($tracking_rules as $tracking_rule => $trackcfg) {
    // Handle simple branch tracking
    if (is_string($trackcfg)) {
        unset($tracking_rules[$tracking_rule]);
        $tracking_rule = $trackcfg;
        $trackcfg = array();
    }

    foreach ($global_checks as $global_check) {
        if (empty($trackcfg[$global_check])) {
            if (empty(${$global_check})) {
                error('no tracking ' . $global_check . ' nor global version '
                    . 'available.');
            } else {
                $trackcfg[$global_check] = ${$global_check};
            }
        }
    }

    // TODO normalize type, remote and action to use previous foreach
    if (!isset($trackcfg['type']) || $trackcfg['type'] != 'tag') {
        $trackcfg['type'] = 'heads';
    } else {
        $trackcfg['type'] = 'tags';
    }

    // TODO Fully fledge this feature
    $trackcfg['remote'] = 'origin';

    if (!isset($trackcfg['action'])) {
        $action = 'git_pull';
    } else {
        $action = 'git_' . $trackcfg['action'];
    }

    if (!function_exists($action)) {
        debug('tracking_response ' . $action . ' does not exist.');

        unset($tracking_rules[$tracking_rule]);
        continue;
    } else {
        $trackcfg['action'] = $action;
    }

    // This is just a copy appended to the array
    // Currently used when sent to the git_* functions
    $trackcfg['target'] = $tracking_rule;

    $tracking_rules[$tracking_rule] = $trackcfg;
}
