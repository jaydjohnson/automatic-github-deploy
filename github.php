<?php
/*
    Functions for use with GitHub repositories

    This is a modification from Igor Lilliputten's BitBucket Deployment Script:
    https://bitbucket.org/lilliputten/automatic-bitbucket-deploy

    Which was also based on:
    Automated git deployment' script by Jonathan Nicoal:
    http://jonathannicol.com/blog/2013/11/19/automated-git-deployments-from-bitbucket/

    ---
    Jay Johnson
*/

/*{{{ *** Global variables */

define('DEFAULT_FOLDER_MODE', 0755);

$PAYLOAD   = array ();
$BRANCHES  = array ();
$REPO      = ''; // full name
$REPO_NAME = ''; // name

/*}}}*/

function initConfig ()/*{{{ Initializing repo configs */
{
    global $PROJECTS;

    $tmpProjects = array();

    // Bitbucket uses lower case repo names!
    foreach ( $PROJECTS as $repoName => $config ) {
        $tmpProjects[strtolower($repoName)] = $config;
    }

    $PROJECTS = $tmpProjects;
}/*}}}*/

function initLog ()/*{{{ Initializing log variables */
{
    global $CONFIG, $_LOG_ENABLED, $_LOG_FILE;

    if ( !empty($CONFIG['log']) ) {
        $_LOG_ENABLED = true;
    }
    if ( !empty($CONFIG['logFile']) ) {
        $_LOG_FILE = $CONFIG['logFile'];
    }
    if ( !empty($CONFIG['logClear']) ) {
        _LOG_CLEAR();
    }

}/*}}}*/

function initPayload ()/*{{{ Get posted data */
{
    global $PAYLOAD;

    if (isset($_SERVER['HTTP_X_GITHUB_EVENT'], $_SERVER['HTTP_X_GITHUB_DELIVERY'], $_SERVER['HTTP_X_HUB_SIGNATURE'], $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['REMOTE_ADDR'])) {
        _LOG('*** ' . $_SERVER['HTTP_X_GITHUB_EVENT'] . ' #' . $_SERVER['HTTP_X_GITHUB_DELIVERY'] .
            ' (' . $_SERVER['HTTP_USER_AGENT'] . ')');
        _LOG('remote addr: ' . $_SERVER['REMOTE_ADDR']);
    } else {
        _LOG('*** [unknown http event key] #[unknown http hook uuid] (unknown http user agent)');
    }

    $PAYLOAD = json_decode($_POST['payload']);

    if ( empty($PAYLOAD) ) {
        _ERROR("No payload data for checkout!");
        exit;
    }

    // Check Github signature
    if (! extension_loaded('hash')) {
        _ERROR("Mising 'hash' extension to check the secret code validitity.");
        exit;
    }

    list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + ['', ''];
    if (! in_array($algo, hash_algos(), TRUE)) {
        _ERROR("Hash algorithm '$algo' is not supported.");
        exit;
    }

    $rawPOST = file_get_contents('php://input');
    if ($hash !== hash_hmac($algo, $rawPost, getenv('GIT_TOKEN'))) {
        _ERROR("Github Token does not match!");
        exit;
    }
    // Done Checking signature

    if ( !isset($PAYLOAD->repository->full_name, $PAYLOAD->commits, $PAYLOAD->ref) ) {
        _ERROR("Invalid payload data was received!");
        exit;
    }

    _LOG("Valid payload was received");

}/*}}}*/

function fetchParams ()/*{{{ Get parameters from bitbucket payload now only (REPO) */
{
    global $REPO, $REPO_NAME, $PAYLOAD, $PROJECTS, $BRANCHES;

    // Get repository name:
    $REPO = strtolower($PAYLOAD->repository->full_name);
    if ( empty($PROJECTS[$REPO]) ) {
        _ERROR("Not found repository config for '$REPO'!");
        exit;
    }

    $REPO_NAME = strtolower($PAYLOAD->repository->name);

    $branch_ref = explode('/', $PAYLOAD->ref);
    $branch_name = array_pop($branch_ref);
    if (isset($PROJECTS[$REPO][$branch_name])) {
        array_push($BRANCHES, $branch_name);
        _LOG("Changes in branch '$branch_name' was fetched");
    } else {
        _LOG("Config not found for $branch_name.");
    }

    if ( empty($BRANCHES) ) {
        _LOG("Nothing to update");
    }

}/*}}}*/

