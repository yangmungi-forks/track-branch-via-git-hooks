#!/usr/bin/php
<?php
/*
 *  Commands to be run via command line as specified user.
 *  This is just variable hook for running github commands.
 */

// Get script variables
require dirname(__FILE__) . '/bootstrap.php';

debug(sprintf('%s called: get_script_user() = %s, is_cli() = %s', 
        __FILE__, get_script_user(), is_cli()));

// make sure that script is run from command line and as specified repo user
if (isset($argv[1])) {
    $track_rule = $argv[1];
    if (isset($tracking_rules[$track_rule])) {
        // bootstrap.php will alter the action field
        $trackcfg = $tracking_rules[$track_rule];

        $repo_user = $trackcfg['repo_user'];
        $repo_location = $trackcfg['repo_location'];
        $fn = $trackcfg['action'];

        if (validate_cli($repo_user) && validate_action($fn)) {
            echo "Working with repository at $repo_location\n";
            chdir($repo_location);

            $action = new $fn($trackcfg);
            $r = $action->run();

            exit($r);
        }
    }
}

// else exit with bad error code
error(__FILE__ . ' called invalidly');
