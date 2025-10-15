# Quick Deployment Guide - Apply Changes Now

## Files Changed (Already Applied)

✅ **`.env.prod`** - Removed conflicting session configurations
✅ **`config/sanctum.php`** - Fixed stateful domain configuration  
✅ **`bootstrap/app.php`** - Cleaned up middleware stack, removed HandleCors interference

## Deploy to Production (Run These Commands)

### Option 1: Docker Deployment (Recommended)

```bash
# Navigate to project directory
cd c:\laragon\www\company_web\mhrpci-backend

# Stop current containers
docker-compose down

# Rebuild with new configuration
docker-compose up -d --build

# Clear all Laravel caches in container
docker exec mhrpci_app php artisan optimize:clear
docker exec mhrpci_app php artisan config:cache
docker exec mhrpci_app php artisan route:cache
docker exec mhrpci_app php artisan view:cache

# Verify services are running
docker-compose ps

# Check logs for errors
docker logs mhrpci_app --tail=50
```

### Option 2: Manual Deployment (if not using Docker)

```bash
# Upload changed files to production server:
# - .env.prod (rename to .env on server)
# - config/sanctum.php
# - bootstrap/app.php

# On production server, run:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM/web server
sudo systemctl restart php8.2-fpm  # Adjust version as needed
sudo systemctl restart nginx
```

## Test After Deployment

### 1. Verify HTTPS Detection
```bash
# In tinker
docker exec mhrpci_app php artisan tinker
>>> request()->secure()  // Should return true in production
>>> exit
```

### 2. Check Session Configuration
```bash
docker exec mhrpci_app php artisan tinker
>>> config('session.secure')    // Should return true
>>> config('session.domain')    // Should return '.mhrpci.site'
>>> config('session.driver')    // Should return 'redis'
>>> exit
```

### 3. Test Authentication Flow
- Login to the application
- Perform authenticated actions
- Check if 419 errors are gone

### 4. Monitor Logs
```bash
# Watch for any 419 errors
docker exec mhrpci_app tail -f storage/logs/laravel.log
```

## Expected Results

✅ No more 419 CSRF token errors
✅ HTTPS properly detected
✅ Sessions work correctly across requests
✅ Application is scalable for production traffic

## Rollback (If Issues Occur)

If you need to rollback:

```bash
# Restore from backup (make backup first!)
git checkout <previous-commit>

# Or manually restore old files
# Then rebuild containers
docker-compose down
docker-compose up -d --build
```

## Key Configuration Differences Fixed

| Setting | Before (Broken) | After (Fixed) |
|---------|----------------|---------------|
| Session Config | Extra `SESSION_SAME_SITE`, `SESSION_PARTITIONED_COOKIE` | Clean, matching working backend |
| Sanctum Stateful | Complex with conditionals | Simple, direct concatenation |
| Middleware | Custom HandleCors in API | Removed, using Laravel defaults |
| Proxy Trust | Present but after other config | Properly positioned and configured |

## Production Environment Variables (Verify These)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adminpci.mhrpci.site
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.mhrpci.site
SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Contact & Support

If issues persist:
1. Check `PRODUCTION_SETUP.md` for detailed troubleshooting
2. Compare configuration with working `mhrhci-backend`
3. Verify Redis is running: `docker exec mhrpci_redis redis-cli ping`

---

**Status:** Ready to deploy
**Risk Level:** Low (matched to working configuration)
**Estimated Downtime:** ~2-3 minutes (container rebuild)
