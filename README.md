# About

  This project is to collect RSVP responses so that cooking and filling teams
  can plan accordingly.

# System Requirements

## Front-end

The website uses the following on the client side:

* HTML
* CSS
* Javascript
* [Angular](https://angularjs.org/) 1.2.x or higher
* [Angular ui-router](https://github.com/angular-ui/ui-router/wiki)

## Back-end

On the backend, it currently requires:

* PHP 8.1
* MySQL 5.7
* Web server

# Dev Environment Setup

## Pre-requisites
1. Install [mysql](https://dev.mysql.com/downloads/mysql/) and run `mysql -v -u root < migration/*.sql` to bootstrap the database
2. Install php - MacOS (Homebrew) - `brew install php`
3. Install [node](https://nodejs.org/en/download/package-manager/)
4. Install [npm](https://docs.npmjs.com/getting-started/installing-node)

## Build and run

1. Install all project dependencies using: npm install
2. Run `gulp serve &` which will serve local files at: [http://127.0.0.1:8010](http://127.0.0.1:8010)
3. Make changes to files under app/ directory. Any changes are detected andsend reload event to browser

# Deployment

  * When ready to deploy, run "gulp" and publish all files in build directory
    except for hidden .tmp sub-directory
  * Run deployment squasher:
    `cd rsvp; perl deploy.pl <dbhost> <dbusername> <dbpassword> <dbname> <adminemail> 

# Files

  * database_*.sql used to setup database
  * app/index.html is the single web page
  * app/js/main.js is the main angular controllers
  * other app/js/*.js files contain view-specific controllers
  * app/*.php files serve JSON

# Internal dependencies

  * Uses angular-ui-router to route to different parts of the app
  * Uses bootstrap to style buttons, tables, etc.
  * Uses loading-bar to show progress bar at top

# Note about admin email

Using the `adminemail` for login with ANY thaali number allows you to assume the role of any thaali number. You can make changes on their behalf without being subjected to the limitations of the regular user.

Keep the `adminemail` safe.

_We want to move away from this approach in the long-term, PRs are welcome_