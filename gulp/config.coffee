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
                'bower_components/datatables/media/js/jquery.dataTables.min.js'
                'bower_components/angular/angular.min.js'
                'bower_components/angular-datatables/dist/angular-datatables.min.js'
                'bower_components/angular-datatables/dist/plugins/bootstrap/angular-datatables.bootstrap.min.js'
                'bower_components/angular-route/angular-route.min.js'
                'bower_components/ngstorage/ngStorage.min.js'
                'bower_components/angular-animate/angular-animate.min.js'
                'bower_components/angular-ui-bootstrap-bower/ui-bootstrap-tpls.min.js'
                'bower_components/angular-resource/angular-resource.min.js'
                'bower_components/ng-lodash/build/ng-lodash.min.js'
                'bower_components/angular-toastr/dist/angular-toastr.tpls.min.js'
                'bower_components/angular-recaptcha/release/angular-recaptcha.min.js'
                'bower_components/angular-toggle-switch/angular-toggle-switch.min.js'
                'bower_components/angular-validation-match/dist/angular-validation-match.min.js'
                'bower_components/ng-caps-lock/ng-caps-lock.min.js'
                'bower_components/moment/min/moment.min.js'
                'bower_components/angular-moment/angular-moment.min.js'
                'bower_components/angularjs-ordinal-filter/ordinal-browser.js'
            ],
            css: [
                'bower_components/angular-ui-bootstrap-bower/ui-bootstrap-csp.css'
                'bower_components/angular-toastr/dist/angular-toastr.css'
                'bower_components/datatables/media/css/jquery.dataTables.css'
                'bower_components/angular-datatables/dist/plugins/bootstrap/datatables.bootstrap.css'
                'bower_components/angular-toggle-switch/angular-toggle-switch.css'
                'bower_components/angular-toggle-switch/angular-toggle-switch-bootstrap.css'
            ]
