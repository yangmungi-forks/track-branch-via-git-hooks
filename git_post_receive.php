<?php
/**
 * Respond to github post-receive JSON.
 * 
 * Security concerns: I wish I can restrict this script to only respond to POST
 * requests from github's servers, but that information isn't avaialble.
 */

// get script variables
require dirname(__FILE__) . '/config.php';
require dirname(__FILE__) . '/functions.php';

// NOTE: JSON should come preinstalled with PHP starting with 5.2
if (!function_exists('json_decode')) {
    die(error_log('JSON not installed'));
}

debug('github script called by ' . $_SERVER['SERVER_ADDR']);

// github sends git post-receive hooks as a single POST param called 'payload'
/* POST param is in following JSON format:
{
  :before     => before,
  :after      => after,
  :ref        => ref,
  :commits    => [{
    :id        => commit.id,
    :message   => commit.message,
    :timestamp => commit.committed_date.xmlschema,
    :url       => commit_url,
    :added     => array_of_added_paths,
    :removed   => array_of_removed_paths,
    :modified  => array_of_modified_paths,
    :author    => {
      :name  => commit.author.name,
      :email => commit.author.email
    }
  }],
  :repository => {
    :name        => repository.name,
    :url         => repo_url,
    :pledgie     => repository.pledgie.id,
    :description => repository.description,
    :homepage    => repository.homepage,
    :watchers    => repository.watchers.size,
    :forks       => repository.forks.size,
    :private     => repository.private?,
    :owner => {
      :name  => repository.owner.login,
      :email => repository.owner.email
    }
  }
}
 */

if (!empty($_POST['payload'])) {    
    debug('got payload: ' . $_POST['payload']);
    
    // parse payload data
    $payload = json_decode($_POST['payload']);    
    // on error json_decode retuns null
    if (empty($payload)) {
        die(error_log('Invalid payload JSON sent from ' . 
                $_SERVER['SERVER_ADDR']));
    }
    
    $sent_email = false;
    $cmd = false;
    $output = array(); 
    $return_var = null;

    $currdir = dirname(__FILE__);

    // now make sure that the commit is for the branch we want to track
    if ($payload->ref === 'refs/heads/' . $tracking_branch) {
        debug('payload is a commit to a tracking branch: ' . $tracking_branch);
        
        // this is kinda convoluted, but necessary for security (?). apache
        // user only has the ability to run a specified script as specified user
        // so will need to execute that script as specified user. That script
        // will output and exit the git pull command as if it was run here
        $cmd = sprintf("sudo -u %s %s/gitpull.php", $repo_user, $currdir);    
        // system should be setup to run gitpull.php as specified user 
    } else if (preg_match('|refs/tags/.*-gm|', $payload->ref)) {
        debug('payload was a gm-cut');

        $cmd = sprintf("sudo -u %s %s/gitmergeorreset.php", $repo_user, $currdir);
    } else {
        debug('payload was not a commit to a tracking branch: ' . $tracking_branch);        
    }

    if ($cmd) {
        debug('executing command: ' . $cmd);
        exec($cmd, $output, $return_var);        
        
        debug('cmd output: ' . implode("\n", $output));
        
        // if $return_var is non-zero, then an error happened
        // http://www.linuxtopia.org/online_books/advanced_bash_scripting_guide/exitcodes.html
        if (0 !== $return_var) {
            // there was an error, so email the admin
            debug('there was an error, emailing admin: ' . $admin_email);
            $sent_email = mail($admin_email, 'git_post_receive: Failed to update ' . 
                    'branch ' . $tracking_branch, sprintf("return_var: %d\n\ncommand line " . 
                    "output:\n\n%s\n\njson_payload:\n\n%s", $return_var,
                    implode("\n", $output), print_r($payload, true)));
        } else {
            // update was successful, so email committer and admin
            
            // get emails of committers
            $committers = array($admin_email, $payload->pusher->email);
            debug('update successful, emailing pusher and admin: ' . implode(';', $committers));            
            
            $sent_email = mail(implode(',', $committers), 'git_post_receive: ' . 
                    sprintf('Update repo with changes from %s branch', $tracking_branch), 
                    sprintf("Updated repo at %s with latest changes from %s " . 
                    "branch. Here is the output of the git pull " . 
                    "\n\n%s", $repo_location, $tracking_branch, 
                    implode("\n", $output)));            
        }
        
        if (empty($sent_email)) {
            error_log('Could not send email notification for git_post_receive');
        }
    }
} else {
    debug('no payload found');
}
?>
