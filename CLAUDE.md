# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About

This is an RSVP website for collecting meal responses and helping cooking/filling teams plan accordingly. It's a community jamaat application for managing thaali (meal) distribution.

## Development Commands

### Local Development (without Docker)
```bash
npm install                  # Install dependencies
npm run dev                  # Start dev server at http://127.0.0.1:3000
npm run build                # Build production files to build/ directory
npm run serve-prod           # Build and serve production version
```

The `npm run dev` command runs both the PHP backend (`php -S localhost:8010`) and browser-sync proxy on port 3000 with auto-reload on file changes.

### Docker Development

#### First-time Setup
1. Copy the example environment file to create your local configuration:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env` to customize database credentials, email addresses, and other settings
3. Start the containers:
   ```bash
   docker-compose up -d --build
   ```

#### Daily Commands
```bash
docker-compose up -d --build # Build and start containers
docker-compose down          # Stop containers
docker-compose down -v       # Stop and remove volumes (resets database)
docker-compose logs app      # View application logs
docker-compose logs db       # View database logs
```

Access the application at http://localhost:8080 when using Docker.

#### Configuration
The Docker setup uses `.env` for all application configuration. This file is gitignored and should be created from `.env.example`. Key configuration sections include:
- **Database**: Connection settings (DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD, DB_ROOT_PASSWORD)
- **Email**: Admin, contact, and secretary email addresses (EMAIL_ADMIN, EMAIL_CONTACT, EMAIL_SECRETARY)
- **RSVP Settings**: Cutoff mode (daily/weekly), timezone, cutoff times (RSVP_CUTOFF_MODE, RSVP_TIMEZONE, etc.)
- **Application**: App name, secretary title, external links (APP_NAME, SECRETARY_TITLE, LINK_PLANNING, LINK_FEEDBACK)

The same .env format is used for both Docker and traditional deployments, eliminating configuration drift.

### Build System Details
- `npm run build:js` - Minifies and concatenates JS files (route.js first, then app/js/*, app/lib/*, templates)
- `npm run build:css` - Processes and minifies CSS
- `npm run build:php` - Copies PHP files to build directory
- `npm run build:templates` - Minifies HTML templates and generates templates.js
- `npm run clean` - Removes build artifacts

## Architecture Overview

### Frontend (AngularJS 1.x SPA)
- **Router**: Uses `angular-ui-router` for state-based routing (see `app/js/route.js`)
- **Main App Module**: Defined in `route.js` as `angular.module("rsvp", ['ui.router', 'ngCookies', 'angular-loading-bar'])`
- **Controllers**: Each view has a dedicated controller in separate JS files (event.js, family.js, rsvp.js, etc.)
- **Common Patterns**:
  - `$rootScope.init()` (in main.js) is called by most controllers to setup scope, fetch data, handle navigation warnings
  - Authentication uses cookies (token, thaali, email) stored in localStorage
  - All HTTP requests include offset and date params for pagination/filtering

### Backend (PHP)
- **Entry Point**: Each PHP file in `app/` directory serves JSON responses
- **Database Layer**: `oo_db.php` provides database connection wrapper
- **Helper Functions**: `auxil.php` contains authentication and common utilities:
  - `Helper::verify_token()` - Validates user session
  - `Helper::is_admin()` - Checks admin privileges
  - `Helper::create_token()` - Generates auth tokens
- **Authentication**: MD4 hash-based token system using thaali ID + server name + email

### Database Schema
- **family**: User records (thaali ID, name, email, phone, area, size)
- **events**: Event dates with menu details and enabled status
- **rsvps**: Junction table linking thaali_id to event dates with RSVP boolean

Migrations are in `migration/` directory, run sequentially (01_setup.sql through 09_its.sql).

### Key Architectural Patterns

1. **State Management**: Each view receives data via HTTP GET, modifies locally, submits via POST
2. **Change Detection**: Controllers track `scope.changed` to warn users about unsaved changes
3. **Pagination**: Most views support offset-based pagination via URL params
4. **Size Eligibility**: Users can only select meal sizes equal to or one size larger than their default (XS→S, S→M, M→L, L→XL), admins can select any size
5. **Build Process**: Production build concatenates all JS (starting with route.js), minifies, and inlines templates

## Configuration

### Unified .env Configuration
Both Docker and traditional deployments use the same .env-based configuration system:

- `.env.example` - Template for database credentials, email addresses, and app settings (checked into git)
- `.env` - Your local configuration file (gitignored, create from .env.example)
- `deploy.pl` - Perl script that processes .env config and templates values into PHP files

The configuration workflow is identical for both deployment methods:
1. Copy `.env.example` to `.env`
2. Customize `.env` with your settings
3. Run `deploy.pl .env` (done automatically in Docker via `docker-entrypoint.sh`)

This approach eliminates configuration drift between Docker and traditional deployments.

## Important Notes

- The app uses AngularJS 1.x `.success()/.error()` callback pattern (deprecated but in use)
- Build order matters: `route.js` must be first in concatenation to define the angular module
- Templates are minified and converted to angular template cache in build process
- All PHP endpoints expect token authentication via cookies except login.php
- Docker setup auto-runs migrations on first startup via MySQL's `/docker-entrypoint-initdb.d`
