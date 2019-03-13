
# Automatic deployment for github.com web-based projects

Based on [Automatic BitBucket Deploy script](https://bitbucket.org/lilliputten/automatic-bitbucket-deploy) by Igor Lilliputten which was based on [«Automated git deployment» script](http://jonathannicol.com/blog/2013/11/19/automated-git-deployments-from-bitbucket/) by [Jonathan Nicoal](http://jonathannicol.com/).

Version 0.1.0 Last changes 2018.03.29

## Features

- Support for multiple projects and branches. See array `$PROJECTS` in **config.sample.php**.
- Optional fetching or cloning repositiories demand on their presence.
- Create Project and repository folders automaticaly if they do not exists.
- Post hook command execution.

## Requirements

- PHP 5.3+
- Git installed
- Shell access
- PHP system function
- SSH key pair for github created with **empty** passphrase
- Secret key added to your webhook

## Installation

You will need to set up a Deploy Key on the server add it to your repository.

Also you must set up an environment variable [GIT_TOKEN](https://developer.github.com/webhooks/securing/) which will help secure your webhook.

In your Github Repository settings, set your hooks Payload URL to point to github-hook.php.  You should probably also rename this file to something unique.
