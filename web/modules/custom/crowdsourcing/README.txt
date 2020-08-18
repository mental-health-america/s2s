CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Additional Files

INTRODUCTION
------------

The Crowdsourcing module gives the ability to comment on thoughts/questions.

REQUIREMENTS
------------

This module requires the following modules:

 * crowdsourcing_comment (a sub-module of crowdsourcing)

INSTALLATION
------------

 * drush en crowdsourcing crowdsourcing_comment
 * drush ct

ADDITIONAL FILES
----------------

 * Place 'field' and 'comment' directories under 's2s/templates'.

CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions and allow "Anonymous/Authenticated User" to:

   - Edit own comments
   - Post comments
   - Skip comment approval
   - View comments

