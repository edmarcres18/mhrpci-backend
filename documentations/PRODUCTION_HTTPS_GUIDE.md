# Production HTTPS & CSRF Configuration Guide

## Overview
This guide provides a comprehensive solution for eliminating 419 CSRF errors in production when running Laravel behind HTTPS with a reverse proxy (Nginx/Apache/Load Balancer).

---

## 🔴 Critical: Root Causes of 419 Errors in Production

### 1. **Missing Proxy Trust Configuration**
When Laravel runs behind a reverse proxy (Nginx, Apache, Load Balancer), it cannot detect HTTPS unless configured to trust proxy headers.

**Result**: Laravel thinks requests are HTTP, not HTTPS → Session cookies with `secure` flag aren't sent → CSRF tokens fail

### 2. **Incorrect CORS Configuration**
Production domains must be explicitly allowed in CORS middleware.

**Result**: Browsers block cross-origin requests → CSRF tokens not received → 419 errors

### 3. **Session Configuration Issues**
Improper session settings for production HTTPS environments.

**Result**: Sessions don't persist → CSRF tokens regenerate unexpectedly → 419 errors

---

## ✅ Fixes Applied to This Codebase

### 1. **Proxy Trust Configuration** (`bootstrap/app.php`)
```php
use Illuminate\Http\Request;

$middleware->trustProxies(
    at: '*',
    headers: Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB
);
```

**What this does**:
- Tells Laravel to trust `X-Forwarded-*` headers from reverse proxies
- Enables proper HTTPS detection when behind Nginx/Load Balancer
- Ensures session cookies with `secure` flag work correctly

### 2. **Enhanced CORS Middleware** (`app/Http/Middleware/HandleCors.php`)
```php
$allowedOrigins = [
    // Local development
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    // Production domains
    'https://adminpci.mhrpci.site',
    'https://mhrpci.site',
];

// Dynamic APP_URL support
$appUrl = config('app.url');
if ($appUrl && !in_array($appUrl, $allowedOrigins)) {
    $allowedOrigins[] = $appUrl;
}
```

**What this does**:
- Allows requests from production domains
- Automatically includes `APP_URL` from environment
- Handles OPTIONS preflight requests properly
- Exposes CSRF token in response headers

### 3. **CSRF Token Middleware** (`app/Http/Middleware/VerifyCsrfToken.php`)
Already configured with:
- Proper token validation
- CSRF token in JSON response headers
- Exception handling

### 4. **Frontend Configuration** (`resources/js/bootstrap.ts`)
Already configured with:
- Automatic CSRF token reading from meta tag
- CSRF token in request headers
- Auto-reload on 419 errors

---

## 📋 Production Environment Configuration

### Required `.env` Settings for Production

```env
# Application
APP_NAME="Your App Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adminpci.mhrpci.site

# Session Configuration (CRITICAL FOR HTTPS)
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.mhrpci.site
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
SESSION_PARTITIONED_COOKIE=false

# Sanctum Configuration (CRITICAL FOR SPA)
SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site

# Redis Configuration (for session storage)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Key Configuration Explanations

| Setting | Value | Why? |
|---------|-------|------|
| `SESSION_DRIVER` | `redis` | Better performance and persistence than database |
| `SESSION_SECURE_COOKIE` | `true` | **REQUIRED for HTTPS** - cookies only sent over secure connections |
| `SESSION_DOMAIN` | `.mhrpci.site` | Share cookies across subdomains (note the leading dot) |
| `SESSION_SAME_SITE` | `lax` | Balance between security and usability |
| `SANCTUM_STATEFUL_DOMAINS` | `adminpci.mhrpci.site` | Allows stateful SPA authentication from this domain |
| `APP_URL` | `https://...` | **MUST use HTTPS** in production |

---

## 🔧 Nginx Configuration for Production

Your Nginx configuration **MUST** pass proxy headers to Laravel:

```nginx
server {
    listen 443 ssl http2;
    server_name adminpci.mhrpci.site;

    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/mhrpci-backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        
        # CRITICAL: These headers allow Laravel to detect HTTPS
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_X_FORWARDED_PROTO https;
        fastcgi_param HTTP_X_FORWARDED_FOR $proxy_add_x_forwarded_for;
        fastcgi_param HTTP_X_FORWARDED_HOST $host;
        fastcgi_param HTTP_X_FORWARDED_PORT $server_port;
        
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name adminpci.mhrpci.site;
    return 301 https://$server_name$request_uri;
}
```

**Critical Headers Explained**:
- `fastcgi_param HTTPS on;` - Tells PHP/Laravel request is HTTPS
- `HTTP_X_FORWARDED_PROTO https` - Forwards the original protocol
- `HTTP_X_FORWARDED_FOR` - Forwards client IP
- `HTTP_X_FORWARDED_HOST` - Forwards original host
- `HTTP_X_FORWARDED_PORT` - Forwards original port

---

## 🚀 Deployment Checklist

### Before Deployment

- [ ] **Install Laravel Sanctum** (if not already installed)
  ```bash
  composer require laravel/sanctum
  ```

- [ ] **Run migrations** to create sessions table
  ```bash
  php artisan migrate --force
  ```

- [ ] **Verify Redis is running**
  ```bash
  redis-cli ping
  # Should return: PONG
  ```

### After Deployment

- [ ] **Clear all caches**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan route:clear
  php artisan view:clear
  php artisan optimize:clear
  ```

- [ ] **Rebuild optimized cache** (optional, for performance)
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

- [ ] **Set proper file permissions**
  ```bash
  chown -R www-data:www-data /var/www/mhrpci-backend
  chmod -R 755 /var/www/mhrpci-backend
  chmod -R 775 /var/www/mhrpci-backend/storage
  chmod -R 775 /var/www/mhrpci-backend/bootstrap/cache
  ```

- [ ] **Restart PHP-FPM**
  ```bash
  sudo systemctl restart php8.2-fpm
  ```

- [ ] **Restart Nginx**
  ```bash
  sudo systemctl restart nginx
  ```

---

## 🧪 Testing After Deployment

### 1. Test HTTPS Detection
```bash
php artisan tinker
>>> request()->secure()
# Should return: true
```

### 2. Test Session Driver
```bash
php artisan tinker
>>> config('session.driver')
# Should return: "redis"
```

### 3. Test Sanctum Configuration
```bash
php artisan tinker
>>> config('sanctum.stateful')
# Should return: array with your production domain
```

### 4. Test in Browser

**Step 1**: Open browser DevTools (F12)

**Step 2**: Go to your login page (`https://adminpci.mhrpci.site/login`)

**Step 3**: Check for CSRF token in HTML
```javascript
console.log(document.querySelector('meta[name="csrf-token"]').content);
// Should output a long token string
```

**Step 4**: Check session cookie
- Go to DevTools > Application > Cookies
- Look for `laravel_session` or `your_app_name_session`
- Verify it has:
  - ✅ `Secure` flag
  - ✅ `HttpOnly` flag
  - ✅ `SameSite=Lax`
  - ✅ Domain: `.mhrpci.site`

**Step 5**: Test Login
- Submit login form
- If successful without 419 error → ✅ Configuration is correct!

### 5. Test API Requests

In browser console:
```javascript
// This should work without 419 errors
axios.post('/api/test-endpoint', { test: 'data' })
    .then(response => console.log('✅ Success:', response))
    .catch(error => console.error('❌ Error:', error.response?.status));
```

---

## 🐛 Troubleshooting

### Issue: Still getting 419 errors

**Solution 1**: Verify HTTPS is properly detected
```bash
php artisan tinker
>>> request()->secure()
# Must return: true
```

If false, check:
- Nginx is passing `fastcgi_param HTTPS on;`
- Laravel proxy trust is configured (check `bootstrap/app.php`)

