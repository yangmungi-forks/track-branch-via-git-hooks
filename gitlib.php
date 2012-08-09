<?php

function gitpull($trackcfg) {
    // now run git pull for given repo
    $output = array();
    $return_var = null;

    $cmd = sprintf("/usr/bin/git pull 2>&1");
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    debug('cmd output: ' . implode("\n", $output));    

    // output command results
    echo(implode("\n", $output));

    return $return_var;  // exit with command error code
}

function gitmergeorreset($trackcfg) {
    // now run git pull for given repo
    $output = array();
    $return_var = null;

    // We do not care about the following command's STDERR
    $cmd = sprintf("/usr/bin/git fetch --tags");
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    debug('cmd output: ' . implode("\n", $output));    

    $cmd = sprintf("/usr/bin/git merge `git tag | grep %s | tail -n1` 2>&1",
        escapeshellarg($trackcfg['target']));
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    debug('cmd output: ' . implode("\n", $output));    

    if ($return_var !== 0) {
        // Our working directory is in a conflicted state, so hard reset
        $cmd = "/usr/bin/git reset --hard 2>&1 && /usr/bin/git clean -fd 2>&1";

        // This should always work, and will return the incorrect return value
        debug('executing command: ' . $cmd);
        exec($cmd, $output, $solemn);
        debug('cmd output: ' . implode("\n", $output));    
    }

    // output command results
    echo(implode("\n", $output));

    return $return_var;
}
