# Deploying to RedHat Openshift

PHPDraft can be deployed to cloud-based services like [RedHat OpenShift](https://openshift.redhat.com).

This is optimal for an app like PHPDraft - OpenShift is free, cloud based (so it's blazing fast), and it's relatively simple to get up and running. This how-to assumes that you have downloaded the latest ZIP archive release of PHP Draft, have extracted the archive to a local directory and have a passing familiarity with the steps contained in [INSTALL.md](INSTALL.md).

Here's a list of things that you will need to possess or acquire before starting:

- Install [**Git**](https://git-scm.com/) as well as SSH keys for secure repository access. [Github](https://help.github.com/articles/generating-an-ssh-key/) is a great resource for newbies to SSH keys.
- An account with [**OpenShift**](https://openshift.redhat.com) containing the **PHP 5.4, MySQL 5.5** and **phpMyAdmin 4.0** cartridges added to the new app.
- A verified account with [**Mailgun**](http://www.mailgun.com) - for handling SMTP email
- An account with [**Google Recaptcha**](https://www.google.com/recaptcha/) and a site added for your new install
- OPTIONAL A GUI client like [GitExtensions](http://gitextensions.github.io/) (Windows only) or [SourceTree](https://www.sourcetreeapp.com/) (Windows/Mac) may be helpful if you're not comfortable on the commandline.

## The Install Process

1. Execute `/api/Domain/Migrations/initialize.sql` on the `phpdraft` database. This is done through the **OpenShift** console with **phpMyAdmin**

1. Follow **step 5** in [INSTALL.md](INSTALL.md), but there are a few differences due to the cloud-based nature of OpenShift when editing your `appsettings.php`:

    -`DB_HOST` needs set to `getenv('OPENSHIFT_MYSQL_DB_HOST')`

    -`DB_PORT` needs set to `getenv('OPENSHIFT_MYSQL_DB_PORT')`

    -`DB_USER` needs set to `getenv('OPENSHIFT_MYSQL_DB_USERNAME')`

    -`DB_PASS` needs set to `getenv('OPENSHIFT_MYSQL_DB_PASSWORD')`

    -`AUTH_KEY` needs set to `getenv('OPENSHIFT_SECRET_TOKEN')`

    -`RECAPTCHA_SECRET` needs set to your Google reCAPTCHA 2 secret key*

    -`MAIL_SERVER` needs set to `smtp.mailgun.org`

    -`MAIL_USER` needs set to your Mailgun default SMTP login

    -`MAIL_PASS` needs set to your Mailgun default password

    -`MAIL_PORT` needs set to `25`

    -`MAIL_USE_ENCRYPTION` needs set to `true`

    -`MAIL_ENCRYPTION` needs set to `tls`

    -`APP_BASE_URL` needs set to http://**{{your_openshift_subdomain}}**.rhcloud.com

    -`API_BASE_URL` needs set to http://**{{your_openshift_subdomain}}**.rhcloud.com/api

1. Open `js/config.js` and update two values to match your installation:

    - **apiEndpoint** needs set to http://**{{your_openshift_subdomain}}**.rhcloud.com/api/
    - **recaptchaPublicKey** should contain the *public key* portion of your Google reCAPTCHA 2 key

1. Open `.htaccess` and remove lines 14 and 15. For some reason the HTTPS redirect doesn't resolve properly. These lines tell Apache to force the site to run off of HTTPS instead of HTTP.

1. Clone your **OpenShift** app's repository using the SSH URL provided on the dashboard (will start with `ssh://`). Clone it into a different directory from the one you extracted PHP Draft into.

1. Leave the `.git` and `.openshift` folders, delete the rest of the contents in the Openshift repository folder.

1. Copy the following directory and files from your local PHPDraft into your Openshift repository folder:

    -`/api`

    -`/css`

    -`/fonts`

    -`/images`

    -`/js`

    -`/vendor`

    -`.htaccess`

    -`appsettings.php`

    -`index.html`

1. Within the Openshift repository folder, commit all changes on the working directory on `master`, and then push your changes with `git push origin master`. OpenShift's Git server should accept the changes (granted that your SSH keys are in working order), and will update you via the commandline as to its status (it will restart PHP, MySQL and phpMyAdmin after its own build process).

1. Load http://**{{your_openshift_subdomain}}**.rhcloud.com in the browser, and PHP Draft should load! Follow the **last 4 steps** in [INSTALL.md](INSTALL.md) to finish your installation off. Enjoy!

Any time you need to update the code, you can commit and push to the SSH-secured OpenShift Git repository.

Additionally, if you run into issues, OpenShift provides **shell access** which allows you to traverse your site's directory structure via the commandline. This is quite tricky to get correct due to the nature of SSH keys (especially on Windows), but using [PuTTY](http://www.chiark.greenend.org.uk/~sgtatham/putty/download.html) is possible.

App logs are located at `~/app-root/logs` (I only needed to peruse `php.log` using `vim` but YMMV), and the current version of the app is located at `~/app-deployments/current/repo/`. If I were you, I'd be **VERY** mindful of editing these files on-server, but just use the shell access as a read-only sanity check.

Any issues with installation, hit me up on [Twitter](http://twitter.com/mattheworres) or enter an issue on Github.