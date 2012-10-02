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

    // Check to see if this works for multiple matches
    foreach ($tracking_rules as $track_key => $trackcfg) {
        $type = $trackcfg['type'];
        $repo_user = $trackcfg['repo_user'];
        $tracking_rule = $trackcfg['target'];

        if (preg_match("|refs/$type/$tracking_rule\$|", $updated_ref)) {
            debug('payload matched a rule: ' . $tracking_rule);
            $cmd = sprintf("sudo -u %s %s/usergit.php %s", $repo_user, 
                $currdir, escapeshellarg($track_key));
            $repo_location = $trackcfg['repo_location'];
            $matching_track_key = $track_key;

            $output = array();

            // Execute the command
            bash::execute($cmd . ' 2>&1', $output, $return_var);        

            $mailto = array($admin_email);
            $subject_part = 'Successfully run ';

            // if $return_var is non-zero, then an error happened
            // http://www.linuxtopia.org/online_books/advanced_bash_scripting_guide/exitcodes.html
            if (0 !== $return_var) {
                // there was an error, so email the admin
                debug('there was an error, emailing admin: ' . $admin_email);

                $subject_part = 'There was an error running ';
            
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
                    "Updated repo at %s with command `%s`.\n"
                        . "Settings: %s.\n"
                        . "Output of the command:\n\n%s", 
                    $repo_location,
                    $cmd,
                    print_r($tracking_rules[$matching_track_key], true),
                    implode("\n", $output)
                );
            }
      
            // TODO make an email manager
            $sent_email = mail(
                implode(',', $mailto),
                "Git-integrate : $subject_part $cmd",
                $mail_content
            );

            if (empty($sent_email)) {
                error_log(
                    'Could not send email notification for git_post_receive'
                );
            }
        }
    }

    if (!$cmd) {
        debug('payload matched no rules.');
    }
} else {
    debug('no payload found');
}
