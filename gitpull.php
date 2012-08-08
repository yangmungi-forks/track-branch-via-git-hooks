#!/usr/bin/php
<?php
/*
 * Commands to be run via command line as specified user.
 */

// get script variables
require dirname(__FILE__) . '/bootstrap.php';

debug(sprintf('%s called: get_script_user() = %s, is_cli() = %s', 
        __FILE__, get_script_user(), is_cli()));

// make sure that script is run from command line and as specified repo user
if (validate_cli($repo_user)) {
    // now run git pull for given repo
    $output = array();
    $return_var = null;
    $cmd = sprintf("cd %s && /usr/bin/git pull", $repo_location);
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    debug('cmd output: ' . implode("\n", $output));    

    // output command results
    echo(implode("\n", $output));
    exit($return_var);  // exit with command error code
}

// else exit with bad error code
debug(__FILE__ . ' called invalidly');
error('Script needs to be run on command line as specified user');
