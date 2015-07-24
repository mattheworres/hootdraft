yargs = require 'yargs'

yargs.option 'browser', type: 'string'
yargs.option 'concat', type: 'boolean'
yargs.option 'env', default: 'dev', type: 'string'
yargs.option 'minify', type: 'boolean'
yargs.option 'open', default: 'http://phpdraft.local', type: 'string'
yargs.option 'revAssets', type: 'boolean'
yargs.option 'serve', default: true, type: 'boolean'
yargs.option 'sourceMaps', type: 'boolean'
yargs.option 'templates', type: 'boolean'

argv = yargs.argv

module.exports =
    options:
        browser: argv.browser
        concat: argv.concat
        env: argv.env
        minify: argv.minify
        open: argv.open
        revAssets: argv.revAssets
        serve: argv.serve
        sourceMaps: argv.sourceMaps
        templates: argv.templates

    paths:
        app:
            config: "app/config/#{argv.env}.json"
            css: 'app/css/style.less'
            fonts: 'app/fonts/**/*'
            html: 'app/index.html'
            img: 'app/img/**/*'
            js: 'app/coffee/**/*.coffee'
            templates: 'app/templates/**/*.html'

        vendor:
            fonts: [
                'bower_components/bootstrap/fonts/**/*',
                'bower_components/font-awesome/fonts/**/*'
            ],
            js: [
                'bower_components/jquery/dist/jquery.min.js'
                'bower_components/angular/angular.min.js'
                'bower_components/angular-route/angular-route.min.js'
            ]
