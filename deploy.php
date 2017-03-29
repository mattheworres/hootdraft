<?php
namespace Deployer;
require 'recipe/common.php';

// Configuration
set('ssh_type', 'native');
set('ssh_multiplexing', true);
set('keep_releases', 2);
set('phinx', ['remove-all' => '', 'environment' => 'production']);
set('phpdraft:rollback_required', false);

// Servers
server('production', 'localhost')
    ->user('user')
    ->password('password')  //To provide password at terminal prompt, pass NULL here instead
    //->identityFile()    //Optional, if empty assumes id_rsa within .ssh
    ->set('deploy_path', '/var/www/domain.com') //Is abs path to the base directory of the site
    ;

desc('Deploy PHP Draft to production');
task('deploy', [
    'phpdraft:verify_install',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'phpdraft:upload_files',
    'phinx:breakpoint',         //Remove existing breakpoints
    'phinx:breakpoint',         //Set a breakpoint at the existing migration
    'phinx:migrate',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

//Optional: enable this if you are using php-fpm and have added the deploy user to the sudoers:
//after('deploy:symlink', 'php-fpm:restart');
after('deploy_failed', 'phpdraft:rollback');
after('deploy_failed', 'deploy:unlock');
after('deploy_failed', 'phpdraft:failure');

// Tasks
desc('Verify PHP Draft has been prepared for deployment');
task('phpdraft:verify_install', function() {
    writeln('');

    $items_required_for_deploy = [
        'js',
        'js/config.js',
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
            throw new \Exception("PHP Draft has not been properly prepared for deployment.");
        }
    }

    writeln('<info>PHP Draft is ready for deployment!</info>');
});

desc('Upload app files');
task('phpdraft:upload_files', function() {
    $phpdraft_files = [
        'api',
        'css',
        'fonts',
        'images',
        'js',
        'vendor',
        '.htaccess',
        'appsettings.php',
        'phinx.yml',
        'deploy_libs',
        'index.html',
        'web.config'
    ];

    //List the folders we want to exclude children from based on the list below
    $dynamic_upload_folders = [
        'vendor'
    ];

    //Exclude any children matching the list below from the list above
    //These exclusions are almost all dev dependencies from Composer
    $dynamic_exclusions = [
        '..',
        '.',
        'bin',
        'composer',
        'robmorgan',
        'phpunit',
        'myclabs',
        'phpspec',
        'sebastian',
        'phpmd',
        'pdepend',
        'phpdocumentor',
        'webmozart',
        'deployer',
        'elfet',
        'react',
        'ringcentral',
        'guzzlehttp',
        'evenement',
        'phpseclib'
    ];

    foreach($dynamic_upload_folders as $dynamic_folder) {
        $list_of_items = scandir($dynamic_folder);
        $good_items = array_diff($list_of_items, $dynamic_exclusions);

        foreach($good_items as $item) {
            $phpdraft_files[] = "$dynamic_folder/$item";
        }
    }

    foreach ($phpdraft_files as $file)
    {
        writeln("<comment>Uploading $file as we speak</comment>");
        //upload($file, "{{release_path}}/{$file}");
    }
});

desc('Set Phinx breakpoint');
task('phpdraft:breakpoint', function() {
    cd('{{release_path}}');

    run('php deploy_libs/phinx.phar breakpoint -e production --remove-all');
    run('php deploy_libs/phinx.phar breakpoint -e production');

    set('phpdraft:rollback_required', true);

    cd('{{deploy_path}}');
});

desc('Run Phinx migrations');
task('phpdraft:migrate', function() {
    cd('{{release_path}}');

    run('php deploy_libs/phinx.phar migrate -e production');

    cd('{{deploy_path}}');
});

desc('Rollback migrations on failure');
task('phpdraft:rollback', function() {
    writeln('<comment>Rolling back database migrations...');
    if(get("phpdraft:rollback_required") == true) {
       cd('{{release_path}}');
       run('php deploy_libs/phinx.phar rollback -e production'); 
       cd('{{deploy_path}}');
    }
});

desc('Inform user of failed deploy');
task('phpdraft:failure', function() {
    writeln("\n<error>Whoops! Looks like an error has occurred and the deploy failed.</error>\n");
    writeln("<error>I attempted to rollback any migrations that were made on the database, but you will
        need to verify the integrity of your install. See {{deploy_path}} releases and create a new
        symlink to the last \"good\" release in order to go back to working code, or restore any
        working backups you have to go back in time.</error>");
});

desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo systemctl restart php-fpm.service');
});