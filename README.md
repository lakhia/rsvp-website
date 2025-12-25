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

## Prerequisites
- Install [Docker](https://docs.docker.com/get-docker/) and Docker Compose

## First-time Setup
1. Copy the example environment file to create your local configuration:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env` to customize database credentials, email addresses, and other settings
3. Start the containers:
   ```bash
   docker-compose up -d --build
   ```

The application will be available at [http://localhost:8080](http://localhost:8080)

## Daily Development Commands
```bash
docker-compose up -d --build  # Build and start containers
docker-compose down           # Stop containers
docker-compose down -v        # Stop and remove volumes (resets database)
docker-compose logs app       # View application logs
docker-compose logs db        # View database logs
```

## Configuration
The Docker setup uses `.env` for all application configuration. This file is gitignored and should be created from `.env.example`. Key configuration sections include:
- **Database**: Connection settings (DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD, DB_ROOT_PASSWORD)
- **Email**: Admin, contact, and secretary email addresses
- **RSVP Settings**: Cutoff mode (daily/weekly), timezone, cutoff times
- **Application**: App name, secretary title, external links

# Deployment

  * When ready to deploy, run "npm run build" and publish all files in
    `build/` directory except for hidden .tmp sub-directory
  * Run deployment squasher and templater:
     cd rsvp; perl deploy.pl .env
    For an example configuration, see `.env.example`

# Files

  * `migration/*.sql` used to setup and migrate database
  * `app/*.html` where `index.html` is the main web application and the other files are specific views for individual routes
  * `app/js/*.js` where `main.js` has the main controllers and the remaining files has view specific angular controllers
  * `app/*.php` are backend PHP files that serve JSON to the front-end

# Internal dependencies

  * Uses `angular-ui-router` to route to different parts of the app
  * Uses `bootstrap` to style buttons, tables, etc.
  * Uses `loading-bar` to show progress bar at top
