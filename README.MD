![PHP Draft](/images/logo_xlarge.png?raw=true "PHP Draft")

<a href="https://codeclimate.com/github/mattheworres/phpdraft"><img src="https://codeclimate.com/github/mattheworres/phpdraft/badges/gpa.svg" /></a>
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
- PHP 5.4+ installed
- MySQL 5+ database
- Ability to use `.htaccess` to enable/use `mod_rewrite` (Apache) or `web.config` (IIS)
- Ability to create and edit a file one directory above your PHP Draft install directory
- SMTP mail account (for user registration/password resets)
- Google reCAPTCHA 2 site key (public and private - used to verify new user registration)
- OPTIONAL (but recommended): SSL certificate installed for your domain (for enhanced user privacy/security)

## Installing PHP Draft

See [INSTALL.md](INSTALL.md) for detailed installation instructions.

**If you currently use PHP Draft v1.3.0**, please see [UPGRADE.md](UPGRADE.md) for instructions on how to perform an upgrade instead of a new installation.

Currently there are no plans to support upgrading from versions previous to 1.3.0.

## Feature Requests

Check out our [FeatHub](http://feathub.com/mattheworres/phpdraft) page for a list of popular features, and sign in with your **Github** account to vote and submit more!

You can also enter the project's [Gitter](https://gitter.im/mattheworres/phpdraft) chat room for help from me or other PHP Draft users!

Or, just hit me up on [Twitter](https://twitter.com/mattheworres) and let me know what you have in mind!

## Bugs

Check out the [Issues](https://github.com/mattheworres/phpdraft/issues) tab on the Github project. Search the issues (open and closed) for some help, or enter a new issue!

You can also enter the project's [Gitter](https://gitter.im/mattheworres/phpdraft) chat room for help from me or other PHP Draft users!

You can also hit me up on [Twitter](https://twitter.com/mattheworres) if you're having trouble. No promises on the return time.

## Notes

 1. Version 2 adds full-blown user management and secure access, such that PHP Draft is no longer a *PHP site*, it's now a first class **web application** that can run in a **Software as a Service (SaaS)** model. What does that mean? You don't actually need to install PHP Draft yourself - you can use my site at [phpdraft.com](https://phpdraft.com) instead :)

    - **I pay out of pocket for my own hosting**. If you use it, please consider making a Paypal donation to **[matthew.orres@gmail.com](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=U6AZ6QENWDWPE)** to help cover the costs. Thanks!

 1. This was a complete project rewrite with 110% feature parity from version 1.3.0 (I added a few nice features/smoothed other things out from 1.3). The only missing "feature" was the MP3 sounds autoplaying when pick timers ran out. I plan on bringing it back soon, but felt it was both A) a superfluous feature at best and B) would risk jeopardizing the 2.0 codebase hardening in time for real life MLB 2016 usage.

    - If you loved this feature, let me know! (Twitter: [@mattheworres](https://twitter.com/mattheworres) ) I may prioritize it a little higher if I hear from enough folks and add it back in a future version.

 1. This also marks the first time that PHP Draft has been available on Git/Github as opposed to Mercurial/Sourceforge/Bitbucket.

    - Can I just say how awesome Atlassian Bitbucket is?! I started using Bitbucket way back in 2010, and it's come leaps and bounds to where I *actually* prefer Bitbucket to Github, all other things being equal. Their pricing is particularly advantageous when compared to Github's, especially for private teams. So, if you're considering the two, I *would* vote for Atlassian's Bitbucket. But... It kills me to switch from Bitbucket to Github, but the main reason for the switch was...
    - **Sourceforge**. When I started the project in 2010, Sourceforge was an *OK* place for open source projects to live. It's a different story nowadays, unfortunately. SF has turned into a shell of its former self, and never seemed to find a sustainable revenue model. From its issues with "junkware" to the severely declined OS traffic, it makes it less and less advantageous for PHP Draft to live there.
    - **Github** is synonymous with open source, and I hope to switch to both it as well as **Git** over Mercurial will help drive more code contribution. The platform has a much better setup for an open source project (it handles source, exterior collaboration, issues, releases), and a huge bonus is that there are a ton of external services that are free for open source projects (FeatHub and Gitter are two that come to mind, but there are more).
    - For now, issue tracking will remain in **Atlassian's JIRA** - and I am still incredibly grateful for their lifetime license of the JIRA software for this project. If I end up getting more contributors in the future, I can envision needing to leverage the power that JIRA provides. Stay tuned.

## Contributing & custom development

See [DEVELOPMENT.md](DEVELOPMENT.md) if you are interested in developing or making custom changes to your PHP Draft.

PHP Draft is now a PHP API with an Angular (Javascript) front end, and has a complicated build toolchain that includes NPM, Bower, Gulp, and Coffeescript (to name a few) - take a gander at that readme for better instructions on how the project is now built.

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

**Jamie Orres**

**Ryan Scarbery**

**Jeff Machusko**

**Justin Pyvis**

## Copyright and license

Code and documentation copyright 2010-2016 Matthew Orres. Code released under the GNU v3 license.
