# Run doc — IT Support Helpdesk (PHP + Docker)

No Node.js / npm involved. This is a PHP 8.2 + MySQL 8.0 app served through Docker Compose.

## Reproduce artifacts

No uncommitted artifacts needed:
- DB credentials/env live inline in `docker-compose.yml` (no `.env` files).
- First build needs the image: `docker compose build` (installs `pdo_mysql`, `gd`).
- `database/schema.sql` is auto-imported by MySQL on first init of the `db_data` volume
  (`/docker-entrypoint-initdb.d/`). If you need a truly fresh DB, `docker compose down -v`
  wipes the volume — only do that deliberately.

## Run the server

1. Docker Desktop must be running (start the app, then wait for `docker info` to answer).
2. From the project root:

   ```bash
   docker compose up -d --build   # first time; afterwards plain `docker compose up -d` is enough
   ```

3. Wait until the URL answers (port 8080 default, defined in `docker-compose.yml`):

   ```bash
   curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/   # expect 302 -> /auth/login.php
   ```

4. URLs:
   - App:        http://localhost:8080   (login: `admin` / `1234`)
   - phpMyAdmin: http://localhost:8081   (root / rootpass)

The web container mounts the repo at `/var/www/html`, so code edits appear on refresh
(no rebuild needed unless the Dockerfile itself changes).
