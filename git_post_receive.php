<?php
/**
 * Respond to github post-receive JSON.
 * 
 * Security concerns: I wish I can restrict this script to only respond to POST
 * requests from github's servers, but that information isn't avaialble.
 */

// get script variables
require dirname(__FILE__) . '/bootstrap.php';

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
        error('Invalid payload JSON sent from ' . $_SERVER['SERVER_ADDR']);
    }
    
    $sent_email = false;
    $cmd = false;
    $output = array();
    $return_var = null;

    $currdir = dirname(__FILE__);

    $updated_ref = $payload->ref;

    // Check to see if this
    foreach ($tracking_rules as $tracking_rule => $tracking_response) {
        if (preg_match('/refs\/' . preg_quote($tracking_rule, '/')
                . '/', $updated_ref)) {
            debug('payload matched a rule: ' . $tracking_rule);
            $cmd = sprintf("sudo -u %s %s/%s.php", $repo_user, $currdir,
                $tracking_response);

        }
    }

    if (!$cmd) {
        debug('payload matched no rules.');
    } else {
        debug('executing command: ' . $cmd);
        // Redirect STDERR output to STDOUT for email
        exec($cmd . ' 2>&1', $output, $return_var);        
        debug('cmd output: ' . implode("\n", $output));

        // if $return_var is non-zero, then an error happened
        // http://www.linuxtopia.org/online_books/advanced_bash_scripting_guide/exitcodes.html
        if (0 !== $return_var) {
            // there was an error, so email the admin
            debug('there was an error, emailing admin: ' . $admin_email);
            $sent_email = mail(
                $admin_email, 
                'git_post_receive.php : Failed to run ' . $cmd, 
                sprintf(
                    "return_var: %d\n\ncommand line output:\n\n%s"
                        . "\n\njson_payload:\n\n%s", 
                    $return_var,
                    implode("\n", $output), 
                    print_r($payload, true)
                )
            );
        } else {
            // update was successful, so email committer and admin
            // get emails of committers
            $committers = array($admin_email);
            if (isset($payload->pusher->email)) {
                $committers[] = $payload->pusher->email;
            }

            debug('update successful, emailing pusher and admin: ' 
                . implode(';', $committers));            
            
            $sent_email = mail(
                implode(',', $committers), 
                'git_post_receive.php : Successfully run ' . $cmd, 
                sprintf(
                    "Updated repo at %s with latest command %s " 
                        . "branch. Here is the output command" 
                        . "\n\n%s", 
                    $repo_location,
                    $cmd,
                    implode("\n", $output)
                )
            );
        }
        
        if (empty($sent_email)) {
            error_log('Could not send email notification for git_post_receive');
        }
    }
} else {
    debug('no payload found');
}
