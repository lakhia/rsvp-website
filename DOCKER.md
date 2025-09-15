# Docker Setup for RSVP Website

This document describes how to run the RSVP website using Docker.

## Prerequisites

- Docker
- Docker Compose

## Quick Start

1. Clone the repository:
   ```bash
   git clone https://github.com/lakhia/rsvp-website.git
   cd rsvp-website
   ```

2. Build and start the containers:
   ```bash
   docker-compose up -d --build
   ```

3. Access the application:
   - Web application: http://localhost:8080
   - Database: localhost:3306

## Configuration

The Docker setup uses the following configuration:

- PHP application runs on port 8080
- MySQL database runs on port 3306
- Database credentials are defined in docker-compose.yml
- Application configuration is in config/docker.yaml

## Development

- The application code is mounted as a volume, so changes are reflected immediately
- Node.js dependencies are installed during container build
- Database migrations are automatically run on first startup

## Troubleshooting

1. If the application can't connect to the database:
   - Check if MySQL container is running: `docker-compose ps`
   - Check MySQL logs: `docker-compose logs db`

2. If the application shows errors:
   - Check application logs: `docker-compose logs app`
   - Verify config/docker.yaml settings

3. To rebuild the containers:
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```

## Database Management

- Database data persists in a Docker volume
- To reset the database:
  ```bash
  docker-compose down -v
  docker-compose up -d
  ```

## Cleanup

To stop and remove all containers and volumes:
```bash
docker-compose down -v
```

