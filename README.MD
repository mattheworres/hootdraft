#Thank you for downloading PHP Draft!
PHP Draft allows commissioners to setup web-based real-time drafts for their fantasy sports leagues. If your leagues do **offline drafts** (instead of using your fantasy provider's online drafting tools), PHP Draft is just for you! 

You log in to PHP Draft, setup your draft and you're ready to begin drafting!

Your managers will notify you once they make a selection, and you enter the draft pick. As you enter picks, your managers will see the picks being made in real-time, on the large color-coded draft board.

*NOTE*: 1.3.0 will be the last official release for PHP Draft version 1. Version 2.0 will be a complete application re-write that will aim to increase app speed, responsiveness and user experience, all while doing so on a modern architecture that's maintainable and extendable. My aim is to have version 2 completed and released by the end of August 2015. Big things are on the horizon for PHP Draft :)

####REQUIREMENTS for PHP Draft 1.3.0:

  - Web server with sufficient bandwidth

  - PHP 5.4+ installed

  - MySQL and the ability to add a new database

  - PHPMyAdmin or some other MySQL utility that allows import of large SQL dump files

##To install, follow these steps:

 1. Unzip entire PHP Draft project to your local machine, and browse to that folder in your file explorer. 

 1. Open /includes/global_setup.php to ensure all of the settings are correct for your server environment. `setDatabaseHostname`, `setDatabaseName`, `setDatabaseUsername` and `setDatabasePassword` are the four lines most commonly edited, but there are other options available to edit.

 1. Upload the entire PHP Draft project to your web server (will most likely be easiest through an FTP client, such as Filezilla).

 1. Using whatever database utility (such as PHPMyAdmin), create a database called `phpdraft` (this must match the name you chose for `setDatabaseName` above).

   1. Please note that if you're using shared hosting, most likely your host has you sharing the same MySQL server with several other users, so they enforce separation of accounts by prepending database names with a certain account-identifier. So, your actual database name could be "johnny_phpdraft" if your account name is "johnny", for instance. Check local listings.

 1. Using an import feature, or a SQL execution method of your database tool, execute the SQL contained in `/sql/database_initialize.sql`

 1. If you have encountered no errors, goto the base directory of your PHP Draft install: http://www.yoursite.com/phpdraft/ (If you have errors, please go to http://sourceforge.net/projects/phpdraft/forums/forum/1198073 and create a new thread to get help)

 1. To login as PHPDraft admin, use these default credentials:
Username: admin_commish
Password: password

 1. Once logged in, *update the default commisioner login by going to the Control Panel tab, and click `Update Your User Profile`*. From there you can change the password.

 1. If you kept the Autocomplete feature on, you will need to upload the most up to date roster CSVs. As the logged in commissioner, go to the Control Panel tab, then click `Update Pro Players Database`. Select the league(s) you wish to add players for the autocomplete, select the most up to date `.CSV` and upload.

 1. Enjoy! Any bugs, feature requests, comments or questions please send them to me at http://sourceforge.net/projects/phpdraft/ , or tweet me on twitter: http://twitter.com/mattheworres
 
###Notes

 1. Version 1.3.0 adds custom picktimers to your drafts (disabled by default). You define times per pick (for the entire draft or a per-round basis) and a timer dialog will appear on the draft board. Hopefully this will help keep your drafts quick!

 1. Additionally the pick timer dialog can auto-play .MP3 sounds when the timer runs out! Find some funny sounds and upload them. To enable this, edit `/js/public_draft_board.js` around line 302. Follow the instructions to add an array of filepaths that point to your MP3 files.

 1. Version 1.3.0 also adds a 'player already drafted' check based on first and last player name. If one or more drafted players match, a dialog with all matches will be displayed, and will ask you if you wish to continue entering the pick. 

 Since the check performs a `string`-based match on first AND last names, this check will only be effective if the autocomplete feature is also used. 

 1. There are two separate NFL CSV files in the /resources to update the autocomplete database - one is "standard", the other "extended", which includes just about all NFL players (except punters, because theyre never tracked in fantasy football). If you are drafting for a league that drafts defensive players, offensive linemen, you'll want to first Enable Extended NFL positions in global_setup.php, and THEN go to Control Panel - Update Pro Players Database and upload ProPlayers_NFLExtended_xxxx.csv (where xxxx is the current year).

#Changelog
###v1.3.0
**Official Release**

**ADDED**: PHP autoloading to the entire app. This should help speed the app up in most instances - previously every file was being loaded on every request, but now autoloading allows us to lazy-load files as they're required.

**ADDED**: Pick timers feature. Ensure your managers are making picks in a timely manner. Specify pick times for the entire draft, or on a per-round basis. Play funny/insulting sounds when the timer runs out (works on Chromecast, too!)

**ADDED**: Add "already drafted" check to add pick calls to ensure one player isn't drafted twice.

**UPDATED**: MLB 2015 rosters CSV.

###v1.2.2
**Critical Fixes Release**

**FIXED**: Fixed an issue with the last pick of the draft not being detected, resulting in a non-error being thrown instead of the draft being flipped to ended.

###v1.2.0
**Official Release**

**FIXED**: REWROTE PUBLIC DRAFT BOARD. Finally, a solution I am happy with. I also re-did the styling of the board and am fairly happy with how it looks. While the board still does polling (checks every X seconds to see if there's been another pick), it only downloads data from the server that it needs. Using a counter, it's able to grab any updated picks and new ones. Before, if you edited a pick you'd have to wait until you added a new one before it showed up, but now it will show up on everyone's draft board as soon as possible.

**FIXED**: There was an issue with MLB drafts in particular where certain player positions didn't have colors. It was a CSS issue - the position was the class name, and "1B" or "2B" are invalid CSS classes by default. Added a work-around for these instances.

**UPDATED**: Add jQuery CDN lookup, but fall back to load local copy. Also updated jQuery/jQuery UI to latest versions

**UPDATED**: Updated a few portions of the site that do simple data updates to just use AJAX, like Manager edits and the "status change button"

**UPDATED**: Refactored code to use a "service" pattern. Models were pretty hefty so moving them into a separate service layer should help. Also tried to do some better error handling in the code rather than relying on return values.


###v1.1.1
**Critical Fixes Release**

**FIXED**: Autocomplete issue - did not properly use ! (PHP Not operator) when checking the UseComplete flag

**UPDATED**: NFL 2012 CSV rosters. Control Panel - Update Pro Players and upload the new 2012 CSV files (found inside /Resources folder)

**ADDED**: Flag to use extended NFL rosters (that includes defensive players). By default its false (most fantasy leagues only track offensive players), but you can enable it. If using defensive players, it's suggest to also upload the "extended" pro players CSV file for the NFL, as this includes all defensive players as well (and offensive linemen, etc.)

###v1.1.0 
**Official Release**

**ADDED**: Trades feature

**ADDED**: Autocomplete pick entry feature

**ADDED**: Customizable autocomplete tables

**FIXED**: "Undefined index" errors on high error level reporting settings

**FIXED**: Updated jQuery from 1.6.2 to 1.7.1

**FIXED**: NBA position marked as "SH" instead of "SG"

**REQUIRED**: DB Migration from previous versions (new tables, updated values): use /sql/1.0_to_1.1_migration.sql if coming from 1.0.0 - 1.0.4

###v1.0.4 

**Critical Fixes Release**

**FIXED**: Minor defects related to commissioner pick entry

**FIXED**: Move several (not all) class includes to includes/global_setup.php

###v1.0.3

**Critical Fixes Release**

**FIXED**: Several minor to severe fixes.

###v1.0.2

**Critical Fixes Release**

**ADDED**: PDO replaces MySQL driver

**FIXED**: Several critical fixes

###v1.0.1

**Critical Fixes Release**

**FIXED**: Includes a few minor to severe fixes.

###v1.0.0
**Official Release**

**ADDED**: New Javascript-driven add managers UI

**ADDED**: New streamlined pick entry screen

**FIXED**: Re-write of entire codebase into MVP code pattern, OOP

###v0.9.0
**Beta Release**

**ADDED**: Everything (initial public release!)