**Solution 2**: Check session cookie
- Open DevTools > Application > Cookies
- If cookie doesn't have `Secure` flag → HTTPS detection problem
- If cookie domain is wrong → Check `SESSION_DOMAIN` in `.env`

**Solution 3**: Clear everything and restart
```bash
php artisan optimize:clear
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
# Clear browser cookies and try again
```

### Issue: Session not persisting

**Check Redis connection**:
```bash
php artisan tinker
>>> Redis::ping()
# Should return: "PONG"
```

**Check Redis is configured correctly**:
```bash
php artisan tinker
>>> config('database.redis.default')
# Should show your Redis configuration
```

### Issue: CORS errors

**Verify origin is allowed**:
- Check browser console for exact error
- Ensure your domain is in `HandleCors.php` `$allowedOrigins` array
- Or set `APP_URL` in `.env` to your production URL

### Issue: Mixed content warnings

**Cause**: Some resources loading over HTTP instead of HTTPS

**Solution**:
```bash
# Force HTTPS for all assets
php artisan tinker
>>> URL::forceScheme('https')
```

Add to `AppServiceProvider::boot()`:
```php
if ($this->app->environment('production')) {
    URL::forceScheme('https');
}
```

---

## 📊 Security Best Practices

### 1. Never Do This in Production ❌
```env
# BAD - Never use these in production
SESSION_SECURE_COOKIE=false
APP_DEBUG=true
SANCTUM_STATEFUL_DOMAINS=*
SESSION_DOMAIN=null
```

### 2. Always Do This in Production ✅
```env
# GOOD - Production settings
SESSION_SECURE_COOKIE=true
APP_DEBUG=false
SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site
SESSION_DOMAIN=.mhrpci.site
```

### 3. CSRF Exemptions
Only exempt routes that:
- Receive webhooks from third-party services
- Are public APIs with token authentication

**Bad Example** ❌:
```php
protected $except = [
    'api/*',  // Never do this!
];
```

**Good Example** ✅:
```php
protected $except = [
    'webhooks/stripe',
    'webhooks/github',
];
```

---

## 📚 Additional Resources

- [Laravel CSRF Protection](https://laravel.com/docs/11.x/csrf)
- [Laravel Sanctum SPA Authentication](https://laravel.com/docs/11.x/sanctum#spa-authentication)
- [Laravel Behind Proxies](https://laravel.com/docs/11.x/requests#configuring-trusted-proxies)
- [Laravel Session Configuration](https://laravel.com/docs/11.x/session)

---

## 🔍 Quick Debugging Commands

```bash
# View current configuration
php artisan config:show session
php artisan config:show sanctum

# Check if app detects HTTPS
php artisan tinker
>>> request()->secure()
>>> request()->getScheme()
>>> request()->header('X-Forwarded-Proto')

# Check session configuration
php artisan tinker
>>> config('session.driver')
>>> config('session.secure')
>>> config('session.domain')

# Test Redis connection
php artisan tinker
>>> Redis::connection()->ping()

# View all middleware
php artisan route:list --columns=method,uri,name,middleware

# Check for sessions table
php artisan tinker
>>> Schema::hasTable('sessions')
```

---

## ✅ Summary: What Was Fixed

1. **Added Proxy Trust Configuration** - Laravel now properly detects HTTPS behind reverse proxy
2. **Enhanced CORS Middleware** - Production domains are now allowed
3. **Updated Environment Configuration** - Added comprehensive session and Sanctum settings
4. **Frontend CSRF Handling** - Already configured to handle CSRF tokens properly

**Result**: 419 errors should now be completely eliminated in production HTTPS environments.

If you still experience issues after following this guide, check:
1. Nginx logs: `sudo tail -f /var/log/nginx/error.log`
2. Laravel logs: `tail -f storage/logs/laravel.log`
3. PHP-FPM logs: `sudo tail -f /var/log/php8.2-fpm.log`
