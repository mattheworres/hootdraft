Thank you for downloading PHP Draft! PHP Draft allows commissioners to setup web-based real-time drafts for their fantasy sports leagues. If your leagues do "offline" drafts (instead of using your fantasy provider's online drafting tools), PHP Draft is just for you! You log in to PHP Draft, add managers, determine the number of rounds, style of draft (standard or serpentine), the sport and the name, and then when you're ready to begin drafting. Your managers will come to you once they make a selection, and you enter it. As you enter picks, your managers will see real-time the picks being made, in a large, easy-to-read color-coded draft board.

REQUIREMENTS for PHP Draft 1.1.0:
-Web server with sufficient bandwidth
-PHP 5+ installed (5.3.0+ preferred)
-MySQL and the ability to add a new database
-PHPMyAdmin or some other MySQL utility that allows import of large SQL dump files

To install, follow these steps.

1) Unzip entire PHP Draft project to your local machine, and browse to that folder in your file explorer.

2) Open the /includes/ folder up, and open global_setup.php for editing. Here you will find all of the key settings in order to make PHP Draft run. The variables you'll need to change will 99% of the time be above the line that reads "DO NOT EDIT BELOW THIS LINE". Ensure the database username/password are updated, then save the file and exit your text editor.

3) Upload the entire PHP Draft project to a web server (will most likely be easiest through an FTP client, such as Filezilla).

4) In PHPmyAdmin, create a database called phpdraft (you can rename later). Click on that new database name on the left, and then click the Import tab. Click Browse, and select the database_initialize.sql file from within PHPDraft's /sql/ directory. Click Go.

4A) Please note that if you're using shared hosting, most likely your host has you sharing the same MySQL server with several other users, so they enforce separation of accounts by prepending database names with a certain account-identifier. So, your actual database name could be "johnny_phpdraft" if your account name is "johnny", for instance. Check local listings.

4) If you have encountered no errors, goto the base directory of your PHP Draft install: http://www.yoursite.com/phpdraft/ (If you have errors, please go to http://sourceforge.net/projects/phpdraft/forums/forum/1198073 and create a new thread to get help!)

5) To login as PHPDraft admin, use these default credentials:
Username: admin_commish
Password: password

6) Once logged in, BE SURE TO update the default commisioner login by going to the Control Panel tab, and click "Update Your User Profile". From there you can change the password.

7) Enjoy! Any bugs, feature requests, comments or questions please send them to me at http://sourceforge.net/projects/phpdraft/

NOTE 1) Version 1.1 adds a new autocomplete feature to suggest players when you type 2 or more characters on the Add Pick screen. If you wish, you can disable this feature by editing changing $PHPD->setUseAutocomplete to false. You'll more than likely want this great feature on, though.

NOTE 2) Version 1.1 also adds a new feature to help keep your PHP Draft autocomplete up-to-date with the latest NFL, NHL, MLB and NBA rosters so you have the most up-to-date player information being suggested to you. I will make a best effort to provide new updated CSVs for each season, but if for whatever reason I cannot or you have a better one, there is an option to update your database in the commissioner control panel by uploading a CSV file. Following the instructions on that page will delete the stock player data and replace it with what you provide. Depending on the size of your CSV, your connection speed, et. al., if you encounter errors uploading you may need to disable the default timeout PHP provides for operations. Unfortunately, this function call requires relatively high permissions, so it may not work for you. Good luck.



***CHANGELOG***
v1.1.0 - Official Release
ADDED: Trades feature
ADDED: Autocomplete pick entry feature
ADDED: Customizable autocomplete tables
FIXED: "Undefined index" errors on high error level reporting settings
FIXED: Updated jQuery from 1.6.2 to 1.7.1
FIXED: NBA position marked as "SH" instead of "SG"
REQUIRED: DB Migration from previous versions (new tables, updated values): use /sql/1.0_to_1.1_migration.sql if coming from 1.0.0 - 1.0.4

v1.0.4 - Critical Fixes Release
FIXED: Minor defects related to commissioner pick entry
FIXED: Move several (not all) class includes to includes/global_setup.php

v1.0.3 - Critical Fixes Release
FIXED: Several minor to severe fixes.

v1.0.2 - Critical Fixes Release
ADDED: PDO replaces MySQL driver
FIXED: Several critical fixes

v1.0.1 - Critical Fixes Release
Includes a few minor to severe fixes.

v1.0.0 - Official Release
ADDED: New Javascript-driven add managers UI
ADDED: New streamlined pick entry screen
FIXED: Re-write of entire codebase into MVP code pattern, OOP

v0.9.0 - Beta Release
ADDED: Everything (initial public release!)