# CSRF 419 Error - Fix Documentation

This document outlines the fixes applied to resolve the 419 CSRF token mismatch errors in your Laravel application.

## Issues Fixed

### 1. **Missing Sessions Table Migration**
- **Problem**: Using `SESSION_DRIVER=database` without a sessions table
- **Solution**: Created migration `2025_10_15_000001_create_sessions_table.php`
- **Action Required**: Run `php artisan migrate` to create the sessions table

### 2. **Missing SANCTUM_STATEFUL_DOMAINS Configuration**
- **Problem**: Local `.env` was missing Sanctum stateful domains configuration
- **Solution**: Added `SANCTUM_STATEFUL_DOMAINS` to all environment files
- **Configuration**:
  - **Local**: `localhost:8000,127.0.0.1:8000,192.168.1.210:8000`
  - **Production**: `adminpci.mhrpci.site`

### 3. **Custom CSRF Token Middleware**
- **Problem**: No custom CSRF verification middleware for handling exceptions
- **Solution**: Created `app/Http/Middleware/VerifyCsrfToken.php`
- **Features**:
  - Proper token validation
  - CSRF token in JSON response headers
  - Exception handling for specific routes

### 4. **CORS Headers Missing CSRF Tokens**
- **Problem**: CORS middleware didn't allow CSRF token headers
- **Solution**: Updated `app/Http/Middleware/HandleCors.php`
- **Added Headers**:
  - `X-CSRF-TOKEN` and `X-XSRF-TOKEN` to allowed headers
  - `X-CSRF-TOKEN` to exposed headers
  - Added `PATCH` to allowed methods

### 5. **Incomplete Session Configuration**
- **Problem**: Missing session security settings in environment files
- **Solution**: Added comprehensive session configuration
- **Added Settings**:
  - `SESSION_SECURE_COOKIE` (false for local, true for production)
  - `SESSION_SAME_SITE=lax`
  - `SESSION_PARTITIONED_COOKIE=false` (production)

## Required Actions

### 1. Run Database Migration
```bash
php artisan migrate
```

### 2. Clear Application Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Restart Development Server
If you're running `php artisan serve`, restart it to pick up the new configuration.

### 4. Clear Browser Data
- Clear cookies and local storage for your application domain
- Or use incognito/private browsing mode to test

## Frontend Configuration

The frontend is already properly configured in `resources/js/bootstrap.ts`:
- Automatically reads CSRF token from meta tag
- Includes token in Axios headers
- Auto-reloads on 419 errors

## Production Deployment Checklist

### Environment Variables (.env.prod)
- ✅ `SESSION_DRIVER=redis` (for better performance)
- ✅ `SESSION_SECURE_COOKIE=true` (requires HTTPS)
- ✅ `SESSION_SAME_SITE=lax`
- ✅ `SESSION_DOMAIN=.mhrpci.site` (for subdomain sharing)
- ✅ `SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site`

### CORS Configuration
Update `app/Http/Middleware/HandleCors.php` to include production domains:
```php
$allowedOrigins = [
    'https://adminpci.mhrpci.site',
    'https://mhrpci.site',
    // Add other production domains
];
```

### HTTPS Requirements
For production with `SESSION_SECURE_COOKIE=true`:
- Ensure SSL certificate is properly configured
- All requests must use HTTPS
- Mixed content (HTTP/HTTPS) will cause session issues

## Common Issues and Solutions

### Issue: Still getting 419 errors after fixes
**Solutions**:
1. Clear all caches: `php artisan optimize:clear`
2. Check if sessions table exists and is writable
3. Verify SANCTUM_STATEFUL_DOMAINS matches your request origin
4. Check browser console for CSRF token presence
5. Ensure session driver (database/redis) is working correctly

### Issue: Sessions not persisting
**Solutions**:
1. Check database connection
2. Verify sessions table has proper permissions
3. For Redis: ensure Redis is running and accessible
4. Check `SESSION_LIFETIME` isn't too short

### Issue: CORS errors along with CSRF
**Solutions**:
1. Ensure frontend domain is in `$allowedOrigins`
2. Verify `Access-Control-Allow-Credentials: true` is set
3. Check that frontend sends requests with `credentials: 'include'`

### Issue: Different domains/subdomains
**Solutions**:
1. Set `SESSION_DOMAIN=.yourdomain.com` (note the leading dot)
2. Add all subdomains to `SANCTUM_STATEFUL_DOMAINS`
3. Ensure cookies can be shared across subdomains

## Testing CSRF Protection

### Test 1: Verify CSRF Token is Present
Open browser console and run:
```javascript
console.log(document.querySelector('meta[name="csrf-token"]').content);
```

### Test 2: Check Session Cookie
Check browser DevTools > Application > Cookies
Look for `laravel_session` or `[app_name]_session` cookie

### Test 3: Test POST Request
```javascript
// This should work
axios.post('/your-endpoint', { data: 'test' })
  .then(response => console.log('Success:', response))
  .catch(error => console.error('Error:', error.response.status));
```

### Test 4: Check Response Headers
In Network tab, verify POST responses include:
- `X-CSRF-TOKEN` header
- Proper CORS headers

## Security Best Practices

1. **Never** add `api/*` to CSRF exceptions unless absolutely necessary
2. **Only** exclude third-party webhooks from CSRF verification
3. **Always** use HTTPS in production
4. **Never** set `SESSION_SECURE_COOKIE=false` in production
5. **Keep** `SESSION_SAME_SITE=lax` or `strict` for security
6. **Limit** `SANCTUM_STATEFUL_DOMAINS` to trusted domains only

## Additional Resources

- [Laravel CSRF Protection](https://laravel.com/docs/11.x/csrf)
- [Laravel Sanctum SPA Authentication](https://laravel.com/docs/11.x/sanctum#spa-authentication)
- [Session Configuration](https://laravel.com/docs/11.x/session)
- [CORS in Laravel](https://laravel.com/docs/11.x/routing#cors)

## Troubleshooting Commands

```bash
# Check configuration
php artisan config:show session
php artisan config:show sanctum

# Clear everything
php artisan optimize:clear

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check if sessions table exists
php artisan tinker
>>> Schema::hasTable('sessions');

# View current session driver
php artisan tinker
>>> config('session.driver');
```

## Support

If you continue experiencing issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode temporarily: `APP_DEBUG=true`
3. Check PHP error logs
4. Verify all middleware is properly loaded
