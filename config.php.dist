<?php
/**
 * Config values for git_post_receive.php 
 */

// These define which preg_match() will run what script.
// Use "heads/MYBRANCH" for branches, "tags/MYTAG" for tags.
// This can be a regular expression, and does not need to be escaped.
// It will be evaluated using preg_match(), and will always have an ending
// '$' matching symbol appended to it.
// The first match will run, and will stop checking for other matches.
//  (i.e. rules 'heads/foo' and 'heads/foobar' will always match 'heads/foo',
//  if 'heads/foo' comes first.)
//
//      Basic:
//  
// Simply replace the 'master' with whatever branch you want to track. Be
//  sure to surround it in quotes.
// 
//      Advanced:
//
// The array value (right side of =>) must be an array that can consist of
//  'action' => name of file that will be called (without '.php'). 
//      Currently 'automerge' and 'pull' are available.
//      Check out gitlib.php to see what each does.
//      Defaults to 'pull'.
//  'type' => 'branch' or 'tag'. 
//      'tag' will imply 'action' => 'automerge', to override, explicitly
//          state that 'action' => 'pull' (or action desired).
//      Defaults to 'branch'.
//  'remote' => name of remote repo to fetch from. $repo user must have access.
//      Defaults to 'origin'.
//  'repo_location' => location of repository to chdir() to.
//  'repo_user' => username to use with `sudo -u`.
//      Needs special sudo rules.
//  'admin_email' => email to message for successes and failures.
//      Personal note: this will probably not be used that often.
//  'admin_name' => name to use for merge commit message.
$tracking_rules['master'] = array();

// #### //
// Global options.
// 
// Each of these can be overwritten in the $tracking_rules array.
// Just specify in the relevant matcher.
//
// @example:
//  $tracking_rules = array();
//  $tracking_rules['master'] = array();
//  $tracking_rules['.*-gm']['type'] = 'tag';
//  $tracking_rules['stage']['repo_location']   = '/server/stage/';
//  $tracking_rules['stage']['repo_user']       = 'stage-admin';
//  $repo_user = 'master-admin';
//  $repo_location = '/server/master/';
//  $admin_email = 'server-master@example.com';
//
// The above configurations will allow the repository at /server/master/
//  to 'pull' from updates to the 'master' branch and will 'automerge'
//  from updates to tags matching '.*-gm'. /server/stage will 'pull' from
//  updates to the 'stage' branch, and will `sudo -u` as 'stage-admin'.
//
// #### //

// This is the directory at which the git repository to run is located.
// Include the final /
$repo_location = '';

// This is used for security purposes.
// Apache must be able to sudo as this user.
// This user must have access to the git repository.
// Refer to README for more information.
$repo_user = '';  

// Email address to contact everytime there is an error or an action is taken.
$admin_email = ''; 

// Name to provide to Github/Git configuration settings
$admin_name = '';

// Set to true to print debugging messages into error_log.
$debug = false; 
