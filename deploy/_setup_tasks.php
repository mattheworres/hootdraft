<?php
namespace Deployer;

desc('PHP Draft: Use a wizard to set important variables');
task('setup', [
    'phpdraft:setupconfirm',
    'phpdraft:asksetupquestions',
    'phpdraft:copyfiles',
    'phpdraft:replacevalues',
    'backup',
    'phpdraft:setupsuccess'
]);

desc('PHP Draft: Offer to backup current settings for future upgrade imports');
task('backup', function() {
	if (file_exists("js/config.js") == false) {
		writeln("<error>Looks like js/config.js doesn't exist - I can't back up your settings.</error>\n");
		writeln("<comment>Ensure you have downloaded a compiled release from https://github.com/mattheworres/phpdraft/releases</comment>\n");
		writeln("<comment>Or, if you are building from sourcecode, consult the wiki on how to properly prepare a release.</comment>\n");
		throw new \Exception("PHP Draft is not in a exportable state (use downloads from Releases on Github)");
	}

  writeln("\n\n<info>Looking great! Hey, if you want I can back up these snazzy settings files "
    ."I just created for you. This will make it WAY easier to update PHP Draft in the future!</info>\n");
  $answer = askConfirmation("Should I back up your settings for you?", false);

  if($answer == true) {
    writeln("\n\n<info>Awesome! Wherever you tell me to back your settings up to, you should make sure "
      ."that only your user has permissions to view the file (if the server you're installing on "
      ."is more important, you should probably ensure your disk is encrypted, too).</info>\n");
    $backup_location = ask("Great! Where should I back your settings up to (ensure the directory exists first)?",
      "~/phpdraft_settings");

    runLocally("cp appsettings.php $backup_location/appsettings.php");
    runLocally("cp js/config.js $backup_location/config.js");
    runLocally("cp phinx.yml $backup_location/phinx.yml");
    runLocally("cp deploy.php $backup_location/deploy.php");
		runLocally("cp .htaccess $backup_location/.htaccess");
		runLocally("cp index.html $backup_location/index.html");
    runLocally("cp web.config $backup_location/web.config");
  }
});

desc('Confirm that the user wants to go through with this; warn of irreversible nature');
task('phpdraft:setupconfirm', function() {
  $message = "Are you sure you wish to begin setup? This will OVERWRITE "
  ."any values stored within appsettings.php, deploy.php and js/config.js files!!";

  $answer = askConfirmation($message, false);

  if($answer == false) {
    throw new \Exception("Quitting setup at the user's request. BYE FELICIA.");
  }
})->setPrivate();

