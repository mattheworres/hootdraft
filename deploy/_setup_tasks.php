<?php
namespace Deployer;

$backupTitle = 'Hoot Draft: Offer to backup current settings for future upgrade imports';

desc('Hoot Draft: Use a wizard to set important variables');
task('setup', [
    'phpdraft:setupconfirm',
    'phpdraft:asksetupquestions',
    'phpdraft:copyfiles',
    'phpdraft:replacevalues',
    'backup',
    'phpdraft:setupsuccess'
]);

desc($backupTitle);
task('export', ['backup']);

desc('Hoot Draft: Import settings before deploying');
task('import', function() {
  writeln("\n<info>Hello! Let's get those settings files imported for you, right quick!</info>\n");

  writeln("\n<comment>Just a note: this will OVERWRITE anything and everything in .env.local, deploy.php and js/config.js. Hit CTRL+C to exit now if you don't want to do that!</comment>\n");

  $settings_location = ask("What is the absolute (local) path of where your settings files are located?",
      "~/phpdraft_settings");

  // runLocally("cp $settings_location/.htaccess .htaccess");
  runLocally("cp $settings_location/.env.local .env.local");
  runLocally("cp $settings_location/config.js js/config.js");
  runLocally("cp $settings_location/deploy.php deploy.php");
  //runLocally("cp $settings_location/index.html index.html");
  runLocally("cp $settings_location/web.config web.config");

  writeln("\n\n<info>Success! I imported those settings for you, you should be ready to deploy now.</info>\n");
});

desc($backupTitle);
task('backup', function() {
  if (file_exists("js/config.js") == false) {
    writeln("<error>Looks like js/config.js doesn't exist - I can't back up your settings.</error>\n");
    writeln("<comment>Ensure you have downloaded a compiled release from https://github.com/mattheworres/hootdraft/releases</comment>\n");
    writeln("<comment>Or, if you are building from sourcecode, consult the wiki on how to properly prepare a release.</comment>\n");
    throw new \Exception("Hoot Draft is not in a exportable state (use downloads from Releases on Github)");
  }

  writeln("\n\n<info>Looking great! Hey, if you want I can back up these snazzy settings files "
    ."I just created for you. This will make it WAY easier to update Hoot Draft in the future!</info>\n");
  $answer = askConfirmation("Should I back up your settings for you?", false);

  if($answer == true) {
    writeln("\n\n<info>Awesome! Wherever you tell me to back your settings up to, you should make sure "
      ."that only your user has permissions to view the file (if the server you're installing on "
      ."is more important, you should probably ensure your disk is encrypted, too).</info>\n");
    $backup_location = ask("Great! Where should I back your settings up to (ensure the directory exists first)?",
      "~/phpdraft_settings");

    runLocally("cp .env.local $backup_location/.env.local");
    runLocally("cp js/config.js $backup_location/config.js");
    runLocally("cp deploy.php $backup_location/deploy.php");
    runLocally("cp .htaccess $backup_location/.htaccess");
    //runLocally("cp index.html $backup_location/index.html");
    runLocally("cp web.config $backup_location/web.config");
  }
});

desc('Confirm that the user wants to go through with this; warn of irreversible nature');
task('phpdraft:setupconfirm', function() {
  $message = "Are you sure you wish to begin setup? This will OVERWRITE "
  ."any values stored within .env.local, deploy.php and js/config.js files!!";

  $answer = askConfirmation($message, false);

  if($answer == false) {
    throw new \Exception("Quitting setup at the user's request. BYE FELICIA.");
  }
})->hidden();

desc('Ask the user all questions for the necessary values.');
task('phpdraft:asksetupquestions', function() {
  runLocally('clear');

  writeln("\n\n<info>Hello! I'm the Hoot Draft Setup Wizard (pointy hat not included)</info>");
  writeln("\n<info>I need to ask you a series of questions in order to determine how to properly setup "
    ."your installation of Hoot Draft. To make this go smoothly, you should make sure you have everything "
    ."you need by reading the Install section of the wiki on Github (https://github.com/mattheworres/hootdraft/wiki/Installing-PHP-Draft).</info>\n");
  writeln("\n<info>Helpful hint: you can quit out at any time before I permanently overwrite your settings near the end by hitting CTRL+C!</info>\n");

  writeln("<comment>Database</comment>");
  $dbHost = ask("Where is the MySQL server located (IP or DNS name, usually localhost)?", "localhost");
  $dbName = ask("What is the name of the MySQL database?", "phpdraft");
  $dbPort = ask("What port is MySQL running on (usually 3306)?", 3306);
  $dbUser = ask("What is the name of the user that has access to the $dbName database?", 'your-database-user');
  $dbPass = askHiddenResponse("What is $dbUser 's password (characters will be hidden from the console for security)?");

  writeln("\n<comment>Caching</comment>");
  $cacheSeconds = ask("How long should I cache draft data (helps keep Hoot Draft running quickly with concurrent users online - default is 3600 seconds)?", 3600);
  $cachePath = ask("What is the absolute path (on the webserver) to store cache files?", "/var/www/example.com/tmp");

  writeln("\n<comment>Google Recaptcha</comment>");
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
  $deployLocation = ask("What is the location of the  webserver (Apache/Nginx) where Hoot Draft will be installed? (SSH must be enabled at this location)",
    "127.0.0.1");
  $deployUser = ask("What is the name of the user (with SSH access) to $deployLocation?", "your-ssh-user");
  $deployPath = ask("What is the absolute path (on the webserver) where the application will be located?",
    "/var/www/your_site_name");
  $deployReleasesKept = ask("How many Hoot Draft releases should I keep on the webserver when uploading upgrades?", 2);

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
    'appSecret' => bin2hex(random_bytes(32)),
    'recaptchaPublicKey' => $recaptchaPublicKey,
    'recaptchaPrivateKey' => $recaptchaPrivateKey,
    //TODO: Update with a question to enable this?
    'mailDev' => false,
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
})->hidden();

desc('Copy files from the deploy directory to their place.');
task('phpdraft:copyfiles', function() {
  if(file_exists('js') !== true) {
    runLocally('mkdir js');
  }

  runLocally('cp deploy/config.js.ci js/config.js');
  runLocally('cp deploy/env.local.ci .env.local');
  runLocally('cp deploy/deploy.php.ci deploy.php');
})->hidden();

desc('Replace placeholder markers with values from user');
task('phpdraft:replacevalues', function() {
  $files_to_replace_in = [
    '.env.local',
    'js/config.js',
    'deploy.php'
  ];

  // Plain string replacement (no shell/sed), so passwords may contain any character.
  foreach($files_to_replace_in as $file) {
    $contents = file_get_contents($file);

    foreach(get('phpdraft_setup_variables') as $property => $value) {
      // .env.local values sit inside double quotes: escape what Symfony's Dotenv treats specially there
      $replacement = $file === '.env.local' ? addcslashes((string) $value, '"\\$') : (string) $value;
      $contents = str_replace("{phpdraft.$property}", $replacement, $contents);
    }

    file_put_contents($file, $contents);
  }
})->hidden();

desc('Inform user that setup has completed successfully');
task('phpdraft:setupsuccess', function() {
    writeln("\n<info>Alright! Setup has completed for Hoot Draft!</info>\n");
    writeln("\n<info>If you plan on deploying this install, you can say NO to import your settings - "
      ."they're already in place and don't need imported!</info>");
})->hidden();
