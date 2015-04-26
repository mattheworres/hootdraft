#Thank you for downloading PHP Draft!
PHP Draft allows commissioners to setup web-based real-time drafts for their fantasy sports leagues. If your leagues do **offline drafts** (instead of using your fantasy provider's online drafting tools), PHP Draft is just for you! 

You log in to PHP Draft, add managers, select the number of rounds, style of draft (standard or serpentine) and then you're ready to begin drafting!

Your managers will notify you once they make a selection, and you enter the draft pick. As you enter picks, your managers will see the picks being made in real-time, on the large color-coded draft board.

####REQUIREMENTS for PHP Draft 2.0.0:

  - Web server with the ability to edit `php.ini` file for your installation

  - If server is Apache, need ability to use `.htaccess` to enable/use `mod_rewrite`

  - PHP 5.4+ installed

  - SQL database compatible with Propel 2

  - PHPMyAdmin or some other MySQL utility that allows import of large SQL dump files

  - TODO - finish

##To install, follow these steps:

 1. Set the following variables in your PHP.ini:

	phpdraft.cfg.DB_USER = ‘your_database_username’
	phpdraft.cfg.DB_PASS = ‘your_database_password’

 1. Unzip entire PHP Draft project to your local machine, and browse to that folder in your file explorer. 

 1. Open the /includes/ folder up, and open global_setup.php for editing. Here you will find all of the key settings in order to make PHP Draft run. The variables you'll need to change will 99% of the time be above the line that reads "DO NOT EDIT BELOW THIS LINE". Ensure the database username/password are updated, then save the file and exit your text editor.

 1. Upload the entire PHP Draft project to a web server (will most likely be easiest through an FTP client, such as Filezilla).

 1. In PHPmyAdmin, create a database called phpdraft (you can rename later). Click on that new database name on the left, and then click the Import tab. Click Browse, and select the database_initialize.sql file from within PHPDraft's /sql/ directory. Click Go.

   1. Please note that if you're using shared hosting, most likely your host has you sharing the same MySQL server with several other users, so they enforce separation of accounts by prepending database names with a certain account-identifier. So, your actual database name could be "johnny_phpdraft" if your account name is "johnny", for instance. Check local listings.

 1. If you have encountered no errors, goto the base directory of your PHP Draft install: http://www.yoursite.com/phpdraft/ (If you have errors, please go to http://sourceforge.net/projects/phpdraft/forums/forum/1198073 and create a new thread to get help!)

 1. To login as PHPDraft admin, use these default credentials:
Username: admin_commish
Password: password

 1. Once logged in, BE SURE TO update the default commisioner login by going to the Control Panel tab, and click "Update Your User Profile". From there you can change the password.

 1. Enjoy! Any bugs, feature requests, comments or questions please send them to me at http://sourceforge.net/projects/phpdraft/
 
###Notes

 1. Version 1.1 adds a new autocomplete feature to suggest players when you type 2 or more characters on the Add Pick screen. If you wish, you can disable this feature by editing changing $PHPD->setUseAutocomplete to false. You'll more than likely want this great feature on, though.

 1. Version 1.1 also adds a new feature to help keep your PHP Draft autocomplete up-to-date with the latest NFL, NHL, MLB and NBA rosters so you have the most up-to-date player information being suggested to you. I will make a best effort to provide new updated CSVs for each season, but if for whatever reason I cannot or you have a better one, there is an option to update your database in the commissioner control panel by uploading a CSV file. Following the instructions on that page will delete the stock player data and replace it with what you provide. Depending on the size of your CSV, your connection speed, et. al., if you encounter errors uploading you may need to disable the default timeout PHP provides for operations. Unfortunately, this function call requires relatively high permissions, so it may not work for you. Good luck.

 1. There are two separate NFL CSV files in the /resources to update the autocomplete database - one is "standard", the other "extended", which includes just about all NFL players (except punters, because theyre never tracked in fantasy football). If you are drafting for a league that drafts defensive players, offensive linemen, you'll want to first Enable Extended NFL positions in global_setup.php, and THEN go to Control Panel - Update Pro Players Database and upload ProPlayers_NFLExtended_xxxx.csv (where xxxx is the current year).

#Changelog
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