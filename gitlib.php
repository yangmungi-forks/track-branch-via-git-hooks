<?php

function git_pull($trackcfg) {
    // now run git pull for given repo
    $output = array();
    $return_var = null;

    $cmd = sprintf("/usr/bin/git pull 2>&1");
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    debug("cmd output:\n" . implode("\n", $output));    

    // output command results
    echo(implode("\n", $output));

    return $return_var;
}

function git_automerge($trackcfg) {
    $output = array();
    $return_var = null;

    // We do not care about the following command's STDERR
    $cmd = sprintf("/usr/bin/git fetch --tags");
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    debug("cmd output:\n" . implode("\n", $output));    

    // This command requires some specialties, since a merge requires a 
    // commit
    // TODO abstract out the instance-only configuration variables
    $cmd = sprintf("/usr/bin/git -c user.email=%s -c user.name=%s merge --no-ff `git tag | grep %s | tail -n1` 2>&1",
        escapeshellarg($trackcfg['admin_email']),
        escapeshellarg($trackcfg['admin_name']),
        escapeshellarg($trackcfg['target']));
    debug('executing command: ' . $cmd);
    exec($cmd, $output, $return_var);
    debug("cmd output:\n" . implode("\n", $output));

    if ($return_var !== 0) {
        // Our working directory is in a conflicted state, so hard reset
        $cmd = "/usr/bin/git reset --hard 2>&1 && /usr/bin/git clean -fd 2>&1";

        // This should always work, and will return the incorrect return value
        debug('executing command: ' . $cmd);
        exec($cmd, $output, $solemn);
        debug("cmd output:\n" . implode("\n", $output));    
    } else {
        // Push?
    }

    // output command results
    echo(implode("\n", $output));

    return $return_var;
}
