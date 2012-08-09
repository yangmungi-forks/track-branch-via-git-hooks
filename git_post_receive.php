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

    // Command to run
    $cmd = false;
    $output = array();
    $return_var = null;

    $currdir = dirname(__FILE__);

    $updated_ref = $payload->ref;

    // Check to see if this
    foreach ($tracking_rules as $tracking_rule => $trackcfg) {
        $type = $trackcfg['type'];

        if (preg_match("|refs/$type/$tracking_rule|", $updated_ref)) {
            debug('payload matched a rule: ' . $tracking_rule);
            $cmd = sprintf("sudo -u %s %s/usergit.php %s", $repo_user, 
                $currdir, escapeshellarg($tracking_rule));
        }
    }

    if (!$cmd) {
        debug('payload matched no rules.');
    } else {
        debug('executing command: ' . $cmd);
        // Redirect STDERR output to STDOUT for email
        exec($cmd . ' 2>&1', $output, $return_var);        
        debug('cmd output: ' . implode("\n", $output));

        $mailto = array($admin_email);
        $subject_part = 'Successfully run ';

        // if $return_var is non-zero, then an error happened
        // http://www.linuxtopia.org/online_books/advanced_bash_scripting_guide/exitcodes.html
        if (0 !== $return_var) {
            // there was an error, so email the admin
            debug('there was an error, emailing admin: ' . $admin_email);
            
            $mail_content = sprintf(
                "return_var: %d\n\ncommand line output:\n\n%s"
                    . "\n\njson_payload:\n\n%s", 
                $return_var,
                implode("\n", $output), 
                print_r($payload, true)
            );
        } else {
            // update was successful, so email committer and admin
            // get emails of committers
            if (isset($payload->pusher->email)) {
                $mailto[] = $payload->pusher->email;
            }

            debug('update successful, emailing admin and pusher: ' 
                . implode(';', $mailto));            
            
            $mail_content = sprintf(
                "Updated repo at %s with command `%s`. Here is the output "
                    . "of the command:\n\n%s", 
                $repo_location,
                $cmd,
                implode("\n", $output)
            );
        }
        
        $sent_email = mail(
            implode(',', $mailto),
            "git_post_receive.php : $subject_part $cmd",
            $mail_content
        );

        if (empty($sent_email)) {
            error_log('Could not send email notification for git_post_receive');
        }
    }
} else {
    debug('no payload found');
}
