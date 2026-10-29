<?php
namespace Deployer;

desc('Hoot Draft: Deploy to production');
task('deploy', [
  'phpdraft:verify_install',
  'deploy:info',
  'deploy:setup',
  'deploy:lock',
  'deploy:release',
  'phpdraft:upload_files',
  'deploy:shared',
  'phpdraft:warm_cache',
  'phpdraft:breakpoint',
  'phpdraft:migrate',
  'deploy:symlink',
  //'phpdraft:restart_fpm',
  'deploy:unlock',
  'deploy:cleanup',
  'deploy:success'
]);

// Logs survive between releases. Settings (.env.local) are uploaded with every release.
// var/sessions is shared so a deploy doesn't log everybody out
set('shared_dirs', ['var/log', 'var/sessions']);
set('shared_files', []);
// vendor/ ships inside the release package (no composer needed on the host); Phinx is a regular dependency.

before('deploy:release', 'phpdraft:set_rollback');
fail('deploy', 'phpdraft:rollback');
fail('deploy', 'phpdraft:failure');

desc('Verify Hoot Draft has been prepared for deployment');
task('phpdraft:verify_install', function() {
  writeln('');

  $items_required_for_deploy = [
    'js',
    'vendor',
    '.env.local',
    'css',
    'index.html'
  ];

  foreach($items_required_for_deploy as $file) {
    if(file_exists($file) !== true) {
      writeln("<error>File or directory $file does not exist, cannot deploy.</error>\n");
      writeln("<comment>Ensure you have downloaded a compiled release from https://github.com/mattheworres/hootdraft/releases</comment>\n");
      writeln("<comment>Or, if you are building from sourcecode, consult the wiki on how to properly prepare a release.</comment>\n");
      throw new \Exception("Hoot Draft has not been properly prepared for deployment.");
    }
  }

  writeln('<info>Hoot Draft is ready for deployment!</info>');
})->hidden();

desc('Upload app files');
task('phpdraft:upload_files', function() {
  // Directories are uploaded as their contents (trailing slash), files one by one.
  // Only what the running app needs: no tests/, docs/, docker/ or node_modules.
  $phpdraft_dirs = [
    'api',
    'bin',
    'config',
    'src',
    'templates',
    'vendor',
    'css',
    'db',
    'fonts',
    'images',
    'js',
  ];

  $phpdraft_files = [
    '.env',
    '.env.local',
    '.htaccess',
    'composer.json',
    'composer.lock',
    'phinx.php',
    'index.html',
    'web.config',
    'favicon.ico',
  ];

  foreach ($phpdraft_dirs as $dir) {
    upload("$dir/", "{{release_path}}/$dir/");
  }

  foreach ($phpdraft_files as $file) {
    upload($file, "{{release_path}}/$file");
  }
})->hidden();

desc('Install NPM dependencies remotely');
task('phpdraft:remote_yarn', function() {
  cd('{{release_path}}');

  run('yarn --production');
})->hidden();

desc('Build the production Symfony cache');
task('phpdraft:warm_cache', function() {
  run('mkdir -p {{release_path}}/var/cache');
  run('cd {{release_path}} && APP_ENV=prod APP_DEBUG=0 {{bin/php}} bin/console cache:warmup');
})->hidden();

//TODO: SSH user needs elevated via the sudoers file in order to do this in Deployer
// desc('Restart the PHP FPM service');
// task('phpdraft:restart_fpm', function() {
// 	if(get("restart_fpm") == true) {
// 		writeln("<comment>Restarting the php-fpm service...</comment>");
// 		run('sudo service php8.4-fpm restart');
// 	} else {
// 		writeln("<comment>Skipping restart of php-fpm service.</comment>");
// 	}
// })->hidden();

desc('Set Phinx breakpoint');
task('phpdraft:breakpoint', function() {
  run('cd {{release_path}} && {{bin/php}} vendor/bin/phinx breakpoint -e production --remove-all');
  run('cd {{release_path}} && {{bin/php}} vendor/bin/phinx breakpoint -e production');
})->hidden();

desc('Set rollback var to false in case of error');
task('phpdraft:set_rollback', function() {
  set('phpdraft_rollback_required', false);
})->hidden();

desc('Run Phinx migrations');
task('phpdraft:migrate', function() {
  set('phpdraft_rollback_required', true);

  run('cd {{release_path}} && {{bin/php}} vendor/bin/phinx migrate -e production');
})->hidden();

desc('Rollback migrations on failure');
task('phpdraft:rollback', function() {
  writeln('<comment>Rolling back database migrations...</comment>');
  if(get("phpdraft_rollback_required") == true) {
    run('cd {{release_path}} && {{bin/php}} vendor/bin/phinx rollback -e production');
  }
})->hidden();

desc('Inform user of failed deploy');
task('phpdraft:failure', function() {
  writeln("\n<error>Whoops! Looks like an error has occurred and the deploy failed.</error>\n");
  writeln("<error>I attempted to rollback any migrations that were made on the database, but you will
    need to verify the integrity of your install. See {{deploy_path}} releases and create a new
    symlink to the last \"good\" release in order to go back to working code, or restore any
    working backups you have to go back in time.</error>");
})->hidden();
