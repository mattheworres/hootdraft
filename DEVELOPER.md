# Developing

Want to work on your own custom version of PHP Draft, or have an idea for a great feature? Here's how you can be up and running in a few short minutes (or perhaps a few long minutes, depending on your internet speed).

### Tools Required
+ Node Package Manager (NPM) installed globally. Since NPM is a part of **Node.js**, its easiest to use the Node.js installer found here: https://nodejs.org/
+ Bower (front-end package manager) installed via NPM globally
  ```shell
  npm install bower -g
  ```
+ Install the [**Composer**](https://getcomposer.org) PHP package manager
+ A working webserver locally that is pointed to your local copy of the repository (XAMPP or IIS Express both work - that setup is beyond the scope of this document)
+ A working MySQL server locally that has an empty `phpdraft` database as well as a user account that can access it (again, setup is beyond the scope of this document)

#### Optional (but helpful) Tools
A working (or stubbed out) local SMTP server to simulate sending mail. User registration may seem broken on the front end (because the API will not get a response from an SMTP server), but you can manually enable and make the user account an admin as you do in the README.md instructions. A Ruby gem called **Mailcatcher** was useful to me while developing ([Papercut](https://github.com/jaben/papercut) is a nice Windows-only helper, too), and you can install it with these steps:
+ Install **Ruby** : [Linux/Mac OSX](https://www.ruby-lang.org/en/documentation/installation/) or [Windows](http://rubyinstaller.org/)
+ Install [**RubyGems**](https://rubygems.org/pages/download)
+ Install **Mailcatcher**:
  ```shell
  gem install mailcatcher 
  ```
+ Run **Mailcatcher**:
  ```shell
  mailcatcher
  ```
+ Update `appsettings.php` to point to `localhost:1025` (`MAIL_SERVER` is `localhost`, `MAIL_PORT` is `1025`)
+ Then access the web interface running on http://localhost:1080 to see all emails sent by the API

## Running Locally
First, you should treat your local copy of the repository as an installation of PHP Draft - so first follow README.md 's Install section.

The repository purposefully does not track any code in the `/vendor`, `/node_modules` or `/bower_components` folders. Instead, the project relies on 3 separate package managers to manage third party libraries: **Composer**, **npm** and **Bower**. There's a multitude of reasons for why (these three folders combine for over **150MB** of disk space is a big one, but I digress), but each time you clone the repository or pull down major changes, you'll need to run these three following commands to keep the app running.

Use **Composer** to download all of the API's third party dependencies by opening a commandline/terminal window and navigating to the base directory of the PHP Draft repository and executing:
  ```shell
  composer install
  ```
  
Next, ask **npm** to pull down all local third party dependencies related to the build process:
  ```shell
  npm install
  ```
  
Then, you will need to use **Bower** to download all third party web dependencies that the Angular app will leverage:
  ```shell
  bower install
  ```
  
##### Great! Now you're ready to use the Gulp toolchain to piece together everything on the front end!

At the base directory of the repository, invoke **Gulp** to build the project locally:

  ```shell
  gulp build
  ```
  
There are several flags that can be set that change how or what processes are run during the build:

  Gulp Build Flag                      | Notes
  ---------------------------      | -------------
  `--minify`             | `boolean` Run minification on all Coffeescript transpilation (greatly reduces filesize for better network performance)
  `--templates`    | `boolean` Compile HTML Angular templates into the Javascript for better network performance
  `--concat`             | `boolean` Concatenate similar Javascript files together into singular files like `vendor.js` and `app.js` (also for better network performance)
  `--env`             | `string` Specify the environment to build for, corresponds to the `/app/config/*.json` file for environment-specific settings. Defaults to `dev` and compiles into `js/config.js`
  `--sourceMaps`               | Include Javascript sourcemaps with the build (intended for dev only)

When a new PHP Draft release is made, this is the command I run (for reference):
```shell
gulp build --minify --templates --concat --env=dist
```
## Unit Tests
To run unit tests for the project, use this command at the base directory of the code:

```shell
vendor/bin/phpunit
```

Tests are located within the `/tests` folder.

Unit test coverage is anemic and well below where it should be - I just recently added them :) As I add new features or change existing ones, I will add test coverage as best I can.

## Project Structure
If you intend on submitting code to the project, it would be helpful for you to have a basic understanding of how things are structured on both the backend (Silex/PHP) and the front end (Angular/Coffeescript). PHP Draft is no longer written in "your father's PHP", and was intentionally done this way to vastly improve its maintainability and increase the ease to introduce new features.

### Back end (API)
Using the [Silex](http://silex.sensiolabs.org/) microframework, the API is the "brain center" of the entire application. It handles security, and coordinates all data access in conjunction with said security. It does this as a mostly [REST](https://en.wikipedia.org/wiki/Representational_state_transfer)ful API.

Starting with each request made, the API is kickstarted through `/api/bootstrap.php`, which just pulls in all necessary **Silex** setup files contained in `/api/config/`. For brevity we will assume  "it just works" is a good enough explanation for this section - requests in the form of `/api/resource/id` are properly routed to **Controllers**, and we're OK with that black box abstraction :)

**Controllers** are classes that are responsible for taking a web request, invoking whatever other classes are necessary to complete the request, and then formulate a response. Like in many **MVC** patterns, PHP Draft controllers should be *very lightweight* (which means **no business logic should reside in a controller**).

**Domain** (folder) is a catch-all area used to denote "business logic lives here". It's over-arching and doesn't perfectly fit in some cases, but most of the time works. 

**Entities** are classes that are 1-to-1 representations of database tables. They contain **absolutely no** logic and are intentionally kept barebones. They define a class structure that **DBAL** (Doctrine Database Access Layer, a wrapper around **PDO** - which is PHP Data Objects) can use to automatically map data from `SELECT` queries into.

**Migrations** (folder) just contains all database operations in **SQL** to allow sites to migrate to new versions.

**Models** are classes that provide a go-between for business logic code and the database. This data only exists in memory.

**Services** are (for a lack of a better term) classes that provide a public interface to other classes for performing actions that require more than the most basic amount of business logic. Most of the time a Controller will invoke a Service in order to fulfill a request.

**Validators** are classes that perform data validation to ensure all data sent to the API is valid. Defining maximum field lengths or that only valid values are present on particular fields are all handled within validators.

**Repositories** are classes that act as an abstraction to the database. Needing to read or write to the database should always occur in a Repository, but should be kept relatively straight forward in terms of just in/out database operations. 99-100% of the API's **SQL** should live in these files.

### Front end (Angular)
The front end of the app utilizes the [AngularJS](http://www.angularjs.org) framework, which means it's a [single page app](https://en.wikipedia.org/wiki/Single-page_application) (one data-heavy network load up front for speedier, more user-friendly data-only API requests for the rest of the time). `index.html` loads **once**, and then from then on the front end app asks the API to dynamically update the page from there.

The app also uses [Twitter Bootstrap](http://getbootstrap.com/) in order to provide a nicer more standardized user interface that leverages some really nice interface components. Bootstrap is known for allowing dead-simple responsive layouts - a single HTML file can describe where everything needs to be on-screen for both a smartphone screen (600 pixels wide) or a ultra high-end desktop monitor (2,800+ pixels wide) - and update dynamically as the screen size changes.

The app's Javascript is actually not written in Javascript - its source is written in [Coffeescript](http://coffeescript.org/) and then is transpiled into Javascript that browsers understand.

Additionally, the CSS for the app is written in [LESS](http://lesscss.org/) and then processed into browser-readable CSS.

`/coffee/app.coffee` is the definition of the main `app` module, and it defines all third party module dependencies that other classes may invoke (Angular leverages dependency injection)

`/coffee/router.coffee` defines all "page" routes within the Angular single page app. This ties a URL route (in the browser's address bar) to a Controller file, and marries both of them to an HTML Template file. So going to `/draft/3` means that `DraftIndexController` is handed a `draft_id` parameter value of `3`, and `/templates/draft/index.html` is the template the user is rendered. Simple, right? :)

`/coffee/config/` contains files that configure the app once, so it includes putting the JWT auth token (if present) on every request.

`/coffee/constants/` contains values that will not change, but are defined to help skirt the [magic string](https://en.wikipedia.org/wiki/Magic_string) anti-pattern

`/coffee/controllers/` are classes that are responsible for handling user requests to load a new "page" of the application. They contain interaction logic that includes making calls to the API for data that views (**templates** in the Angular world) will need

`/coffee/directives/` are Angular-specific classes that encapsulate both a specific piece of front end HTML as well as a lightweight piece of Javascript backend to handle user interaction code. In most instances you can consider a custom directive a *cookie cutter* of HTML and Javascript goodness :)

`/coffee/listeners/` are containers of logic within Angular's `run` block that contain an event listener using Angular's built-in [pub-sub](https://en.wikipedia.org/wiki/Publish%E2%80%93subscribe_pattern) architecture. One event per file at most, this is code that will occur asynchronously.

`/coffee/services/` are classes that encapsulate business logic that Controllers shouldn't need to know

## Contributing
Thanks for considering helping out and contributing. Open source software is made great by the fact that the source code is open to all, and an unlimited number of people can jump in and help where they can.

That being said, as the project owner and sole developer, I *cannot treat the planning and development of this project as a democracy*, and will reject or deny otherwise good code or great features that provide value to *someone*. 

This doesn't mean I think you're a *bad person* or a *not very good programmer*. Hogwash to that! It's just one of the realities of running a software project - a sharp vision and focus must always be in use in order to maintain a quality piece of software. You are free to continue to maintain your own fork of the codebase as long as you abide by the guidelines of the open source license PHP Draft is licensed under. Though I may not use your code, I urge you to continue sharing it with the world for as long as it's still valuable to *someone*. If it helps one other person, then open source has worked :)

If you're unsure of where to start, or are not familiar with the tools, languages and patterns I use - **please reach out**! I am happy to help if you're interested in learning, and I am even more compelled to do so if you mean to leverage your new knowledge to help other humans! :)

If you can't or won't contribute code, but have a great idea - I want that too! Submit an issue on the project and I'll mark it as a feature request. I continually maintain a **backlog** of features and improvements, and will re-prioritize constantly in order to develop the "next best thing".

### Defining and adding your own leagues and players
The main file to pay attantion to when creating your own leagues is `DraftDataRepository.php`, located in `/api/Domain/Repositories`.

Assumptions: You are doing a league that picks PLAYERS who have POSITIONS and play on TEAMS.  The LEAGUE is sorted by multiple coaches, each drafting a certain number of PLAYERS to fill the required POSITIONS on their roster. 

+ First, declare your league teams and positions as private array variables.  I'm going to use Curling as an example.
`private $curling_teams;`
`private $alma_mater_positions;`

+ OK.  You've declared your variables that will contain arrays of your teams and positions.  Let's fill those in now.

Let's start by making an array of curling positions.  Make sure your position abbreviation is three letters or less, or else the angular frontend might get a liitle wonky.
```
    $this->curling_positions = array(
        "LED" => "Lead",
        "2ND" => "Second",
        "3RD" => "Third",
        "SKP" => "Skip"
    );
```

+ Excellent.  Now let's make some curling teams.  I'm going to use the teams for the 2016 Scotties Tournament of Hearts as an example.  Make sure your team abbreviation is three letters or less, or else the angular frontend might get a liitle wonky.
```
    $this->curling_teams = array(
        "AB" => "Alberta",
        "BC" => "British Columbia",
        "CA" => "Canada",
        "MB" => "Manitoba",
        "NB" => "New Brunswick",
        "NL" => "Newfoundland and Labrador",
        "NON" => "Northern Ontario",
        "NWT" => "Northwest Territories",
        "HUR" => "Hurricanes",
        "NS" => "Nova Scotia",
        "NV" => "Nunavut",
        "ON" => "Ontario",
        "PEI" => "Prince Edward Island",
        "QC" => "Quebec",
        "SK" => "Saskatchewan",
        "YK" => "Yukon"
    );
```
+ Good.  Now, let's assign colors to the positions, to make the draft easier to follow, and to make the draft board look pretty.
Note:  All of the position colors are kept in the same array (`$this->position_colors`), selecting from the array of possible colors in `$this->colors`.  Add your position colors to the end of the `position_colors` array.
```
    $this->position_colors = array(
      //Lots of other positions here.  Make sure to add a comma to the last position before adding yours.
        "FH" => $this->colors['light_purple'],  //This is the last position from the previous league.  Add the comma.
        //Curling League
        "LED" => $this->colors['light_blue'], //LT BLUE
        "2ND" => $this->colors['light_orange'], //LT ORANGE
        "3RD" => $this->colors['light_yellow'], //LT YELLOW
        "SKP" => $this->colors['light_red'] //LT RED
    );
```
Note that if another league has the same position abbreviation, that's OK, just don't try reassign that position abbreviation to another color.  It's probably best to mention that you're re-using the position in an inline comment.

+ Now, your league exists, but you need to add it to the list of leagues.  Find the array constructor for `$this->sports` and add a three letter abbreviation to inclcate your league, as such:
```
    $this->sports = array(
      "NFL" => "Football (NFL)",
      "NFLE" => "Football - Extended Rosters (NFL)",
      "MLB" => "Baseball (MLB)",
      "NBA" => "Basketball (NBA)",
      "NHL" => "Hockey (NHL)",
      "S15" => "Rugby (Super 15)",  // Add the comma at the end of the previous array
      "CUR" => "Curling League (Scotties Tournament of Hearts)"  //Here's the new league.
    );
```

+ Excellent.  Now link your teams to your league.  This happens in `public function GetTeams($pro_league)`.  Add your league to the end of the case statement in the function:
```
  public function GetTeams($pro_league) {
    switch (strtolower($pro_league)) {
      case 'nhl':
      case'hockey':
        return $this->nhl_teams;
        break;
      case 'nfl':
      case 'football':
        return $this->nfl_teams;
        break;
      case 'mlb':
      case 'baseball':
        return $this->mlb_teams;
        break;
      case 'nba':
      case 'basketball':
        return $this->nba_teams;
        break;
      case 's15':
        return $this->super_rugby_teams;
        break;
      case 'cur':
        return $this->curling_teams;
        break;
    }
  }
```
 
+ OK.  Let's link your positions to your league.  This happens in `public function GetPositions($pro_league)`.  Add your league to the end of the case statement in the function:
```
  public function GetPositions($pro_league) {
    switch (strtolower($pro_league)) {
      case 'nhl':
      case 'hockey':
        return $this->nhl_positions;
        break;
      case 'nfl':
      case 'football':
        return $this->nfl_positions;
        break;
      case 'nfle':
        return $this->extended_nfl_positions;
        break;
      case 'mlb':
      case 'baseball':
        return $this->mlb_positions;
        break;
      case 'nba':
      case 'basketball':
        return $this->nba_positions;
        break;
      case 's15':
        return $this->super_rugby_positions;
        break;
      case 'cur':
        return $this->curling_positions;
        break;
    }
  }
```

+ Great!  You have a team.  Now you can add players by uploading a correctly formed CSV file.  Here's a description of the format.  Each field is encased by double quotes, separated by one semicolon, with a header row at the top.  The format of the player data is as follows:
```
"Player";"Position";"Team"
"PlayerLastName,PlayerFirstName";"POS";"TEM"
```
Make sure that your player has both a first and last name, or the front-end validation logic will prevent your player from being drafted.  Use the abbreviation for the position and the team, not the full name.  Save your results when you are done.

+ Once your CSV has been created, you may add the players within PHPDraft by deploying your new code, logging in as an admin, and selecting "Admin Stuff->Player Data".  Select the appropriate league, upload the CSV, and watch PHPDraft take care of the draft.  I'd suggest doing a test draft with a couple teams, and seeing if everything is appropriately selected, and everything shows up correctly on the draft board.

+ Bask in the splendor of your new league.  Create a league that uses the new sport, hold a draft, and enjoy!
