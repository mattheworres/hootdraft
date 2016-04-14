# Changelog
## v2.0.3
**Hotfix Release**

Fixed issue with missing MLB player data (sorry Joey Votto, Anthony Rizzo, Josh Harrison et. al.)

Fixed issues #12 and #13

Fixed a few random issues related to Extended NFL leagues. Saved myself entering a Github issue :)

Also updated frontend and backend dependencies, including the libraries to use JSON web tokens with Silex.

## v2.0.2
**Hotfix Release**

Fixed issue #9 with UTC dates not displaying in local timezones properly.

Updated 2016 MLB player CSV data for the soon-to-be-here season.

## v2.0.1
**Hotfix Release**

Fixed a minor version where admins couldn't see completed password-protected drafts.

Also reworked the project a little in order to allow for MySQL 5.5 to be used (in order to allow OpenShift deployments).

Rather than including appsettings.php in the directory above, it now stays in the base directory.

CSV data wasn't updated, but included in this release still. Will update MLB data in about a month (more free agent signings to go still)

## v2.0.0
**Official Release**

**COMPLETE PROJECT REWRITE**

Everything you loved about version 1.3.0 and then some.

SaaS-friendly user registration/authentication/management.
Angular front end utilizing Twitter Bootstrap 3 for responsive layouts (read: works great on smartphones!)
Streamlined clean user interface designed from the bottom up to be a quick and easy experience

Project development is now considerably more complicated for beginners. Please see [DEVELOPER.md](DEVELOPER.md) for details.

Also, thanks to @JustinPyvis for the Super Rugby sports player data, and @YamieSquirrel for additional QA help!

## v1.3.0
**Official Release**

**ADDED**: PHP autoloading to the entire app. This should help speed the app up in most instances - previously every file was being loaded on every request, but now autoloading allows us to lazy-load files as they're required.

**ADDED**: Pick timers feature. Ensure your managers are making picks in a timely manner. Specify pick times for the entire draft, or on a per-round basis. Play funny/insulting sounds when the timer runs out (works on Chromecast, too!)

**ADDED**: Add "already drafted" check to add pick calls to ensure one player isn't drafted twice.

**UPDATED**: MLB 2015 rosters CSV.

## v1.2.0
**Official Release**

**FIXED**: REWROTE PUBLIC DRAFT BOARD. Finally, a solution I am happy with. I also re-did the styling of the board and am fairly happy with how it looks. While the board still does polling (checks every X seconds to see if there's been another pick), it only downloads data from the server that it needs. Using a counter, it's able to grab any updated picks and new ones. Before, if you edited a pick you'd have to wait until you added a new one before it showed up, but now it will show up on everyone's draft board as soon as possible.

**FIXED**: There was an issue with MLB drafts in particular where certain player positions didn't have colors. It was a CSS issue - the position was the class name, and "1B" or "2B" are invalid CSS classes by default. Added a work-around for these instances.

**UPDATED**: Add jQuery CDN lookup, but fall back to load local copy. Also updated jQuery/jQuery UI to latest versions

**UPDATED**: Updated a few portions of the site that do simple data updates to just use AJAX, like Manager edits and the "status change button"

**UPDATED**: Refactored code to use a "service" pattern. Models were pretty hefty so moving them into a separate service layer should help. Also tried to do some better error handling in the code rather than relying on return values.


## v1.1.1
**Critical Fixes Release**

**FIXED**: Autocomplete issue - did not properly use ! (PHP Not operator) when checking the UseComplete flag

**UPDATED**: NFL 2012 CSV rosters. Control Panel - Update Pro Players and upload the new 2012 CSV files (found inside /Resources folder)

**ADDED**: Flag to use extended NFL rosters (that includes defensive players). By default its false (most fantasy leagues only track offensive players), but you can enable it. If using defensive players, it's suggest to also upload the "extended" pro players CSV file for the NFL, as this includes all defensive players as well (and offensive linemen, etc.)

## v1.1.0 
**Official Release**

**ADDED**: Trades feature

**ADDED**: Autocomplete pick entry feature

**ADDED**: Customizable autocomplete tables

**FIXED**: "Undefined index" errors on high error level reporting settings

**FIXED**: Updated jQuery from 1.6.2 to 1.7.1

**FIXED**: NBA position marked as "SH" instead of "SG"

**REQUIRED**: DB Migration from previous versions (new tables, updated values): use /sql/1.0_to_1.1_migration.sql if coming from 1.0.0 - 1.0.4

## v1.0.4 

**Critical Fixes Release**

**FIXED**: Minor defects related to commissioner pick entry

**FIXED**: Move several (not all) class includes to includes/global_setup.php

## v1.0.3

**Critical Fixes Release**

**FIXED**: Several minor to severe fixes.

## v1.0.2

**Critical Fixes Release**

**ADDED**: PDO replaces MySQL driver

**FIXED**: Several critical fixes

## v1.0.1

**Critical Fixes Release**

**FIXED**: Includes a few minor to severe fixes.

## v1.0.0
**Official Release**

**ADDED**: New Javascript-driven add managers UI

**ADDED**: New streamlined pick entry screen

**FIXED**: Re-write of entire codebase into MVP code pattern, OOP

## v0.9.0
**Beta Release**

**ADDED**: Everything (initial public release!)