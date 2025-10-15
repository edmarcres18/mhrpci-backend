# Docker Setup Guide

## Prerequisites
- Docker Desktop installed
- Docker Compose installed

## Quick Start

1. **Build and start the containers:**
   ```bash
   docker-compose up -d --build
   ```

2. **Access the application:**
   - Application: http://localhost:4003
   - PHPMyAdmin: http://localhost:4004
   - MySQL: localhost:3037
   - Redis: localhost:6382

## Services

The Docker setup includes the following services:

- **app**: Laravel application (PHP-FPM)
- **webserver**: Nginx web server
- **db**: MySQL 8.0 database
- **redis**: Redis 7 for caching, sessions, and queues
- **phpmyadmin**: Database management interface
- **scheduler**: Laravel task scheduler

### Scheduler (Supervisor)

The `scheduler` service runs Laravel's scheduler in daemon mode using Supervisor.

- Command: `php artisan schedule:work`
- Process manager: `supervisord`
- Healthcheck: verifies `laravel-scheduler` is RUNNING via `supervisorctl`

Verify scheduler status:

```bash
docker-compose ps scheduler
docker-compose logs -f scheduler
docker-compose exec scheduler supervisorctl -c /etc/supervisor/supervisord.conf status
```

## Environment Configuration

All services are configured via the `.env` file. Key variables:

```env
# External ports (host machine)
NGINX_PORT=4003
DB_EXTERNAL_PORT=3037
REDIS_EXTERNAL_PORT=6382
PHPMYADMIN_PORT=4004

# Internal ports (container network)
DB_HOST=db
DB_PORT=3306
DB_DATABASE=mhrpci
DB_USERNAME=mhrpci_user
DB_PASSWORD=mhrpci-admin@2025

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
```

## Common Commands

### Start containers
```bash
docker-compose up -d
```

### Stop containers
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f
docker-compose logs -f app
docker-compose logs -f webserver
```

### Execute artisan commands
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Access container shell
```bash
docker-compose exec app bash
```

### Rebuild containers
```bash
docker-compose down
docker-compose up -d --build
```

If only the scheduler changed, you can rebuild it specifically:

```bash
docker-compose build scheduler && docker-compose up -d scheduler
```

### Remove all data (including volumes)
```bash
docker-compose down -v
```

## Troubleshooting

### Database connection issues
Ensure MySQL is fully started:
```bash
docker-compose logs db
```

Wait for healthcheck to pass:
```bash
docker-compose ps db
```

### Permission errors
Fix storage permissions:
```bash
docker-compose exec app chmod -R 777 /var/www/html/storage
docker-compose exec app chmod -R 777 /var/www/html/bootstrap/cache
```

### Cache issues
Clear all caches:
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear
```

### Composer dependency issues
```bash
docker-compose exec app composer install
docker-compose exec app composer update
```

### NPM/Node issues
```bash
docker-compose exec app npm install
docker-compose exec app npm run build
```

## Data Persistence

Data is persisted in Docker volumes:
- `dbdata`: MySQL database files
- `redisdata`: Redis data files

To backup the database:
```bash
docker-compose exec db mysqldump -u mhrpci_user -pmhrpci-admin@2025 mhrpci > backup.sql
```

To restore a database backup:
```bash
docker-compose exec -T db mysql -u mhrpci_user -pmhrpci-admin@2025 mhrpci < backup.sql
```

## Production Deployment

For production, modify the following:

1. Update `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://adminpci.mhrpci.site
   ```

2. Use production-ready MySQL password (change in both `.env` and `docker-compose.yml`)
3. Configure proper domain in `APP_URL`
4. Enable HTTPS with reverse proxy (nginx/traefik)
5. Set secure session and cache drivers
6. Configure mail settings for production

## Docker Compose Services Details

### app (PHP-FPM)
- Image: Custom built from Dockerfile
- Base: php:8.3-fpm-bookworm
- Extensions: pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip, intl, redis
- Composer: v2
- Working directory: /var/www/html

### webserver (Nginx)
- Image: nginx:alpine
- Port: 4003 (external) → 80 (internal)
- Configuration: ./docker/nginx/default.conf
- Serves Laravel public directory

### db (MySQL)
- Image: mysql:8.0
- Port: 3037 (external) → 3306 (internal)
- Database: mhrpci
- User: mhrpci_user
- Root password: root

### redis (Redis)
- Image: redis:7-alpine
- Port: 6382 (external) → 6379 (internal)
- Persistence: redisdata volume

### phpmyadmin
- Image: phpmyadmin/phpmyadmin
- Port: 4004 (external) → 80 (internal)
- Host: db
- Upload limit: 100M

### scheduler (Supervisor)
- Image: Same as app (custom Dockerfile)
- Command: supervisord
- Runs: php artisan schedule:work
- Logs: stdout/stderr

## Network

All services run on the `mhrpci-network` bridge network, allowing them to communicate with each other using service names as hostnames.

## Volumes

- `dbdata`: Persistent MySQL database storage
- `redisdata`: Persistent Redis data storage
- `.:/var/www/html`: Application code bind mount (for development)
- `./docker/nginx:/etc/nginx/conf.d`: Nginx configuration bind mount

## Health Checks

- **db**: Checks MySQL ping every 5s
- **redis**: Checks redis-cli ping every 5s  
- **scheduler**: Checks supervisorctl status every 30s

## First-Time Setup

After starting containers for the first time:

```bash
# Enter the app container
docker-compose exec app bash

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets (if needed)
npm install
npm run build
```

## Monitoring

Check service health:
```bash
docker-compose ps
```

Monitor resource usage:
```bash
docker stats
```

View container details:
```bash
docker-compose logs --tail=100 -f
```