desc('Ask the user all questions for the necessary values.');
task('phpdraft:asksetupquestions', function() {
  runLocally('clear');

  writeln("\n\n<info>Hello! I'm the PHP Draft Setup Wizard (pointy hat not included)</info>");
  writeln("\n<info>I need to ask you a series of questions in order to determine how to properly setup "
    ."your installation of PHP Draft. To make this go smoothly, you should make sure you have everything "
    ."you need by reading the Install section of the wiki on Github (https://github.com/mattheworres/phpdraft/wiki/Installing-PHP-Draft).</info>\n");
  writeln("\n<info>Helpful hint: you can quit out at any time before I permanently overwrite your settings near the end by hitting CTRL+C!</info>\n");

  writeln("<comment>Database</comment>");
  $dbHost = ask("Where is the MySQL server located (IP or DNS name, usually localhost)?", "localhost");
  $dbName = ask("What is the name of the MySQL database?", "phpdraft");
  $dbPort = ask("What port is MySQL running on (usually 3306)?", 3306);
  $dbUser = ask("What is the name of the user that has access to the $dbName database?", 'your-database-user');
  $dbPass = askHiddenResponse("What is $dbUser 's password (characters will be hidden from the console for security)?");

  writeln("\n<comment>Caching</comment>");
  $cacheSeconds = ask("How long should I cache draft data (helps keep PHP Draft running quickly with concurrent users online - default is 3600 seconds)?", 3600);
  $cachePath = ask("What is the absolute path (on the webserver) to store cache files?", "/var/www/example.com/tmp");

  writeln("\n<comment>User Authorization</comment>");
  $authKey = askHiddenResponse("What value should I use to generate JWT tokens (use a long and random value!)");
  $authSeconds = ask("How long should user logins be valid for (default is 86,400 seconds, or 1 day)?", 86400);
  $recaptchaPublicKey = ask("What is your PUBLIC key for Google Recaptcha 2?", "recaptcha_public");
  $recaptchaPrivateKey = askHiddenResponse("What is your PRIVATE key for Google Recaptcha 2?");

  writeln("\n<comment>Email</comment>");
  $mailServer = ask("Where is the SMTP email server located (IP or DNS name)?", "localhost");
  $mailPort = ask("What port is the SMTP email server running on?", 25);
  $mailUser = ask("What is the name of the user for $mailServer?", "your_mail_username");
  $mailPass = askHiddenResponse("What is $mailUser 's password?");

  writeln("\n<comment>Application</comment>");
  $appBaseUrl = ask("What is the install's base URL (for the frontend, no trailing slash)?", "https://www.example.com");
  $apiBaseUrl = ask("What is the install's API url (no trailing slash)?", "$appBaseUrl/api");

  writeln("\n<comment>Deployment/SSH</comment>");
  $deployLocation = ask("What is the location of the  webserver (Apache/Nginx) where PHP Draft will be installed? (SSH must be enabled at this location)",
    "127.0.0.1");
  $deployUser = ask("What is the name of the user (with SSH access) to $deployLocation?", "your-ssh-user");
  $deployPath = ask("What is the absolute path (on the webserver) where the application will be located?",
    "/var/www/your_site_name");
  $deployReleasesKept = ask("How many PHP Draft releases should I keep on the webserver when uploading upgrades?", 2);

  writeln("\n\n<comment>Important to note that it is also possible to provide an SSH password - by default "
    ."I will assume you have $deployUser 's ssh key stored in ~/.ssh/id_rsa . Please edit deploy.php "
    ."as needed for the level of security your setup requires.</comment>\n");

  $vars = [
    'dbHost' => $dbHost,
    'dbName' => $dbName,
    'dbPort' => $dbPort,
    'dbUser' => $dbUser,
    'dbPass' => $dbPass,
    'cacheSeconds' => $cacheSeconds,
    'cachePath' => $cachePath,
    'authKey' => $authKey,
    'authSeconds' => $authSeconds,
    'recaptchaPublicKey' => $recaptchaPublicKey,
    'recaptchaPrivateKey' => $recaptchaPrivateKey,
    'mailServer' => $mailServer,
    'mailPort' => $mailPort,
    'mailUser' => $mailUser,
    'mailPass' => $mailPass,
    'appBaseUrl' => $appBaseUrl,
    'apiBaseUrl' => $apiBaseUrl,
    'deployLocation' => $deployLocation,
    'deployUser' => $deployUser,
    'deployPath' => $deployPath,
    'deployReleasesKept' => $deployReleasesKept
  ];

  set('phpdraft_setup_variables', $vars);
})->setPrivate();

desc('Copy files from the deploy directory to their place.');
task('phpdraft:copyfiles', function() {
	if(file_exists('js') !== true) {
		runLocally('mkdir js');
	}

  runLocally('cp deploy/config.js.ci js/config.js');
  runLocally('cp deploy/appsettings.php.ci appsettings.php');
  runLocally('cp deploy/phinx.yml.ci phinx.yml');
  runLocally('cp deploy/deploy.php.ci deploy.php');
})->setPrivate();

desc('Replace placeholder markers with values from user');
task('phpdraft:replacevalues', function() {
  $files_to_replace_in = [
    'appsettings.php',
    'js/config.js',
    'phinx.yml',
    'deploy.php'
  ];

  foreach(get('phpdraft_setup_variables') as $property => $value) {
    foreach($files_to_replace_in as $file) {
      //TODO: Is there something more platform agnostic we can use?
      runLocally("sed -i '' 's|{phpdraft.$property}|$value|g' $file $file");
    }
  }
})->setPrivate();

desc('Inform user that setup has completed successfully');
task('phpdraft:setupsuccess', function() {
    writeln("\n<info>Alright! Setup has completed for PHP Draft!</info>\n");
    writeln("\n<info>If you plan on deploying this install, you can say NO to import your settings - "
      ."they're already in place and don't need imported!</info>");
})->setPrivate();
