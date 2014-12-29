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

  Optionally, use build system by installing:
  * [npm](https://docs.npmjs.com/getting-started/installing-node)
  * [grunt](http://gruntjs.com/getting-started#working-with-an-existing-grunt-project)
  * Run `grunt` and output will be saved in `build` directory
  * Run `grunt clean` to clean output directory

# Files

  * database_setup.sql used to setup database
  * index.html is the single web page
  * rsvp.js has the angular controller
  * several php files serve JSON to angular
  * Uses bootstrap to style buttons, tables, etc.
