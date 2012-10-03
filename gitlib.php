<?php
/**
 *  A simple wrapper class for often-used shell commands.
 **/
class bash {
    const redir_err = '2>&1';
    const chain = ' && ';

    /**
     *  Simple wrapper, includes some debugging.
     **/
    static function execute($cmd, &$output) {
        debug('executing command: ' . $cmd);
        exec($cmd, $output, $retvar);
        debug("command output:\n" . implode("\n", $output));
        return $retvar;
    }
}

/**
 *  A simple wrapper class for bash-git.
 **/
class git {
    const bin = '/usr/bin/git';

    /**
     *  Basic command, with automated STDERR redirection to STDOUT.
     **/
    static function command($command) {
        return self::bin . " $command " . bash::redir_err;
    }

    /**
     *  Generates a git command that can commit.
     **/
    static function commit_command($command, $args) {
        $fields = array(
            'admin_email' => 'user.email', 
            'admin_name' => 'user.name'
        );

        // Turn (A => B) and (A => C) into (B => C)
        foreach ($fields as $field => $gitcfg) {
            $gitcfgv = '';
            if (!empty($args[$field])) {
                $gitcfgv = $args[$field];
            }

            unset($fields[$field]);
            $fields[$gitcfg] = $gitcfgv;
        }

        // I want to array_map
        $specials = array();
        foreach ($fields as $gitcfg => $gitv) {
            $specials[] = "-c $gitcfg=". escapeshellarg($gitv);
        }

        $special = implode(' ', $specials);

        return self::command("$special $command");
    }

    ///////////////////////////////
    // Convenience git functions //
    ///////////////////////////////

    static function branch() {
        return self::command('branch');
    }

    static function fetch() {
        return self::command('fetch -p') 
            . bash::chain . self::command('fetch -pt');
    }

    static function update_submodules() {
        return self::command('submodule update --init');
    }

    static function full_clean() {
        return self::command('reset --hard') 
            . bash::chain . self::command('clean -fd');
    }

    static function show_merge_commits($target, $from='HEAD') {
        return self::command("log $from..$target --first-parent --oneline");
    }
}

/**
 *  Base class for possible actions responding to repo actions.
 **/
abstract class git_exec {
    var $trackcfg = null;
    var $fetched = false;

    function __construct($trackcfg) {
        $this->trackcfg = $trackcfg;
    }

    function print_output($output) {
        echo implode("\n", $output);
    }

    function prefetch() {
        if ($this->fetched) {
            return 0;
        }

        $this->fetched = true;

        return bash::execute(git::fetch(), $output);
    }

    function full_refname() {
        return $this->trackcfg['remote'] . '/' . $this->trackcfg['target'];
    }

    /**
     *  Generic run statement.
     **/
    function run() {
        $retvar = $this->prefetch();

        // A fetch failed!
        if ($retvar) {
            return $retvar;
        }

        bash::execute(git::command('status'), $o);
        $this->print_output($o);

        $cmd = $this->prep_command();

        $retvar = bash::execute($cmd, $output);
        $this->print_output($output);

        return $retvar;
    }

    abstract function prep_command();
}

/**
 *  Run a simple pull. Will attempt fast-foward merges only.
 **/
class git_pull extends git_exec {
    function prep_command() {
        $trackcfg = $this->trackcfg;

        $full_refname = escapeshellarg($this->full_refname());

        return git::show_merge_commits($full_refname) . bash::chain 
            . git::command(
                sprintf(
                    'merge --ff-only %s',
                    $full_refname
                )
            ) . bash::chain . git::update_submodules();
    }
}

/**
 *  Will merge based on tag matches. Only works on tags.
 **/
class git_automerge extends git_exec {
    function run() {
        $this->prefetch();

        $retvar = $this->find_target();
        if ($retvar) {
            return $retvar;
        }

        $mergeret = parent::run();

        if ($mergeret) {
            bash::execute(git::full_clean(), $o);
        }

        return $mergeret;
    }

    function prep_command() {
        // This command requires some specialties, since a merge requires a 
        // commit
        $target = escapeshellarg($this->trackcfg['target']);
        $cmd = git::show_merge_commits($target) . bash::chain 
            . git::commit_command(
                sprintf(
                    "merge --no-ff %s", 
                    $target
                ), $this->trackcfg
            ) . bash::chain . git::update_submodules();

        return $cmd;
    }

    function find_target() {
        $targettype = $this->trackcfg['type'];

        if ($targettype == 'tags') {
            // TODO make more sofistamakated
            bash::execute(sprintf(
                    git::bin . " tag | grep %s | tail -n1", 
                    escapeshellarg($this->trackcfg['target'])
                ), $output);

            if (empty($output)) {
                return 1;
            }

            $track_target = $output[0];
        } else {
            $track_target = $this->full_refname();
        }

        // use $this->target ?
        $this->trackcfg['target'] = $track_target;
    }
}
