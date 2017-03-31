![PHP Draft](/images/logo_xlarge.png?raw=true "PHP Draft")

<a href="https://codeclimate.com/github/mattheworres/phpdraft"><img src="https://codeclimate.com/github/mattheworres/phpdraft/badges/gpa.svg" /></a>
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mattheworres/phpdraft/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mattheworres/phpdraft/?branch=master)
<a href="https://codeclimate.com/github/mattheworres/phpdraft/coverage"><img src="https://codeclimate.com/github/mattheworres/phpdraft/badges/coverage.svg" /></a>
[![Join the chat at https://gitter.im/mattheworres/phpdraft](https://badges.gitter.im/mattheworres/phpdraft.svg)](https://gitter.im/mattheworres/phpdraft?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

PHP Draft allows commissioners to setup web-based real-time drafts for their fantasy sports leagues. If your leagues do **offline drafts** (instead of using your fantasy provider's online drafting tools), PHP Draft is just for you! 

The league commissioner creates an account, and then adds the fantasy league.

Pick the draft sport and style, specify the number of rounds the draft will last. Add the league managers (teams), and specify pick timers (some managers slower than others? Give them a timer!)

The commissioner hits the start button.

#### **BOOM**. Your fantasy draft is underway.

Leave a laptop running on a podium and make each team enter their selections as their pick comes up, or be a gatekeeper commissioner and make every pick go through you - it's your choice!

The large color-coded draft board is perfect for displaying on large screen televisions (compatible with Google Chromecast screen casting!) and will display all pertinent info on-screen as it automatically updates.

NFL, NBA, MLB, NHL and even Rugby are supported, along with trades. Watch your draft unfold or run the whole thing from your smartphone with a **Bootstrap**-powered responsive web app!

Don't know how to install a web app or run a web server? No problem:

##### Sign up for a free commissioner account on [phpdraft.com](https://phpdraft.com) and get drafting today!

### Minimum Server Requirements
- PHP 5.5.9+ installed (with `mcrypt` extension installed; PHP 7.x recommended!)
- MySQL 5.5+ database
- Webserver that can perform URL rewrites (Apache, IIS, or NGINX currently, possibly more in the future)
- SMTP mail account (for user registration/password resets)
- Google reCAPTCHA 2 site key (public and private - used to verify new user registration)
- OPTIONAL (but recommended): SSL certificate installed for your domain (for enhanced user privacy/security)

## Installing PHP Draft

See [INSTALL.md](INSTALL.md) for detailed installation instructions, and [OPENSHIFT_DEPLOYMENT.md](OPENSHIFT_DEPLOYMENT.md) if you are interested in installing PHPDraft in the cloud (free!).

**If you currently use PHP Draft v1.3.0**, please see [UPGRADE.md](UPGRADE.md) for instructions on how to perform an upgrade instead of a new installation.

Currently there are no plans to support upgrading from versions previous to 1.3.0.

## Feature Requests

Check out our [FeatHub](http://feathub.com/mattheworres/phpdraft) page some possible future features, or sign in with your **Github** to add your own:

[![Feature Requests](http://feathub.com/mattheworres/phpdraft?format=svg)](http://feathub.com/mattheworres/phpdraft)

Or, just hit me up on [Twitter](https://twitter.com/mattheworres) and let me know what you have in mind!

## Bugs

Check out the [Issues](https://github.com/mattheworres/phpdraft/issues) tab on the Github project. Search the issues (open and closed) for some help, or enter a new issue!

You can also enter the project's [Gitter](https://gitter.im/mattheworres/phpdraft) chat room for help from me or other PHP Draft users!

You can also hit me up on [Twitter](https://twitter.com/mattheworres) if you're having trouble. No promises on the return time.

## Contributing & custom development

See [DEVELOPER.md](DEVELOPER.md) if you are interested in developing or making custom changes to your PHP Draft.

If you come up with something that others may find worthwhile - please fork this repository and submit a pull request. I provide this code (and the hundreds of hours of my personal time I've poured into the project) free of charge under an open source license without any expectation of payment or even gratitude. I do it to help a few folks out, and to return the favor I ask that you help pass it on :)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the most up to date version info.

## Creators

**Matthew Orres**

  - [Twitter](https://twitter.com/mattheworres)
  - [Github](https://github.com/mattheworres)
  - [Bitbucket](https://bitbucket.org/mattheworres/)
  - [MatthewOrres.com](http://www.mattheworres.com)

## Contributors

**Ross Larson**

**Jamie Orres**

**Justin Pyvis**

**Ryan Scarbery**

**Jeff Machusko**

## Copyright and license

Code and documentation copyright 2010-2016 Matthew Orres. Code released under the GNU v3 license.
