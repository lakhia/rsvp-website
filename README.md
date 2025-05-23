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
2. Run `npm run dev` which will serve local files at: [http://127.0.0.1:3000](http://127.0.0.1:3000)
3. Make changes to files under `app/` directory. Any changes are detected and send reload event to browser

# Deployment

  * When ready to deploy, run "npm run build" and publish all files in
    `build/` directory except for hidden .tmp sub-directory
  * Run deployment squasher and templater:
     cd rsvp; perl deploy.pl config.yaml
    For an example configuration, see `config/example.yaml`

# Files

  * `migration/*.sql` used to setup and migrate database
  * `app/*.html` where `index.html` is the main web application and the other files are specific views for individual routes
  * `app/js/*.js` where `main.js` has the main controllers and the remaining files has view specific angular controllers
  * `app/*.php` are backend PHP files that serve JSON to the front-end

# Internal dependencies

  * Uses `angular-ui-router` to route to different parts of the app
  * Uses `bootstrap` to style buttons, tables, etc.
  * Uses `loading-bar` to show progress bar at top
