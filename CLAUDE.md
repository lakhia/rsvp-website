# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About

This is an RSVP website for collecting meal responses and helping cooking/filling teams plan accordingly. It's a community jamaat application for managing thaali (meal) distribution.

## Development Commands

### Local Development (without Docker)
```bash
npm install                  # Install dependencies
npm run dev                  # Start dev server (Vite at http://localhost:5173, PHP at http://localhost:8010)
npm run build                # Build production files to build/ directory
npm run build:single         # Build minified single-file output (build/index.html)
npm run serve-prod           # Build and serve production version
```

**Note:** Use `http://localhost:5173` (Vite) during development — it proxies PHP requests to port 8010 automatically and provides HMR for instant CSS/JS updates. PHP file changes are also watched and auto-copied to `build/`.

The `npm run dev:docker` command (used by Docker):
1. Copies PHP files to `build/` and runs `deploy.pl` for env substitution
2. Starts PHP backend serving from `build/` (`php -S localhost:8010`)
3. Starts Vite dev server at port 5173 (proxies PHP calls to localhost:8010)

### Docker Development

#### First-time Setup
1. Copy the example environment file to create your local configuration:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env` to customize database credentials, email addresses, and other settings
3. Start the containers:
   ```bash
   docker compose up -d --build
   ```

#### Development Mode (Live Reload with Build) - DEFAULT
The default `docker-compose.yml` runs in **development mode with live reload and auto-rebuild**:

```bash
docker compose up -d --build      # Start with live reload
docker compose logs -f app        # Watch logs (see build + browser-sync output)
```

**Access**: http://localhost:5173 (Vite dev server with HMR)

**Features**:
- Vite HMR for instant Svelte/JS/CSS updates
- PHP file changes auto-copied to `build/`
- Runs `deploy.pl` to substitute template variables from `.env` on startup
- PHP backend on port 8010, proxied transparently by Vite

**How it works**:
1. Uses `docker-entrypoint-dev.sh` which runs `npm run dev:docker`
2. Copies PHP files to `build/` and runs `deploy.pl` substitution
3. Volume mount (`.:/var/www/html`) syncs local files to container
4. PHP server runs on port 8010 serving from `build/`
5. Vite dev server runs on port 5173, proxying `.php` requests to port 8010

#### Production Mode (Test Production Builds)
To test the full production build locally, use `docker-compose.prod.yml`:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

**Access**: http://localhost:8080 (Apache serving production build)

**Features**:
- Runs full `npm run build` + `perl deploy.pl .env` on startup
- Serves optimized/minified files from `build/` directory via Apache
- Tests production-like environment locally
- All source files and configuration (including `.env`) are baked into the Docker image
- No volume mounts - true production-like behavior
- No live reload or file watching - code changes require image rebuild with `--build` flag

**To stop production mode and return to dev:**
```bash
docker compose -f docker-compose.prod.yml down
docker compose up -d --build
```

#### Daily Commands
```bash
# Development (default)
docker compose up -d --build     # Start dev containers with live reload
docker compose down              # Stop containers
docker compose down -v           # Stop and remove volumes (resets database)
docker compose logs -f app       # View application logs (streaming)
docker compose restart app       # Restart app container

