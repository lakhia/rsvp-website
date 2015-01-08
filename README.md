# About

  This project is to collect RSVP responses so that cooking and filling teams
  can plan accordingly.

# Requirements

  The website uses the following on the client side:

  * HTML
  * CSS
  * Javascript
  * [Angular](https://angularjs.org/) 1.2.x or higher
  * [Angular ui-router](https://github.com/angular-ui/ui-router/wiki)

  On the backend, it currently requires:

  * PHP 5.5
  * MySQL
  * Web server

  Use build system:

  * Install [npm](https://docs.npmjs.com/getting-started/installing-node)
  * Install [grunt](http://gruntjs.com/getting-started#working-with-an-existing-grunt-project)
  * Run `grunt watch &` and output will be generated in `build` directory each
    time any changes are detected and send reload event to browser
  * Run `grunt serve &` which will run a webserver and open main page in browser

# Files

  * database_setup.sql used to setup database
  * index.html is the single web page
  * main.js has the main angular controller
  * other javascript files contain view-specific controllers
  * several php files serve JSON to angular
  * Uses bootstrap to style buttons, tables, etc.
