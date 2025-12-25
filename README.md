# About

This project is an RSVP website for collecting meal responses so that cooking and filling teams can plan accordingly. It's a community jamaat application for managing thaali (meal) distribution.

# System Requirements

* PHP 8.1+
* MySQL 5.7+
* Node.js (for build process)
* Docker and Docker Compose (for containerized development)

# Development

## Local Development (without Docker)

*Prerequisites*
1. Install [mysql](https://dev.mysql.com/downloads/mysql/) and run `mysql -v -u root < migration/*.sql` to bootstrap the database
2. Install php - MacOS (Homebrew) - `brew install php`
3. Install [node](https://nodejs.org/en/download/package-manager/)
4. Install [npm](https://docs.npmjs.com/getting-started/installing-node)

### Setup
```bash
npm install                  # Install dependencies
cp .env.example .env         # Create local configuration
# Edit .env with your settings
```

### Commands
```bash
npm run dev                  # Start dev server at http://127.0.0.1:3000
npm run build                # Build production files to build/ directory
npm run serve-prod           # Build and serve production version
```

## Docker Development (Recommended)

### First-time Setup
1. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env` to customize database credentials, email addresses, and other settings
3. Start the containers:
   ```bash
   docker compose up -d --build
   ```

The application will be available at [http://localhost:3000](http://localhost:3000) with live reload.

### Development Mode (Default)
The default setup runs in development mode with auto-rebuild and live reload:

```bash
docker compose up -d --build      # Start with live reload
docker compose logs -f app        # Watch logs
docker compose down               # Stop containers
docker compose down -v            # Stop and remove volumes (resets database)
```

**Features:**
- Auto-rebuilds on source file changes (JS, CSS, HTML, PHP)
- Browser auto-reloads after rebuild completes
- Template variable substitution from `.env` via `deploy.pl`
- Access at [http://localhost:3000](http://localhost:3000)

### Production Mode (Test Production Builds)
To test the full production build locally:

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml down
```

**Access:** [http://localhost:8080](http://localhost:8080) (Apache serving production build)

## Configuration

Both Docker and traditional deployments use the same `.env`-based configuration system:

- `.env.example` - Template file (checked into git)
- `.env` - Your local configuration (gitignored, create from .env.example)
- `deploy.pl` - Script that processes `.env` and substitutes template variables

Key configuration sections:
- **Database**: DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD, DB_ROOT_PASSWORD
- **Email**: EMAIL_ADMIN, EMAIL_CONTACT, EMAIL_SECRETARY
- **RSVP Settings**: RSVP_CUTOFF_MODE, RSVP_TIMEZONE, cutoff times
- **Application**: APP_NAME, SECRETARY_TITLE, LINK_PLANNING, LINK_FEEDBACK

# Deployment

## Pre-requisites
Complete Local Development Prerequisites.

1. Build the production files:
   ```bash
   npm run build
   ```

2. Run the deployment script to substitute environment variables:
   ```bash
   perl deploy.pl .env
   ```

3. Deploy all files from the `build/` directory to your web server

For detailed architecture and development information, see [CLAUDE.md](CLAUDE.md).
