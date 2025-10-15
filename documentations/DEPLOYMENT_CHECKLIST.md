# Production Deployment Checklist - HTTPS & CSRF Protection

> **Quick reference for deploying mhrpci-backend to production**
> 
> Use this checklist to ensure zero 419 errors in production

---

## 📋 Pre-Deployment Checklist

### 1. Code Changes (Already Applied ✅)

- [x] Proxy trust configuration in `bootstrap/app.php`
- [x] CORS middleware updated with production domains
- [x] CSRF token middleware configured
- [x] Frontend bootstrap.ts configured
- [x] Session table migration exists

### 2. Environment Configuration (.env)

Copy your `.env.production` or `.env.prod` file and verify these settings:

```bash
# Copy production environment file
cp .env.prod .env

# OR create new .env with these settings:
```

**Required Settings**:
```env
# ===== APPLICATION =====
APP_NAME="MHRPCI Admin"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adminpci.mhrpci.site

# ===== SESSION (CRITICAL) =====
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.mhrpci.site
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
SESSION_PARTITIONED_COOKIE=false

# ===== SANCTUM (CRITICAL) =====
SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site

# ===== REDIS =====
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# ===== DATABASE =====
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mhrpci_backend
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Checklist**:
- [ ] `APP_URL` uses HTTPS
- [ ] `SESSION_DRIVER=redis` (or `database`)
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_DOMAIN=.mhrpci.site` (with leading dot)
- [ ] `SANCTUM_STATEFUL_DOMAINS` matches your frontend domain
- [ ] Database credentials correct
- [ ] Redis accessible

---

## 🔧 Server Configuration

### 3. Nginx Configuration

**File**: `/etc/nginx/sites-available/mhrpci-backend`

**Minimum Required Configuration**:
```nginx
server {
    listen 443 ssl http2;
    server_name adminpci.mhrpci.site;

    # SSL
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    root /var/www/mhrpci-backend/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        
        # CRITICAL: These lines are required for HTTPS detection
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

**Checklist**:
- [ ] SSL certificate installed and valid
- [ ] `fastcgi_param HTTPS on;` present
- [ ] All `HTTP_X_FORWARDED_*` parameters present
- [ ] HTTP to HTTPS redirect configured
- [ ] Nginx configuration tested: `sudo nginx -t`

---

## 🚀 Deployment Steps

### 4. Upload Code

```bash
# Via Git (recommended)
cd /var/www/mhrpci-backend
git pull origin main

# OR via FTP/SFTP
# Upload all files to /var/www/mhrpci-backend
```

**Checklist**:
- [ ] All code uploaded
- [ ] `.env` file in place
- [ ] `bootstrap/app.php` has proxy trust config
- [ ] `app/Http/Middleware/HandleCors.php` includes production domains

### 5. Install Dependencies

```bash
cd /var/www/mhrpci-backend

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci

# Build frontend assets
npm run build
```

**Checklist**:
- [ ] Composer dependencies installed
- [ ] Laravel Sanctum installed (check `composer.json`)
- [ ] Frontend assets built

### 6. Run Migrations

```bash
# Run database migrations
php artisan migrate --force

# Check if sessions table exists
php artisan tinker
>>> Schema::hasTable('sessions')
# Should return: true
```

**Checklist**:
- [ ] Migrations executed successfully
- [ ] Sessions table exists (if using database driver)

### 7. Set File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/mhrpci-backend

# Set directory permissions
sudo find /var/www/mhrpci-backend -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/mhrpci-backend -type f -exec chmod 644 {} \;

# Storage and cache need write access
sudo chmod -R 775 /var/www/mhrpci-backend/storage
sudo chmod -R 775 /var/www/mhrpci-backend/bootstrap/cache
```

**Checklist**:
- [ ] Ownership set to `www-data`
- [ ] Storage directory writable
- [ ] Bootstrap cache writable

### 8. Clear Caches

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Rebuild optimized cache (optional, for performance)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Checklist**:
- [ ] All caches cleared
- [ ] No errors during cache commands

### 9. Restart Services

```bash
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart Nginx
sudo systemctl restart nginx

# Check services are running
sudo systemctl status php8.2-fpm
sudo systemctl status nginx
```

**Checklist**:
- [ ] PHP-FPM restarted successfully
- [ ] Nginx restarted successfully
- [ ] Both services showing as active

---

## ✅ Post-Deployment Testing

### 10. Verify Server Configuration

```bash
# SSH into your server
ssh user@your-server

# Navigate to project
cd /var/www/mhrpci-backend

# Test HTTPS detection
php artisan tinker
>>> request()->secure()
# Expected: true ✅

>>> request()->getScheme()
# Expected: "https" ✅

>>> request()->header('X-Forwarded-Proto')
# Expected: "https" ✅

# Test session driver
>>> config('session.driver')
# Expected: "redis" or "database" ✅

>>> config('session.secure')
# Expected: true ✅

# Test Redis (if using Redis)
>>> Redis::connection()->ping()
# Expected: "PONG" ✅

# Test Sanctum config
>>> config('sanctum.stateful')
# Expected: array containing "adminpci.mhrpci.site" ✅
```

**Checklist**:
- [ ] `request()->secure()` returns `true`
- [ ] Session driver configured correctly
- [ ] Redis/Database accessible
- [ ] Sanctum stateful domains correct

### 11. Browser Testing

**Step 1: Clear Browser Cache**
- Open browser in Incognito mode
- Or clear all cookies and cache

**Step 2: Open Login Page**
- Navigate to: `https://adminpci.mhrpci.site/login`

