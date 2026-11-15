<?php
namespace Deployer;

desc('Hoot Draft: Build & Package from source (for Github Releases)');
task('package_release', [
  'hootdraft:get_release_details',
  'hootdraft:verify_package',
  //'hootdraft:yarn_install', //Issues here with symlinks in VMs, should use Yarn on your native machine anyways
  'hootdraft:composer_install',
  'hootdraft:slim_composer',
  'hootdraft:build_app',
  'hootdraft:zip_package',
  'hootdraft:zip_resources',
  'hootdraft:package_success'
]);

desc('Verify the repository is ready for building and packaging');
task('hootdraft:verify_package', function() {
  $items_required_for_packaging = [
    'app',
    'db',
    'deploy',
    'gulp'
  ];

  foreach($items_required_for_packaging as $directory) {
    if(file_exists($directory) !== true) {
      writeln("<error>Directory $directory does not exist, cannot package.</error>\n");
      writeln("<error>In order to package a release, you must start with sourcecode from the Github repository, not from a prepackaged release from the Github Releases (you've apparently done it backwards :) )</error>\n");
      throw new \Exception("Hoot Draft cannot be packaged for release.");
    }
  }

  $yarn_output = runLocally('yarn -v');

  if(strpos($yarn_output, '.') == false) {
    writeln('<error>Yarn not found on path. Install Yarn globally for commandline use</error>');
    throw new \Exception("Hoot Draft cannot be packaged for release");
  }

  $sevenZip_output = runLocally('7z');

  if(strpos($sevenZip_output, 'Igor Pavlov') == false) {
    writeln('<error>7-Zip not found on path. Install 7-Zip for commandline use</error>');
    throw new \Exception("Hoot Draft cannot be packaged for release");
  }

  $releaseFileName = get('hootdraft')['releaseFile'];
  $resourceFileName = get('hootdraft')['resourceFile'];
  $archivePath = get('hootdraft')['releasePath'];

  if(file_exists("$archivePath/$releaseFileName")) {
    throw new \Exception("File $archivePath/$releaseFileName already exists.");
  }

  if(file_exists("$archivePath/$resourceFileName")) {
    throw new \Exception("File $archivePath/$resourceFileName already exists.");
  }
})->hidden();

desc('Install NPM packages (for building)');
task('hootdraft:yarn_install', function() {
  runLocally('yarn install');
})->hidden();

desc('Install Composer packages (locally)');
task('hootdraft:composer_install', function() {
  runLocally('composer install --prefer-dist -o --no-progress');
})->hidden();

//Releases are 800MB without this, but moving Deployer to dev deps
//would unnecessarily bloat production install
desc('Remove spurious Composer packages');
task('hootdraft:slim_composer', function() {
  $spurious_vendor_folders = [
    'squizlabs',
    'phpunit',
    'sebastian',
    'phar-io',
    'theseer'
  ];

  foreach ($spurious_vendor_folders as $folder) {
    writeln("Removing vendor/$folder");
    runLocally("rm -rf vendor/$folder/");
  }
})->hidden();

desc('Build Angular App');
task('hootdraft:build_app', function() {
  runLocally('gulp build --minify --concat --templates --revAssets --env=dist');
})->hidden();

desc('Ask the user for release details');
task('hootdraft:get_release_details', function() {
  $hootdraftReleasePath = ask('Where should the release package be stored (provide an absolute path, no trailing slash)?', '~/hootdraft_releases');

  $hootdraftReleaseFile = ask('Name for the main archive (.zip will be appended)', 'HootDraft_2.x.x_Official');

  $hootdraftResourcesFile = ask('Name for the resources archive (.zip will be appended)', 'HootDraft_2.x.x_Player_CSV_Data');

  set('hootdraft', [
    'releasePath' => $hootdraftReleasePath,
    'releaseFile' => "$hootdraftReleaseFile.zip",
    'resourceFile' => "$hootdraftResourcesFile.zip"
  ]);
})->hidden();

desc('Package release with 7zip');
task('hootdraft:zip_package', function() {
  $hootdraft_release_dirs = [
    'api',
    'bin',
    'config',
    'src',
    'templates',
    'css',
    'db',
    'deploy',
    'fonts',
    'images',
    'vendor',
    'js'
  ];

  $hootdraft_release_files = [
    '.env',
    'phinx.php',
    'composer.json',
    'composer.lock',
    'README.MD',
    'package.json',
    'index.html',
    'favicon.ico',
  ];

  $releaseFileName = get('hootdraft')['releaseFile'];
  $archivePath = get('hootdraft')['releasePath'];

  foreach($hootdraft_release_dirs as $archiveDirectory) {
    runLocally("7z a $archivePath/$releaseFileName $archiveDirectory");
  }

  foreach($hootdraft_release_files as $archiveFile) {
    runLocally("7z a $archivePath/$releaseFileName $archiveFile");
  }

  //Hard-coded so we copy a working deploy.php so no one technically needs to edit a single file
  runLocally("7z a $archivePath/$releaseFileName deploy/deploy.php.release");
  runLocally("7z a $archivePath/$releaseFileName deploy/env.local.ci");
  runLocally("7z rn $archivePath/$releaseFileName deploy/deploy.php.release deploy.php");
  runLocally("7z rn $archivePath/$releaseFileName deploy/env.local.ci .env.local");

  //Re-include it in case anyone using this packaged release needs the CI version!
  runLocally("7z a $archivePath/$releaseFileName deploy/env.local.ci");
})->hidden();

desc('Package resources with 7zip');
task('hootdraft:zip_resources', function() {
  $hootdraft_resource_dir = 'resources';
  $filesToExclude = ['.', '..', '.DS_Store'];
  $resourcesFiles = scandir($hootdraft_resource_dir);
  $goodResources = array_diff($resourcesFiles, $filesToExclude);

  $resourceFileName = get('hootdraft')['resourceFile'];
  $archivePath = get('hootdraft')['releasePath'];

  foreach($goodResources as $resourceFile) {
    runLocally("7z a $archivePath/$resourceFileName $hootdraft_resource_dir/$resourceFile");
  }
})->hidden();

desc('Tell user the package was successfully created');
task('hootdraft:package_success', function() {
  $releaseFileName = get('hootdraft')['releaseFile'];
  $resourceFileName = get('hootdraft')['resourceFile'];
  $archivePath = get('hootdraft')['releasePath'];

  writeln("<info>Hoot Draft successfully packaged!");
  writeln("<info>Main archive here: $archivePath/$releaseFileName");
  writeln("<info>Resources archive here: $archivePath/$resourceFileName");
})->hidden();
