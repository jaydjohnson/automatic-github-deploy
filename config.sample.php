<?php
/*
    Sample Config file

    This is a modification from Igor Lilliputten's BitBucket Deployment Script:
    https://bitbucket.org/lilliputten/automatic-bitbucket-deploy

    Which was also based on:
    Automated git deployment' script by Jonathan Nicoal:
    http://jonathannicol.com/blog/2013/11/19/automated-git-deployments-from-bitbucket/

    ---
    Jay Johnson
*/

// Base tool configuration:
$CONFIG = [
    'gitCommand'       => 'git',                   // Git command, *REQUIRED*
    'githubUrl'        => 'github.com',            // URL for github server
    'repositoriesPath' => '/path/to/repositories', // Folder containing all repositories, *REQUIRED*
    'log'              => true,                    // Enable logging, optional
    'logFile'          => 'github.log',            // Logging file name, optional
    'logClear'         => true,                    // clear log each time, optional
    'verbose'          => true,                    // show debug info in log, optional
    'folderMode'       => 0755,                    // creating folder mode, optional
];

// List of deployed projects:
$PROJECTS = [
    'githubUsername/repoName-1' => [             // The key is a github repository full name *REQUIRED*
        'branchName' => [                        // Name of the branch you want to deploy *REQUIRED*
            'deployPath'  => '/deploy_path',     // Path to deploy project, *REQUIRED*
            'postHookCmd' => '',                 // command to execute after deploy, optional
        ],
        // Add other branches here
    ],

    // Add other Projects/Repositories here
];
