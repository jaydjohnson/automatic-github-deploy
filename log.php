<?php
/*
    Logging module

    This is a modification from Igor Lilliputten's BitBucket Deployment Script:
    https://bitbucket.org/lilliputten/automatic-bitbucket-deploy

    Which was also based on:
    Automated git deployment' script by Jonathan Nicoal:
    http://jonathannicol.com/blog/2013/11/19/automated-git-deployments-from-bitbucket/

    ---
    Jay Johnson
*/

/*{{{ Global variables */

$_LOG_FILE    = 'log.txt'; // default log file name
$_LOG_ENABLED = false;     // set to 'true' for enabling logging

/*}}}*/

function _LOG_CLEAR ()/*{{{*/
{
    global $_LOG_FILE;

    if ( !empty($GLOBALS['_LOG_ENABLED']) ) {
        // file_put_contents($GLOBALS['_LOG_FILE'], "", LOCK_EX);
        // flush();
        if ( is_file($_LOG_FILE) ) {
            unlink($_LOG_FILE);
        }
    }
}/*}}}*/
function _LOG ($s)/*{{{*/
{
    if ( !empty($GLOBALS['_LOG_ENABLED']) ) {
        $datetime = date('Y.m.d H:i:s');
        file_put_contents($GLOBALS['_LOG_FILE'], $datetime."\t".$s."\n", FILE_APPEND | LOCK_EX);
        flush();
    }
}/*}}}*/
function _LOG_VAR ($s,$p)/*{{{*/
{
    _LOG($s.': '.print_r($p,true));
}/*}}}*/
function _ERROR ($s)/*{{{*/
{
    _LOG('ERROR: '.$s);
}/*}}}*/
