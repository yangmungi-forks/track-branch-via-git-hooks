#!/usr/bin/php
<?php
/*
 * Commands to be run via command line as specified user.
 */

// get script variables
require dirname(__FILE__) . '/config.php';
require dirname(__FILE__) . '/functions.php';

debug(sprintf('gitpull.php called: get_script_user() = %s, is_cli() = %s', 
        get_script_user(), is_cli()));

// make sure that script is run from command line and as specified repo user
if (get_script_user() == $repo_user && is_cli()) {
    // now run git pull for given repo
    $output = array();
    $return_var = null;
    chdir($repo_location);
    $cmd = sprintf("/usr/bin/git fetch --tags", $repo_location);
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    
    debug('cmd output: ' . implode("\n", $output));    

    $cmd = sprintf("/usr/bin/git merge `git tag | tail -n1`", $repo_location);
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    
    debug('cmd output: ' . implode("\n", $output));    

    if ($return_var !== 0) {
        $cmd = sprintf("/usr/bin/git reset --hard && /usr/bin/git clean -fd", 
            $repo_location);
        debug('executing command: ' . $cmd);
        exec($cmd, $output, $solemn);
        
        debug('cmd output: ' . implode("\n", $output));    
    }

    // output command results
    echo(implode("\n", $output));
    exit($return_var);  // exit with command error code
}

// else exit with bad error code
debug($argv[0] . ' called invalidly');
echo 'Script needs to be run on command line as specified user';
exit(1);
