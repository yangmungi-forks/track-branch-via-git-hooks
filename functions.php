<?php
/*
 * Script functions.
 */

function debug($msg) {
   global $debug;
   if ($debug) {
       error_log($msg);
   }
}

/**
 * For some reason php-posix is not installed by default in our server, so just 
 * run command line command 'whoami' and get result
 */
function get_script_user()
{
    return exec('whoami');
}

/**
 * Determines if script is run from command line.
 * http://www.codediesel.com/php/quick-way-to-determine-if-php-is-running-at-the-command-line/
 * 
 * @return boolean
 */
function is_cli() {

    if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
        return true;
    } else {
        return false;
    }

}