# Production mode (explicit)
docker compose -f docker-compose.prod.yml up -d --build  # Start prod mode
docker compose -f docker-compose.prod.yml down           # Stop prod mode
```

#### Configuration
The Docker setup uses `.env` for all application configuration. This file is gitignored and should be created from `.env.example`. Key configuration sections include:
- **Database**: Connection settings (DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD, DB_ROOT_PASSWORD)
- **Email**: Admin, contact, and secretary email addresses (EMAIL_ADMIN, EMAIL_CONTACT, EMAIL_SECRETARY)
- **RSVP Settings**: Cutoff mode (daily/weekly), timezone, cutoff times (RSVP_CUTOFF_MODE, RSVP_TIMEZONE, etc.)
- **Application**: App name, secretary title, external links (APP_NAME, SECRETARY_TITLE, LINK_PLANNING, LINK_FEEDBACK)

The same .env format is used for both Docker and traditional deployments, eliminating configuration drift.

### PHP Testing

PHPUnit is used for PHP unit tests.

```bash
composer install         # Install PHPUnit (first time only)
./vendor/bin/phpunit     # Run all tests
```

Tests live in `tests/`. The bootstrap file `tests/bootstrap.php` sets timezone to UTC, loads `config.php`, `sizes.php`, `estimation.php`, and `cutoff.php`, and defines a minimal `Helper` stub so `estimation.php` can be loaded without `auxil.php`.


### Build System Details
- `npm run build` - Runs Vite build for frontend, then copies PHP files to `build/`
- `npm run build:single` - Same as above but produces a single minified `build/index.html` (all JS/CSS inlined)
- `npm run build:php` - Copies PHP files from `app/` to `build/`
- `npm run watch:php` - Watches `app/*.php` and auto-copies changes to `build/` (used in `dev`)

## Architecture Overview

### Frontend (Svelte 5 / SvelteKit SPA)
- **Framework**: Svelte 5 with SvelteKit, using `adapter-static` with `fallback: index.html` (client-side SPA)
- **SSR**: Disabled globally via `src/routes/+layout.js` (`export const ssr = false`) — the app relies on localStorage and client-side cookies
- **Routing**: SvelteKit file-based routing under `frontend/src/routes/`
- **Reactivity**: Svelte 5 runes (`$state`, `$derived`, `$effect`) throughout
- **Common Patterns**:
  - `PageState` class (`src/lib/PageState.svelte.js`) manages loading/saving/error state per page
  - Authentication uses localStorage (token, thaali, email) checked via `src/lib/auth.js`
  - All HTTP requests use `get`/`post` helpers in `src/lib/api.js`, which include offset and date params

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

1. **State Management**: Each view fetches data via HTTP GET, modifies locally, submits via POST
2. **Change Detection**: Pages track a `dirty` flag and warn users about unsaved changes before navigating away
3. **Pagination**: Most views support offset-based pagination via URL params
4. **Size Eligibility**: Users can only select meal sizes equal to or one size larger than their default (XS→S, S→M, M→L, L→XL), admins can select any size
5. **Build Process**: Vite bundles and minifies; `build:single` additionally inlines everything into one `index.html` via `scripts/inline-html.js`

## Configuration

### Unified .env Configuration
Both Docker and traditional deployments use the same .env-based configuration system:

- `.env.example` - Template for database credentials, email addresses, and app settings (checked into git)
- `.env` - Your local configuration file (gitignored, create from .env.example)
- `deploy.pl` - Perl script that processes .env config and templates values into files in target directory

The configuration workflow is identical for both deployment methods:
1. Copy `.env.example` to `.env`
2. Customize `.env` with your settings
3. Run `deploy.pl .env [target-dir]` where target-dir defaults to `build/`
   - Processes files in target directory (in-place substitution)
   - Done automatically in Docker via `docker-entrypoint.sh` and `docker-entrypoint-dev.sh`

This approach eliminates configuration drift between Docker and traditional deployments.

**Template Variable Substitution:**
The `deploy.pl` script performs regex-based substitution on files in the target directory:
- `oo_db.php`: Database credentials (DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD)
- `auxil.php`: Email addresses (EMAIL_ADMIN)
- `index.html`: Application settings (APP_NAME, LINK_PLANNING, LINK_FEEDBACK, email addresses, etc.)

Source files in `app/` should contain the original placeholder values. The build process copies these to `build/`, then `deploy.pl` performs substitution. This keeps version-controlled source files clean while generating configured runtime files.

## Important Notes

- All PHP endpoints expect token authentication via cookies except `login.php`
- Docker setup auto-runs migrations on first startup via MySQL's `/docker-entrypoint-initdb.d`
- `scripts/inline-html.js` post-processes the single-file build: collapses HTML whitespace, strips newlines from inlined `<script>` blocks (JS is already minified by Vite/oxc — do not set `minifyJS: true`)
