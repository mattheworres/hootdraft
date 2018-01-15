# Installing PHP Draft

 1. Download the latest release from the Github releases page (https://github.com/mattheworres/phpdraft/releases - do NOT download the source code ZIP), extract the project to a temporary local directory.

 1. Create an empty MySQL database (as well as a corresponding MySQL user that has full access to this database).

 1. Additionally, you should have a webserver (Apache, Nginx or IIS) setup and ready to serve HTML and PHP. You will need to have the following accounts and credentials ready before beginning setup and deployment:
     - The IP address or DNS name of the webserver the app will be served from (SSH must be enabled & keys created - see [this article](https://www.digitalocean.com/community/tutorials/how-to-set-up-ssh-keys--2) for help)
     - The absolute file path to the base directory the application will be stored on the webserver
         - **IMPORTANT** Requests from Apache/Nginx/IIS should be pointed to a symbolic link called `current` from within this path (`/var/www/yoursite.com/current`, for example) which will be auto-created by the PHPDraft Deployer.
		 - Within the `yoursite.com` folder on your server, multiple folders will (eventually, after multiple deployments) exist, each named a sequential number corresponding to the "build" contained within. The symbolic link (created on Unix-like systems with the tool `ln`) will be automatically updated to point to the latest build during deployment.
	 - Both the `mcrypt` and `pdo_mysql` PHP extensions enabled. To check, run `php -m | grep mcrypt` and `php -m | grep pdo_mysql` - if each command returns `mcrypt` and `pdo_mysql` respectively, they are installed.
	 - [Yarn](https://yarnpkg.com/lang/en/docs/install) installed on your server
	 - Ensure the user the SSH key is associated with has file ownership permissions on the `yoursite.com` folder described above
     - SSH login with sufficient permissions to manipulate the base directory of the application (on the server)
     - MySQL user account with which the application will access the database you created above
     - A long, randomly generated JWT seed token (see 504-bit WPA keys on https://randomkeygen.com/ )
     - Google Recaptcha 2 ( https://www.google.com/recaptcha ) registration, both public and private keys
     - SMTP server credentials (like https://www.mailgun.com )

 1. Open a command prompt or terminal on your computer, and navigate to the temporary local directory (same folder as `index.html`). Type **`vendor/bin/dep setup`** and hit enter to begin the automated PHP Draft settings wizard. It will ask you questions in the terminal window, type in the answers and hit enter to save.

 1. If your PHP Draft install will be at the base of the domain or subdomain (as in `sub.example.com` or `www.example.com`), **you may skip this step**. If your base URL looks like www.example.com/phpdraft, **do the following**:

    - Open `index.html` and find this HTML: `<base href="/">` and change it to match the directory path after the base domain. So if your base URL is `www.example.com/phpdraft`, then you must edit the HTML like this: `<base href="/phpdraft/">`

 1. Make any necessary changes to your `.htaccess` (Unix-like) or `web.config` (Windows) config files.
     - If you updated base `href` tag in `index.html` above, you'll need to edit line 5 of `.htaccess` like so: `RewriteBase /phpdraft`

 1. (Optional) Once the settings wizard has finished, type **`vendor/bin/dep backup`** to take the settings files the wizard created for you in the last step, and back them up in a *secure folder* elsewhere on this machine.

>This is helpful when upgrading the app in the future, as you can import these settings before deploying the application. A good suggestion is within your logged in user's home folder (along side Documents and Downloads folders, for example) in a folder called "phpdraft_settings". If you have more than 1 install, you would need to keep them in separate folders within `phpdraft_settings`

 1. **Time to deploy!** For best results, ensure your current machine is on hardwired ethernet instead of wireless.

 1. Type **`vendor/bin/dep deploy`** and wait as the deployer automatically uploads the app, installs dependencies and updates the app database. If you receive a success message from the deployer, continue on!

     - Check the project Gitter chat, or ping Matt on [Twitter](https://twitter.com/mattheworres) for help in this process.
	 - *Tip*: If you encounter an error (exception) during deployment, you will likely need to un-lock the deployment manually before re-deploying. Type **`vendor/bin/dep deploy:unlock`** to do so, which may ask for your SSH user's password (YMMV).

 1. Go to the base URL of your PHP Draft install and register a new user account with your email address.

 1. In the `phpdraft` database, find the row in the `users` table that corresponds to your newly created account. Make three edits:

    - Change the value in the `enabled` column to `1` (which will enable your account)
    - Change the value in the `roles` column to `ROLE_ADMIN,ROLE_COMMISH` (which will make your account both a site administrator and a commissioner)
    - Change the value in the `verificationKey` to `NULL` (this deletes the key contained in the new user email sent to your email address - you don't need to validate your own email :) )

 1. Log in with your email address and password.

 1. In the top header bar, click on **Admin Stuff** then select **Player Data**. Using the CSV files contained in the `/resources/` directory, update each sport with the matching CSV file:

  Sport                      | CSV File
  -------------              | -------------
  Football (NFL)             | ProPlayers_NFLStandard_20xx.csv
  Football - Extended Rosters (NFL)    | ProPlayers_NFLExtended_20xx.csv
  Baseball (MLB)             | ProPlayers_MLB_20xx.csv
  Basketball (NBA)             | ProPlayers_NBA_20xx.csv
  Hockey (NHL)               | ProPlayers_NHL_20xx.csv
  Rugby (Super15)            | ProPlayers_Super15_20xx.csv

##### Congratulations! You have installed PHP Draft. Enjoy!

#### Common Issues

1. If you are encountering issues while `SaltService` attempts to create password salts (in new user registration and forgotten password processes), your installation of PHP may be missing the `mcrypt` extension. Depending on what type of PHP you have installed your mileage may vary, but `SaltService` requires the extension to be installed and enabled (on Ubuntu, a quick `sudo apt-get install php7.0-mcrypt` does the trick. Run `php -m | grep mcrypt` afterwards to verify that `mcrypt` is listed. If nothing returns - it's not installed, try again :( ).

1. If you encounter issues at the end of the draft (like the draft ends on an error), and you also cannot manually generate the draft statistics for that draft through the Admin option on the site, it's likely due to the fact that MySQL's `ONLY_FULL_GROUP_BY` option is enabled and is breaking the SQL query/queries related to draft stats. See this StackOverflow thread for some help: http://stackoverflow.com/a/36033983/324527 (H/T to user `Eyo Okon Eyo`)
