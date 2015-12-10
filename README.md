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
  * Install all project dependencies using: npm install
  * Run `gulp serve &` which will serve local files at:
     * http://127.0.0.1:8010  
  * Make changes to files under app/ directory. Any changes are detected and
    send reload event to browser
  * When ready to deploy, run "gulp" and publish all files in build directory
    except for hidden .tmp sub-directory

# Files

  * database_setup.sql used to setup database
  * app/index.html is the single web page
  * app/js/main.js is the main angular controllers
  * other app/js/*.js files contain view-specific controllers
  * several php files serve JSON to angular

# Internal dependencies

  * Uses angular-ui-router to route to different parts of the app
  * Uses bootstrap to style buttons, tables, etc.
  * Uses loading-bar to show progress bar at top
