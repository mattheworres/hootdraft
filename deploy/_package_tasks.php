<?php
namespace Deployer;

desc('Build & Package PHP Draft from source (for Github Releases)');
task('phpdraft:package_release', [
    'phpdraft:get_release_details',
    'phpdraft:verify_package',
    'phpdraft:npm_install',
    'phpdraft:bower_install',
    'phpdraft:composer_install',
    'phpdraft:build_app',
    'phpdraft:zip_package',
    'phpdraft:zip_resources',
    'phpdraft:package_success'
]);

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

    $releaseFileName = get('phpdraft')['releaseFile'];
    $resourceFileName = get('phpdraft')['resourceFile'];
    $archivePath = get('phpdraft')['releasePath'];

    if(file_exists("$archivePath/$releaseFileName")) {
        throw new \Exception("File $archivePath/$releaseFileName already exists.");
    }

    if(file_exists("$archivePath/$resourceFileName")) {
        throw new \Exception("File $archivePath/$resourceFileName already exists.");
    }
})->setPrivate();

desc('Install NPM packages (locally)');
task('phpdraft:npm_install', function() {
    runLocally('npm install');
})->setPrivate();

desc('Install Bower packages (locally)');
task('phpdraft:bower_install', function() {
    runLocally('bower install');
})->setPrivate();

desc('Install Composer packages (locally)');
task('phpdraft:composer_install', function() {
    runLocally('composer install --prefer-dist -o --no-progress --no-suggest');
})->setPrivate();

desc('Build Angular App');
task('phpdraft:build_app', function() {
    runLocally('gulp build --minify --concat --templates --revAssets --env=dist');
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
})->setPrivate();

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
        'composer.json',
        'composer.lock',
        'README.MD',
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
})->setPrivate();

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
})->setPrivate();

desc('Tell user the package was successfully created');
task('phpdraft:package_success', function() {
    $releaseFileName = get('phpdraft')['releaseFile'];
    $resourceFileName = get('phpdraft')['resourceFile'];
    $archivePath = get('phpdraft')['releasePath'];

    writeln("<info>PHP Draft successfully packaged!");
    writeln("<info>Main archive here: $archivePath/$releaseFileName");
    writeln("<info>Resources archive here: $archivePath/$resourceFileName");
})->setPrivate();