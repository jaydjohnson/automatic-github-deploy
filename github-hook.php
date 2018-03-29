<?php
/*
    GitHub Webhook interface

    This is a modification from Igor Lilliputten's BitBucket Deployment Script:
    https://bitbucket.org/lilliputten/automatic-bitbucket-deploy

    Which was also based on:
    Automated git deployment' script by Jonathan Nicoal:
    http://jonathannicol.com/blog/2013/11/19/automated-git-deployments-from-bitbucket/

    ---
    Jay Johnson
*/

// Initalize:
require_once('log.php');
require_once('bitbucket.php');

// Load config:
include('config.php');

// Let's go:
initConfig(); // Initialize repo configs
initLog(); // Initialize log variables
initPayload(); // Get posted data
fetchParams(); // Get parameters from bitbucket payload (REPO)
checkPaths(); // Check repository and project paths; create them if necessary
placeVerboseInfo(); // Place verbose log information if specified in config
fetchRepository(); // Fetch or clone repository
checkoutProject(); // Checkout project into target folder
