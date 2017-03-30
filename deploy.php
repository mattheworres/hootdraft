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

desc('Build & Package PHP Draft from source (for Github Releases)');
task('phpdraft:package_release', [
    'phpdraft:verify_package',
    'phpdraft:get_release_details',
    'phpdraft:npm_install',
    'phpdraft:bower_install',
    'phpdraft:composer_install',
    'phpdraft:build_app',
    'phpdraft:zip_package',
    'phpdraft:zip_resources',
    'phpdraft:package_success'
]);

desc('Restart PHP-FPM service (useful if changes don\'t show immediately after deploy)');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo systemctl restart php-fpm.service');
});

//Optional: enable this if you are using php-fpm and have added the deploy user to the sudoers:
//after('deploy:symlink', 'php-fpm:restart');
after('deploy:failed', 'phpdraft:rollback');
after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'phpdraft:failure');

// Private Tasks
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
})->setPrivate();

desc('Upload app files');
task('phpdraft:upload_files', function() {
    $phpdraft_files = [
        'api',
        'css',
        'fonts',
        'images',
        'js',
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
        '.DS_Store',
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
        upload($file, "{{release_path}}/{$file}");
    }
})->setPrivate();

desc('Set Phinx breakpoint');
task('phpdraft:breakpoint', function() {
    cd('{{release_path}}');

    run('php deploy_libs/phinx.phar breakpoint -e production --remove-all');
    run('php deploy_libs/phinx.phar breakpoint -e production');

    set('phpdraft:rollback_required', true);

    cd('{{deploy_path}}');
})->setPrivate();

desc('Run Phinx migrations');
task('phpdraft:migrate', function() {
    cd('{{release_path}}');

    run('php deploy_libs/phinx.phar migrate -e production');

    cd('{{deploy_path}}');
})->setPrivate();

desc('Rollback migrations on failure');
task('phpdraft:rollback', function() {
    writeln('<comment>Rolling back database migrations...');
    if(get("phpdraft:rollback_required") == true) {
       cd('{{release_path}}');
       run('php deploy_libs/phinx.phar rollback -e production'); 
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



desc('Verify the repository is ready for building and packaging');
task('phpdraft:verify_package', function() {
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
            throw new \Exception("PHP Draft cannot be packaged for release.");
        }
    }

    $npm_output = runLocally('npm -v');

    if(strpos($npm_output, '.') == false) {
        writeln('<error>NPM not found on path. Install NPM globally for commandline use</error>');
        throw new \Exception("PHP Draft cannot be packaged for release");
    }

    $sevenZip_output = runLocally('7z');

    if(strpos($sevenZip_output, 'Igor Pavlov') == false) {
        writeln('<error>7-Zip not found on path. Install 7-Zip for commandline use</error>');
        throw new \Exception("PHP Draft cannot be packaged for release");
    }
})->setPrivate();

desc('Install NPM packages');
task('phpdraft:npm_install', function() {
    runLocally('npm install');
})->setPrivate();

desc('Install Bower packages');
task('phpdraft:bower_install', function() {
    runLocally('bower install');
})->setPrivate();

desc('Install Composer packages');
task('phpdraft:composer_install', function() {
    runLocally('composer install --prefer-dist -o --no-progress --no-suggest');
})->setPrivate();

desc('Build Angular App');
task('phpdraft:build_app', function() {
    runLocally('gulp build --minify --concat --templates --revAssets');
})->setPrivate();

desc('Ask the user for release details');
task('phpdraft:get_release_details', function() {
    $phpdraftReleasePath = ask('Where should the release package be stored (provide an absolute path, no trailing slash)?', '$/phpdraft_releases');

    $phpdraftReleaseFile = ask('Name for the main archive (.zip will be appended)', 'PHPDraft_2.x.x_Official');

    $phpdraftResourcesFile = ask('Name for the resources archive (.zip will be appended)', 'PHPDraft_2.x.x_Player_CSV_Data');

    set('phpdraft', [
        'releasePath' => $phpdraftReleasePath,
        'releaseFile' => "$phpdraftReleaseFile.zip",
        'resourceFile' => "$phpdraftResourcesFile.zip"
    ]);
});

desc('Package release with 7zip');
task('phpdraft:zip_package', function() {
    $phpdraft_release_dirs = [
        'api',
        'css',
        'db',
        'fonts',
        'images',
        'vendor',
        'js'
    ];

    $phpdraft_release_files = [
        '.htaccess',
        'EXAMPLE_appsettings.php',
        'README.md',
        'INSTALL.md',
        'phinx.yml.EXAMPLE',
        'deploy',
        'index.html',
        'web.config'
    ];

    $releaseFileName = get('phpdraft')['releaseFile'];
    $archivePath = get('phpdraft')['releasePath'];

    foreach($phpdraft_release_dirs as $archiveDirectory) {
        runLocally("7z a $archivePath/$releaseFileName $archiveDirectory");
    }

    foreach($phpdraft_release_files as $archiveFile) {
        runLocally("7z a $archivePath/$releaseFileName $archiveFile");
    }
});

desc('Package resources with 7zip');
task('phpdraft:zip_resources', function() {
    $phpdraft_resource_dir = 'resources';
    $filesToExclude = ['.', '..', '.DS_Store'];
    $resourcesFiles = scandir($phpdraft_resource_dir);
    $goodResources = array_diff($resourcesFiles, $filesToExclude);

    $resourceFileName = get('phpdraft')['resourceFile'];
    $archivePath = get('phpdraft')['releasePath'];

    foreach($goodResources as $resourceFile) {
        runLocally("7z a $archivePath/$resourceFileName $phpdraft_resource_dir/$resourceFile");    
    }
});

desc('Tell user the package was successfully created');
task('phpdraft:package_success', function() {
    $releaseFileName = get('phpdraft')['releaseFile'];
    $resourceFileName = get('phpdraft')['resourceFile'];
    $archivePath = get('phpdraft')['releasePath'];

    writeln("<info>PHP Draft successfully packaged!");
    writeln("<info>Main archive here: $archivePath/$releaseFileName");
    writeln("<info>Resources archive here: $archivePath/$resourceFileName");
});