**Step 3: Check CSRF Token (F12 Console)**
```javascript
// Check for CSRF token
const token = document.querySelector('meta[name="csrf-token"]');
console.log('Token exists:', !!token);
console.log('Token value:', token ? token.content : 'NOT FOUND');
```

**Expected Output**:
```
Token exists: true
Token value: [40-character string]
```

**Step 4: Check Session Cookie (F12 → Application → Cookies)**

Look for cookie named `laravel_session` or `mhrpci_admin_session`:

| Attribute | Expected Value | Status |
|-----------|---------------|---------|
| Domain | `.mhrpci.site` | [ ] |
| Path | `/` | [ ] |
| Secure | ✅ | [ ] |
| HttpOnly | ✅ | [ ] |
| SameSite | `Lax` | [ ] |

**Step 5: Test Login**
- Enter credentials
- Submit form
- **Expected**: Login successful WITHOUT 419 error

**Step 6: Test POST Request (F12 Console)**
```javascript
// Test a POST request
axios.post('/api/test', { test: 'data' })
    .then(response => {
        console.log('✅ SUCCESS:', response.status);
    })
    .catch(error => {
        console.log('❌ ERROR:', error.response?.status);
        if (error.response?.status === 419) {
            console.log('🔴 419 CSRF ERROR STILL PRESENT!');
        }
    });
```

**Checklist**:
- [ ] CSRF token present in HTML
- [ ] Session cookie present with correct attributes
- [ ] Login works without 419 error
- [ ] POST requests work without 419 error
- [ ] User stays logged in across page refreshes

---

## 🐛 If Issues Occur

### 419 Error Still Happening?

**Quick Diagnostic**:
```bash
# 1. Check Laravel is detecting HTTPS
php artisan tinker
>>> request()->secure()
# If false → Nginx not configured correctly

# 2. Check session cookie
# Open DevTools → Application → Cookies
# If no session cookie → Session driver issue

# 3. Check Laravel logs
tail -50 storage/logs/laravel.log

# 4. Check Nginx logs
sudo tail -50 /var/log/nginx/error.log
```

**Common Fixes**:

1. **Nginx not passing headers**:
   ```bash
   # Verify Nginx config has HTTPS parameters
   sudo nano /etc/nginx/sites-available/mhrpci-backend
   # Add fastcgi_param HTTPS on;
   sudo nginx -t
   sudo systemctl restart nginx
   ```

2. **Cache not cleared**:
   ```bash
   php artisan optimize:clear
   sudo systemctl restart php8.2-fpm
   ```

3. **Wrong environment**:
   ```bash
   # Verify .env is correct
   cat .env | grep -E "(APP_ENV|APP_URL|SESSION_SECURE)"
   ```

4. **CORS issues**:
   ```bash
   # Check HandleCors.php includes your domain
   grep "adminpci.mhrpci.site" app/Http/Middleware/HandleCors.php
   ```

### Detailed Troubleshooting

See these documentation files:
- **`documentations/419_ERROR_TROUBLESHOOTING.md`** - Complete troubleshooting guide
- **`documentations/PRODUCTION_HTTPS_GUIDE.md`** - Detailed HTTPS setup
- **`documentations/CSRF_FIX_GUIDE.md`** - CSRF-specific issues

---

## 📊 Health Check Commands

Run these periodically to ensure everything is working:

```bash
# System status
sudo systemctl status nginx php8.2-fpm redis-server

# Check disk space
df -h

# Check Laravel logs
tail -20 storage/logs/laravel.log

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test Redis connection
>>> Redis::ping();

# Check queue status (if using queues)
php artisan queue:failed
```

---

## 🔐 Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] SSL certificate valid and not expired
- [ ] File permissions correct (not 777)
- [ ] `.env` file not accessible via web
- [ ] Database credentials secure
- [ ] Redis password set (if accessible remotely)

---

## 📝 Rollback Plan

If deployment fails:

```bash
# 1. Restore previous code version
git reset --hard HEAD~1

# 2. Clear caches
php artisan optimize:clear

# 3. Restore database backup (if migrations ran)
mysql -u username -p database_name < backup.sql

# 4. Restart services
sudo systemctl restart php8.2-fpm nginx
```

---

## ✨ Success Criteria

Deployment is successful when:

- ✅ Website loads at `https://adminpci.mhrpci.site`
- ✅ Login page shows without errors
- ✅ Login works without 419 errors
- ✅ POST requests work without 419 errors
- ✅ Session persists across page refreshes
- ✅ No errors in Laravel logs
- ✅ No errors in Nginx error logs
- ✅ HTTPS certificate valid and green padlock shows

---

## 📞 Support

If you need help:

1. **Check logs first**:
   - Laravel: `storage/logs/laravel.log`
   - Nginx: `/var/log/nginx/error.log`
   - PHP-FPM: `/var/log/php8.2-fpm.log`

2. **Review documentation**:
   - This checklist
   - `documentations/PRODUCTION_HTTPS_GUIDE.md`
   - `documentations/419_ERROR_TROUBLESHOOTING.md`

3. **Common issues**: See troubleshooting section above

---

## 🎉 Deployment Complete!

Once all checklist items are ✅, your application is production-ready with:
- Zero 419 CSRF errors
- Proper HTTPS handling
- Secure session management
- Production-grade security

**Remember**: Always test in staging environment first before production deployment!

---

**Last Updated**: 2025-10-15
**Version**: 1.0
**Maintainer**: MHRPCI Development Team
