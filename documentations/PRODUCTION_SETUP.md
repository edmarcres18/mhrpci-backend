# Production Setup Guide - MHRPCI Backend

## Overview
This document outlines the production configuration for `mhrpci-backend` to ensure scalability, proper HTTPS handling, and prevent 419 CSRF errors.

## Changes Applied (Based on Working mhrhci-backend)

### 1. Environment Configuration (.env.prod)

**Removed:**
- `SESSION_SAME_SITE=lax` - Now uses default from config/session.php
- `SESSION_PARTITIONED_COOKIE=false` - Now uses default from config/session.php

**Critical Settings for Production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adminpci.mhrpci.site

# HTTPS Session Handling
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.mhrpci.site
SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site

# Redis for Session/Cache (Scalable)
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

### 2. Sanctum Configuration (config/sanctum.php)

**Fixed:** Stateful domain configuration to properly handle CSRF tokens across domains.

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort(),
))),
```

### 3. Middleware Configuration (bootstrap/app.php)

**Removed:**
- Custom `HandleCors` middleware from API routes (can interfere with Laravel's built-in CORS handling)
- Custom CSRF validation exceptions
- Unnecessary middleware imports

**Added/Verified:**
- Proxy trust configuration for HTTPS detection behind reverse proxy
- Clean middleware stack matching working configuration

```php
// Trust proxy headers for HTTPS detection
$middleware->trustProxies(at: '*', headers:
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB
);
```

## How This Fixes 419 CSRF Errors

### Root Causes Addressed:

1. **HTTPS Detection:** 
   - The `trustProxies` configuration ensures Laravel correctly detects HTTPS when behind a reverse proxy (Nginx/Cloudflare)
   - Prevents mixed-content issues that can break CSRF token validation

2. **Session Cookie Configuration:**
   - `SESSION_SECURE_COOKIE=true` ensures cookies are only sent over HTTPS
   - `SESSION_DOMAIN=.mhrpci.site` allows cookies to work across subdomains
   - Removed conflicting session configurations that could interfere

3. **Sanctum Stateful Domains:**
   - Properly configured to recognize `adminpci.mhrpci.site` as a stateful domain
   - Ensures CSRF tokens are properly validated for SPA requests

4. **Middleware Stack:**
   - Removed custom CORS middleware that could interfere with Laravel's built-in handling
   - Simplified middleware configuration reduces potential conflicts

## Production Deployment Checklist

### Before Deployment:
- [ ] Verify `.env.prod` is properly configured
- [ ] Ensure Redis is running and accessible
- [ ] Database migrations are up to date
- [ ] Laravel cache is cleared (`php artisan cache:clear`)
- [ ] Config cache is cleared (`php artisan config:clear`)
- [ ] Route cache is cleared (`php artisan route:clear`)

### After Deployment:
- [ ] Run `php artisan config:cache` (production optimization)
- [ ] Run `php artisan route:cache` (production optimization)
- [ ] Run `php artisan view:cache` (production optimization)
- [ ] Verify HTTPS is working correctly
- [ ] Test authentication flow
- [ ] Monitor for 419 errors in logs

### Using Docker:
```bash
# Rebuild containers with new configuration
docker-compose down
docker-compose up -d --build

# Clear all caches in container
docker exec mhrpci_app php artisan optimize:clear
docker exec mhrpci_app php artisan config:cache
docker exec mhrpci_app php artisan route:cache
docker exec mhrpci_app php artisan view:cache
```

## Scalability Features

### Redis Session Management
- Sessions stored in Redis (not files) for horizontal scaling
- Multiple app instances can share session data
- Fast session lookup and invalidation

### Proxy Trust Configuration
- Supports load balancers and reverse proxies
- Works with Cloudflare, AWS ELB, Nginx, Traefik
- Properly detects client IP and protocol

### Cache Strategy
- Redis for application cache
- Queue system for background jobs
- Optimized for high traffic

## Monitoring

### Check for CSRF Errors:
```bash
# In Docker container
docker exec mhrpci_app tail -f storage/logs/laravel.log | grep 419

# Check session configuration
docker exec mhrpci_app php artisan tinker
>>> config('session.secure')  // Should return true
>>> config('session.domain')  // Should return '.mhrpci.site'
```

### Verify HTTPS Detection:
```php
// In a controller or tinker
if (request()->secure()) {
    echo "HTTPS is properly detected";
} else {
    echo "HTTPS detection issue - check proxy trust configuration";
}
```

## Troubleshooting

### Still Getting 419 Errors?

1. **Clear all caches:**
   ```bash
   docker exec mhrpci_app php artisan optimize:clear
   docker exec mhrpci_app php artisan config:cache
   ```

2. **Verify session driver:**
   ```bash
   docker exec mhrpci_app php artisan tinker
   >>> config('session.driver')  // Should return 'redis'
   ```

3. **Check Redis connection:**
   ```bash
   docker exec mhrpci_redis redis-cli ping  // Should return 'PONG'
   ```

4. **Verify frontend is sending cookies:**
   - Check if `withCredentials: true` is set in frontend HTTP client
   - Ensure frontend domain is in `SANCTUM_STATEFUL_DOMAINS`

5. **Check session domain:**
   - Must start with a dot for subdomain support: `.mhrpci.site`
   - Frontend and backend must share the same root domain

## Security Notes

- Never set `APP_DEBUG=true` in production
- Always use `SESSION_SECURE_COOKIE=true` for HTTPS
- Keep `APP_KEY` secure and never commit to version control
- Regularly update dependencies for security patches
- Monitor logs for suspicious activity

## Differences from mhrhci-backend

Both backends now have identical production configuration patterns:
- Same session handling strategy
- Same proxy trust configuration
- Same middleware stack
- Same Sanctum configuration pattern

The only differences are application-specific:
- Database names
- Domain names
- Container names/ports
- Application keys

## Support

If issues persist after applying these configurations:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Check Nginx logs: `docker logs mhrpci_webserver`
3. Verify environment variables are loaded correctly
4. Compare with working `mhrhci-backend` configuration

---

**Last Updated:** October 15, 2025
**Configuration Aligned With:** mhrhci-backend (working production setup)
