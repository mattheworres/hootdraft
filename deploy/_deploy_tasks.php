<?php
namespace Deployer;

desc('Hoot Draft: Deploy to production');
task('deploy', [
  'phpdraft:verify_install',
  'deploy:prepare',
  'deploy:lock',
  'deploy:release',
  'phpdraft:upload_files',
  'phpdraft:remote_composer',
  'phpdraft:breakpoint',
  'phpdraft:migrate',
  'deploy:symlink',
  //'phpdraft:restart_fpm',
  'deploy:unlock',
  'cleanup',
  'success'
]);

before('deploy:release', 'phpdraft:set_rollback');
after('deploy:failed', 'phpdraft:rollback');
after('deploy:failed', 'phpdraft:failure');

desc('Verify Hoot Draft has been prepared for deployment');
task('phpdraft:verify_install', function() {
  writeln('');

  $items_required_for_deploy = [
    'js',
    'appsettings.php',
    'phinx.yml',
    'css',
    'vendor'
  ];

  foreach($items_required_for_deploy as $file) {
    if(file_exists($file) !== true) {
      writeln("<error>File or directory $file does not exist, cannot deploy.</error>\n");
      writeln("<comment>Ensure you have downloaded a compiled release from https://github.com/mattheworres/phpdraft/releases</comment>\n");
      writeln("<comment>Or, if you are building from sourcecode, consult the wiki on how to properly prepare a release.</comment>\n");
      throw new \Exception("Hoot Draft has not been properly prepared for deployment.");
    }
  }

  writeln('<info>Hoot Draft is ready for deployment!</info>');
})->setPrivate();

desc('Upload app files');
task('phpdraft:upload_files', function() {
  $phpdraft_files = [
    'api/',
    'css/',
    'db/',
    'fonts/',
    'images/',
    'js/',
    '.htaccess',
    'appsettings.php',
    'composer.json',
    'composer.lock',
    'package.json',
    'phinx.yml',
    'deploy/',
    'index.html',
    'web.config'
  ];

  foreach ($phpdraft_files as $file)
  {
    upload($file, "{{release_path}}/{$file}");
  }
})->setPrivate();

desc('Install NPM dependencies remotely');
task('phpdraft:remote_yarn', function() {
  cd('{{release_path}}');

  run('yarn --production');

  cd('{{deploy_path}}');
})->setPrivate();

desc('Install Composer dependencies remotely');
task('phpdraft:remote_composer', function() {
  cd('{{release_path}}');

  run('php {{release_path}}/deploy/composer.phar install --no-dev --prefer-dist -o --no-progress --no-suggest');

  cd('{{deploy_path}}');
})->setPrivate();

//TODO: SSH user needs elevated via the sudoers file in order to do this in Deployer
// desc('Restart the PHP FPM service');
// task('phpdraft:restart_fpm', function() {
// 	cd('{{release_path}}');

// 	if(get("restart_fpm") == true) {
// 		writeln("<comment>Restarting the php7.0-fpm service...</comment>");
// 		run('sudo service php7.0-fpm restart');
// 	} else {
// 		writeln("<comment>Skipping restart of php7.0-fpm service.</comment>");
// 	}

//     cd('{{deploy_path}}');
// })->setPrivate();

desc('Set Phinx breakpoint');
task('phpdraft:breakpoint', function() {
  cd('{{release_path}}');

  run('php deploy/phinx.phar breakpoint -e production --remove-all');
  run('php deploy/phinx.phar breakpoint -e production');

  cd('{{deploy_path}}');
})->setPrivate();

desc('Set rollback var to false in case of error');
task('phpdraft:set_rollback', function() {
  set('phpdraft_rollback_required', false);
})->setPrivate();

desc('Run Phinx migrations');
task('phpdraft:migrate', function() {
  cd('{{release_path}}');

  set('phpdraft_rollback_required', true);

  run('php deploy/phinx.phar migrate -e production');

  cd('{{deploy_path}}');
})->setPrivate();

desc('Rollback migrations on failure');
task('phpdraft:rollback', function() {
  writeln('<comment>Rolling back database migrations...</comment>');
  if(get("phpdraft_rollback_required") == true) {
    cd('{{release_path}}');
    run('php deploy/phinx.phar rollback -e production');
    cd('{{deploy_path}}');
  }
})->setPrivate();

desc('Inform user of failed deploy');
task('phpdraft:failure', function() {
  writeln("\n<error>Whoops! Looks like an error has occurred and the deploy failed.</error>\n");
  writeln("<error>I attempted to rollback any migrations that were made on the database, but you will
    need to verify the integrity of your install. See {{deploy_path}} releases and create a new
    symlink to the last \"good\" release in order to go back to working code, or restore any
    working backups you have to go back in time.</error>");
})->setPrivate();
