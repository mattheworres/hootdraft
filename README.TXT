Thank you for downloading PHP Draft! PHP Draft allows commissioners to setup web-based real-time drafts for their fantasy sports leagues. If your leagues do "offline" drafts (instead of using your fantasy provider's online drafting tools), PHP Draft is just for you! You log in to PHP Draft, add managers, determine the number of rounds, style of draft (standard or serpentine), the sport and the name, and then when you're ready to begin drafting. Your managers will come to you once they make a selection, and you enter it. As you enter picks, your managers will see real-time the picks being made, in a large, easy-to-read color-coded draft board.

REQUIREMENTS for PHP Draft:
-Web server with sufficient bandwidth
-PHP 5+ installed (5.3.0+ preferred)
-MySQL and the ability to add a new database
-PHPMyAdmin or some other MySQL utility that allows import of large SQL dump files

To install, follow these steps.

1) Upload the entire PHP Draft project to a web server (will most likely be easiest through FTP).

2) In PHPmyAdmin, create a database called phpdraft (you can rename later). Click on that new database name on the left, and then click the Import tab. Click Browse, and select the database_initialize.sql file from within PHPDraft's SQL directory. Click Go.

3) Open includes/global_setup.php in your favorite IDE or text editor (I use Netbeans PHP).  Following the instructions at the top of that file, edit lines 12 through 16 with your particular settings, and save and upload (overwrite the existing copy) to your web server through FTP.

3A) Please note that if you're using shared hosting, most likely your host has you sharing the same MySQL server with several other users, so they enforce separation of accounts by prepending database names with a certain account-identifier. So, your actual database name could be "johnny_phpdraft" if your account name is "johnny", for instance. Check local listings.

4) If you have encountered no errors, goto the base directory of your PHP Draft install: http://www.yoursite.com/phpdraft/ (If you have errors, please go to http://sourceforge.net/projects/phpdraft/forums/forum/1198073 and create a new thread to get help!)

5) To login as PHPDraft admin, use these default credentials:
Username: admin_commish
Password: password

6) Once logged in, be sure to update the default administrator's name and password by going to the Control Panel tab, and click "Update Your User Profile". From there you can change the password.

7) Enjoy! Any bugs, feature requests, comments or questions please send them to me at http://sourceforge.net/projects/phpdraft/