function checkPaths ()/*{{{ Check repository and project paths; create them if neccessary */
{
    global $REPO, $CONFIG, $PROJECTS, $BRANCHES;

    // Check for repositories folder path; create if absent
    if ( !is_dir($CONFIG['repositoriesPath']) ) {
        $mode = ( !empty($CONFIG['folderMode']) ) ? $CONFIG['folderMode'] : DEFAULT_FOLDER_MODE;

        if ( mkdir($CONFIG['repositoriesPath'],$mode,true) ) {
            _LOG("Creating repository folder '".$CONFIG['repositoriesPath']." (".decoct($mode).") for '$REPO'");
        }
        else {
            _ERROR("Error creating repository folder '".$CONFIG['repositoriesPath']." for '$REPO'! Exiting.");
            exit;
        }
    }

    // Create folder if absent for each pushed branch
    foreach ( $BRANCHES as $branchName ) {
        if ( !is_dir($PROJECTS[$REPO][$branchName]['deployPath']) ) {
            $mode = ( !empty($CONFIG['folderMode']) ) ? $CONFIG['folderMode'] : DEFAULT_FOLDER_MODE;

            if ( mkdir($PROJECTS[$REPO][$branchName]['deployPath'],$mode,true) ) {
                _LOG("Creating project folder '".$PROJECTS[$REPO][$branchName]['deployPath'].
                    " (".decoct($mode).") for '$REPO' branch '$branchName'");
            }
            else {
                _ERROR("Error creating project folder '".$PROJECTS[$REPO][$branchName]['deployPath'].
                    " for '$REPO' branch '$branchName'! Exiting.");
                exit;
            }
        }
    }

}/*}}}*/

function placeVerboseInfo ()/*{{{ Place verbose log information -- if specified in config */
{
    global $REPO, $REPO_NAME, $CONFIG, $BRANCHES;

    if ( $CONFIG['verbose'] ) {
        _LOG_VAR('CONFIG',$CONFIG);
        _LOG_VAR('REPO',$REPO);
        _LOG_VAR('repoPath',$CONFIG['repositoriesPath'].DIRECTORY_SEPARATOR.$REPO_NAME.'.git');
        _LOG_VAR('BRANCHES',$BRANCHES);
    }
}/*}}}*/

function fetchRepository ()/*{{{ Fetch or clone repository */
{
    global $REPO, $REPO_NAME, $CONFIG;

    // Compose current repository path
    $repoPath = $CONFIG['repositoriesPath'].DIRECTORY_SEPARATOR.$REPO_NAME.'.git';

    // If repository or repository folder are absent then clone full repository
    if ( !is_dir($repoPath) || !is_file($repoPath.DIRECTORY_SEPARATOR.'HEAD') ) {
        _LOG("Absent repository for '$REPO', cloning");

        $cmd = 'cd '.$CONFIG['repositoriesPath'].' && '.$CONFIG['gitCommand'].
            ' clone --mirror git@'.$CONFIG['githubUrl'].':'.$REPO.'.git';
        _LOG_VAR('cmd',$cmd);
        system($cmd, $status);

        if ( $status !== 0 ) {
            _ERROR('Cannot clone repository git@'.$CONFIG['githubUrl'].':'.$REPO.'.git');
            exit;
        }
    }
    // Else fetch changes
    else {
        _LOG("Fetching repository '$REPO'");

        $cmd = 'cd '.$repoPath.' && '.$CONFIG['gitCommand'].' fetch';
        _LOG_VAR('cmd',$cmd);
        system($cmd, $status);

        if ( $status !== 0 ) {
            _ERROR("Cannot fetch repository '$REPO' in '$repoPath'!");
            exit;
        }
    }

}/*}}}*/

function checkoutProject ()/*{{{ Checkout project into target folder */
{
    global $REPO, $REPO_NAME, $CONFIG, $PROJECTS, $BRANCHES, $PAYLOAD;

    // Compose current repository path
    $repoPath = $CONFIG['repositoriesPath'].DIRECTORY_SEPARATOR.$REPO_NAME.'.git';

    // Checkout project files
    foreach ( $BRANCHES as $branchName ) {
        $cmd = 'cd '.$repoPath.' && GIT_WORK_TREE='.$PROJECTS[$REPO][$branchName]['deployPath']
            .' '.$CONFIG['gitCommand'].' checkout -f '.$branchName;
        _LOG_VAR('cmd',$cmd);
        system($cmd, $status);

        if ( $status !== 0 ) {
            _ERROR("Cannot checkout branch '$branchName' in repo '$REPO'!");
            exit;
        }

        if ( !empty($PROJECTS[$REPO][$branchName]['postHookCmd']) ) {
            $cmd = 'cd '.$PROJECTS[$REPO][$branchName]['deployPath'].' && '.$PROJECTS[$REPO][$branchName]['postHookCmd'];
            _LOG_VAR('cmd',$cmd);
            system($cmd, $status);

            if ( $status !== 0 ) {
                _ERROR("Error in post hook command for branch '$branchName' in repo '$REPO'!");
                exit;
            }
        }

        // Log the deployment
        $hash = $PAYLOAD->after;

        _LOG("Branch '$branchName' was deployed in '".$PROJECTS[$REPO][$branchName]['deployPath']."', commit #$hash");

    }
}/*}}}*/
