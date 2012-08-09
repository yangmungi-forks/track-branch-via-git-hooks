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
if (validate_cli($repo_user) && isset($argv[1])) {
    if (isset($tracking_rules[$argv[1]])) {
        // bootstrap.php will alter the action field
        $trackcfg = $tracking_rules[$argv[1]];

        $fn = $trackcfg['action'];

        if (function_exists($fn)) {
            chdir($repo_location);
            exit($fn($trackcfg));
        }
    }
}

// else exit with bad error code
debug(__FILE__ . ' called invalidly');
error('Script needs to be run on command line as specified user');
