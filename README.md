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

<<<<<<< HEAD
On the backend, it currently requires:

  * PHP 5.5
  * MySQL
=======
  On the backend, it currently requires:

  * PHP 5.5
  * MySQL
  * Web server

  Use build system (optional):

  * Install [npm](https://docs.npmjs.com/getting-started/installing-node)
  * Install [grunt](http://gruntjs.com/getting-started#working-with-an-existing-grunt-project)
  * Install [live-reload plugin](http://feedback.livereload.com/knowledgebase/articles/86242-how-do-i-install-and-use-the-browser-extensions-)
  * Run `grunt watch &` and output will be generated in `build` directory each
    time any changes are detected and send reload event to browser plugin
  * Run `grunt serve &` which will run a webserver and open main page in browser
>>>>>>> remotes/origin/experimental

# Files

  * database_setup.sql used to setup database
  * index.html is the single web page
  * rsvp.js has the angular controller
<<<<<<< HEAD
  * several php files serve JSON to angular (with one login.php exception that
    serves text)
=======
  * several php files serve JSON to angular
>>>>>>> remotes/origin/experimental
  * Uses bootstrap to style buttons, tables, etc.
