# 419 Error Fix - Production Deployment

## Root Causes Identified

### 1. **Missing Laravel Sanctum Package**
The mhrpci-backend was missing the `laravel/sanctum` package, which is required for:
- Stateful SPA authentication
- CSRF token management
- Session-based authentication for SPAs

### 2. **Missing Proxy Trust Configuration**
The application was not configured to trust proxy headers (X-Forwarded-*), causing:
- Incorrect HTTPS detection behind Nginx/reverse proxy
- Session cookie domain/secure attribute mismatches
- CSRF token validation failures

### 3. **Incomplete CORS Configuration**
The HandleCors middleware didn't include production domain in allowed origins.

---

## Fixes Applied

### ✅ 1. Created `config/sanctum.php`
Added Sanctum configuration file with:
- Stateful domain configuration from `SANCTUM_STATEFUL_DOMAINS` env variable
- Proper middleware stack for CSRF validation
- Cookie encryption settings

### ✅ 2. Updated `bootstrap/app.php`
Added proxy trust configuration:
```php
$middleware->trustProxies(at: '*', headers:
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB
);
```

### ✅ 3. Updated `app/Http/Middleware/HandleCors.php`
Added production domain to allowed origins:
- `https://adminpci.mhrpci.site`
- Dynamic `APP_URL` from environment

---

## Required Manual Steps

### 🔴 CRITICAL: Install Laravel Sanctum

**Step 1:** Add Sanctum to composer.json
```bash
cd c:\laragon\www\company_web\mhrpci-backend
composer require laravel/sanctum
```

**Step 2:** Publish Sanctum migrations (if needed)
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

**Step 3:** Run migrations
```bash
php artisan migrate
```

**Step 4:** Clear config cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## Environment Configuration Review

Your current `.env.prod` configuration is correct:

```env
# Sanctum / CSRF
SESSION_DOMAIN=.mhrpci.site
SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
```

These settings match the working mhrhci-backend configuration pattern.

---

## Deployment Checklist

Before deploying to production:

- [ ] Install Laravel Sanctum via composer
- [ ] Run database migrations
- [ ] Clear all Laravel caches
- [ ] Verify Redis is running and accessible
- [ ] Verify Nginx is configured to pass proxy headers
- [ ] Test CSRF token generation on login page
- [ ] Test authenticated requests don't get 419 errors

---

## Nginx Configuration Verification

Ensure your Nginx configuration includes these headers:

```nginx
location / {
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;
}
```

---

## Testing the Fix

After deployment, test:

1. **Load the login page** - Check for CSRF token in HTML
2. **Submit login form** - Should not get 419 error
3. **Authenticated actions** - All POST/PUT/DELETE requests should work
4. **Session persistence** - User should stay logged in across requests

---

## Comparison with Working Backend (mhrhci-backend)

Both backends now have matching configurations for:
- ✅ Sanctum stateful domain handling
- ✅ Session cookie configuration
- ✅ Proxy trust headers
- ✅ CSRF protection middleware

The main difference remaining is you need to install the Sanctum package in mhrpci-backend.

---

## Additional Notes

- The 419 error typically occurs when Laravel cannot validate the CSRF token
- In production behind a reverse proxy, the app must trust proxy headers to correctly detect HTTPS
- Without proper HTTPS detection, secure cookies won't be sent, breaking sessions
- Sanctum is essential for SPA authentication in Laravel 11+
