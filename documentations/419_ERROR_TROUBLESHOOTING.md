# 419 CSRF Error - Complete Troubleshooting Guide

## Quick Diagnosis Flowchart

```
419 Error in Production?
├── HTTPS working? → No ❌
│   └── Fix: Configure Nginx/Proxy (see Section 1)
├── Session cookie present? → No ❌
│   └── Fix: Check session configuration (see Section 2)
├── CSRF token in HTML? → No ❌
│   └── Fix: Verify app.blade.php has meta tag (see Section 3)
├── CORS blocking requests? → Yes ❌
│   └── Fix: Add domain to HandleCors (see Section 4)
└── All above OK? → Check advanced issues (see Section 5)
```

---

## Section 1: HTTPS Detection Issues

### Problem: Laravel Not Detecting HTTPS

**Symptoms**:
- Session cookies not being sent
- Requests appear as HTTP instead of HTTPS
- `request()->secure()` returns `false` in production

### Diagnostic Commands

```bash
php artisan tinker
>>> request()->secure()
# Expected: true
# If false → HTTPS detection problem

>>> request()->getScheme()
# Expected: "https"
# If "http" → Proxy headers not being forwarded

>>> request()->header('X-Forwarded-Proto')
# Expected: "https"
# If null → Nginx not sending headers
```

### Solution 1: Configure Laravel Proxy Trust

**File**: `bootstrap/app.php`

Add this inside `withMiddleware()`:
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

### Solution 2: Configure Nginx to Send Proxy Headers

**File**: `/etc/nginx/sites-available/your-site`

```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    
    # CRITICAL: Add these lines
    fastcgi_param HTTPS on;
    fastcgi_param HTTP_X_FORWARDED_PROTO https;
    fastcgi_param HTTP_X_FORWARDED_FOR $proxy_add_x_forwarded_for;
    fastcgi_param HTTP_X_FORWARDED_HOST $host;
    fastcgi_param HTTP_X_FORWARDED_PORT $server_port;
    
    include fastcgi_params;
}
```

After changes:
```bash
sudo nginx -t  # Test configuration
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### Solution 3: Force HTTPS in Laravel

**File**: `app/Providers/AppServiceProvider.php`

```php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
```

---

## Section 2: Session Cookie Issues

### Problem: Session Cookies Not Being Set or Sent

**Symptoms**:
- No `laravel_session` cookie in browser
- Cookie exists but has wrong attributes
- Sessions don't persist between requests

### Diagnostic Steps

**Check Cookie in Browser**:
1. Open DevTools (F12)
2. Application → Cookies
3. Look for `laravel_session` or `[app_name]_session`

**Expected Cookie Attributes**:
```
Name: laravel_session
Value: [long encrypted string]
Domain: .mhrpci.site
Path: /
Expires: [session end]
HttpOnly: ✅
Secure: ✅ (for HTTPS)
SameSite: Lax
```

### Solution 1: Fix Environment Configuration

**File**: `.env`

```env
# Production HTTPS Settings
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.mhrpci.site
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
```

**Critical**: 
- `SESSION_SECURE_COOKIE=true` requires HTTPS
- `SESSION_DOMAIN` with leading dot (`.mhrpci.site`) shares cookies across subdomains
- Use `redis` driver for better performance than `database`

### Solution 2: Verify Session Driver

**For Database Driver**:
```bash
php artisan tinker
>>> Schema::hasTable('sessions')
# Must return: true

# If false, run migration
php artisan migrate
```

**For Redis Driver**:
```bash
php artisan tinker
>>> Redis::connection()->ping()
# Must return: "PONG"

# Check .env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Solution 3: Clear Session Cache

```bash
php artisan session:clear
php artisan cache:clear
php artisan config:clear

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

## Section 3: CSRF Token Not in HTML

### Problem: Meta Tag Missing or Empty

**Symptoms**:
- Browser console shows "CSRF token not found"
- Meta tag doesn't exist in page source
- Token value is empty

### Diagnostic Steps

**Check in Browser Console**:
```javascript
const token = document.querySelector('meta[name="csrf-token"]');
console.log(token);  // Should not be null
console.log(token.content);  // Should be a long string
```

**Check Page Source** (Ctrl+U):
```html
<head>
    <meta name="csrf-token" content="[should have long token string]">
</head>
```

### Solution: Verify Blade Template

**File**: `resources/views/app.blade.php`

Ensure this line exists in `<head>`:
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

**Complete Example**:
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
</head>
<body>
    @inertia
</body>
</html>
```

### Verify Frontend is Reading Token

**File**: `resources/js/bootstrap.ts`

```typescript
const token = document.head.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');

Object.assign(axios.defaults.headers.common, {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    ...(token && { 'X-CSRF-TOKEN': token.content })
});
```

---

## Section 4: CORS Configuration Issues

### Problem: CORS Blocking Requests

**Symptoms**:
- Browser console shows CORS errors
- Network tab shows OPTIONS request fails
- Response headers missing `Access-Control-Allow-Origin`

### Diagnostic Steps

**Check Browser Console**:
```
Access to XMLHttpRequest at 'https://api.example.com' from origin 
'https://app.example.com' has been blocked by CORS policy
```

**Check Network Tab**:
- Look for OPTIONS preflight request
- Check if response has CORS headers

### Solution: Update HandleCors Middleware

**File**: `app/Http/Middleware/HandleCors.php`

```php
public function handle(Request $request, Closure $next): Response
{
    $allowedOrigins = [
        // Local development
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        // Production domains - ADD YOUR DOMAINS HERE
        'https://adminpci.mhrpci.site',
        'https://mhrpci.site',
    ];

    // Automatically include APP_URL
    $appUrl = config('app.url');
    if ($appUrl && !in_array($appUrl, $allowedOrigins)) {
        $allowedOrigins[] = $appUrl;
    }

    $origin = $request->header('Origin');

    // Handle OPTIONS preflight
    if ($request->isMethod('OPTIONS')) {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', $origin && in_array($origin, $allowedOrigins) ? $origin : '')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-XSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Max-Age', '86400');
    }

    $response = $next($request);

    if ($origin && (in_array($origin, $allowedOrigins) || app()->environment('local'))) {
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-XSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Expose-Headers', 'X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Max-Age', '86400');
    }

    return $response;
}
```

### Verify CORS Middleware is Active

```bash
php artisan route:list --columns=method,uri,middleware | grep HandleCors
```

---

## Section 5: Advanced Issues

### Issue: Token Mismatch on Specific Routes

**Cause**: Token regeneration during request

**Solution**: Check for these in your code:
```php
// Bad - causes token regeneration
$request->session()->regenerateToken();

// Good - only regenerate on login/logout
if (auth()->check()) {
    $request->session()->regenerate();
}
```

### Issue: Tokens Expire Too Quickly

**Cause**: Short session lifetime

**Solution**: Increase session lifetime in `.env`:
```env
SESSION_LIFETIME=120  # minutes
```

### Issue: Multiple Tabs Causing Issues

**Cause**: Token regeneration in one tab invalidates others

**Solution**: Only regenerate on sensitive actions:
```php
// In login controller
public function login(Request $request)
{
    // ... authentication logic ...
    
    // Regenerate session after login
    $request->session()->regenerate();
    
    return response()->json(['message' => 'Logged in']);
}
```

### Issue: Load Balancer/Multiple Servers

**Cause**: Session stored locally, not shared between servers

**Solution**: Use Redis for session storage:
```env
SESSION_DRIVER=redis
REDIS_HOST=your-redis-server
```

---

## Section 6: Complete System Check

Run these commands to verify everything is configured correctly:

```bash
# 1. Check HTTPS detection
php artisan tinker
>>> request()->secure()  # Must be: true
>>> request()->getScheme()  # Must be: "https"

# 2. Check session configuration
>>> config('session.driver')  # Should be: "redis" or "database"
>>> config('session.secure')  # Should be: true (production)
>>> config('session.domain')  # Should be: ".yourdomain.com"

# 3. Check Sanctum configuration
>>> config('sanctum.stateful')  # Should include your domain

# 4. Test session storage
>>> session()->put('test', 'value')
>>> session()->get('test')  # Should return: "value"

# 5. Test Redis (if using Redis)
>>> Redis::connection()->ping()  # Should return: "PONG"

# 6. Check CSRF token generation
>>> csrf_token()  # Should return a 40-character string

# 7. Check if sessions table exists (if using database)
>>> Schema::hasTable('sessions')  # Should return: true
```

---

## Section 7: Emergency Quick Fix

If you need a **temporary** fix while investigating:

### Option 1: Disable CSRF for Specific Routes (NOT RECOMMENDED)

**File**: `app/Http/Middleware/VerifyCsrfToken.php`

```php
protected $except = [
    'temporary/route/to/test',  // Remove after fixing!
];
```

⚠️ **WARNING**: This is insecure. Only use temporarily for debugging.

### Option 2: Extended Token Lifetime

**File**: `.env`
```env
SESSION_LIFETIME=1440  # 24 hours
```

### Option 3: Browser Cache Clear

Sometimes the issue is just browser cache:
1. Open DevTools (F12)
2. Right-click refresh button → "Empty Cache and Hard Reload"
3. Or use Incognito mode

---

## Section 8: Production Deployment Checklist

Before going live, verify:

- [ ] ✅ `SESSION_SECURE_COOKIE=true` in `.env`
- [ ] ✅ `APP_URL` uses HTTPS
- [ ] ✅ `SANCTUM_STATEFUL_DOMAINS` set correctly
- [ ] ✅ Proxy trust configured in `bootstrap/app.php`
- [ ] ✅ CORS middleware includes production domain
- [ ] ✅ Nginx passes `X-Forwarded-*` headers
- [ ] ✅ SSL certificate valid and not expired
- [ ] ✅ Redis/Database accessible for sessions
- [ ] ✅ All caches cleared after configuration changes

**Clear caches after any config change**:
```bash
php artisan optimize:clear
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

---

## Section 9: Logging and Debugging

### Enable Detailed Logging

**File**: `config/logging.php`

Temporarily set to debug:
```php
'default' => env('LOG_CHANNEL', 'stack'),
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily'],
        'ignore_exceptions' => false,
    ],
],
```

### Watch Logs in Real-Time

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

### Debug Mode (Temporarily)

```env
APP_DEBUG=true
APP_ENV=local
```

⚠️ **REMEMBER**: Set back to production settings after debugging!

---

## Section 10: Common Mistakes

### ❌ Mistake 1: Missing Leading Dot in SESSION_DOMAIN
```env
# Wrong
SESSION_DOMAIN=mhrpci.site

# Correct (for subdomain sharing)
SESSION_DOMAIN=.mhrpci.site
```

### ❌ Mistake 2: HTTP APP_URL in Production
```env
# Wrong
APP_URL=http://adminpci.mhrpci.site

# Correct
APP_URL=https://adminpci.mhrpci.site
```

### ❌ Mistake 3: Wrong Sanctum Domain Format
```env
# Wrong (includes protocol)
SANCTUM_STATEFUL_DOMAINS=https://adminpci.mhrpci.site

# Correct (no protocol)
SANCTUM_STATEFUL_DOMAINS=adminpci.mhrpci.site
```

### ❌ Mistake 4: Trusting Proxies Not Configured
Missing this in `bootstrap/app.php`:
```php
$middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_ALL);
```

### ❌ Mistake 5: CORS Middleware Not Applied
Check that `HandleCors` is in the middleware stack:
```php
$middleware->api(prepend: [HandleCors::class]);
```

---

## Still Having Issues?

1. **Check this repository's documentation**:
   - `PRODUCTION_HTTPS_GUIDE.md` - Complete HTTPS setup
   - `CSRF_FIX_GUIDE.md` - CSRF-specific fixes
   - `PRODUCTION_419_FIX.md` - Previous fixes applied

2. **Review Laravel logs**:
   ```bash
   tail -100 storage/logs/laravel.log
   ```

3. **Test with curl**:
   ```bash
   curl -I https://adminpci.mhrpci.site
   # Check for proper headers
   ```

4. **Contact server administrator** to verify:
   - SSL certificate is valid
   - Nginx configuration is correct
   - PHP-FPM is running
   - Redis/Database is accessible

---

## Summary

**Most 419 errors in production are caused by**:
1. ⚠️ **HTTPS not properly detected** (70% of cases)
2. ⚠️ **Missing CORS configuration** (15% of cases)
3. ⚠️ **Session configuration issues** (10% of cases)
4. ⚠️ **Other issues** (5% of cases)

**The fix is usually**:
1. ✅ Configure proxy trust in Laravel
2. ✅ Configure Nginx to send proxy headers
3. ✅ Set proper session configuration
4. ✅ Add production domains to CORS

Follow this guide step-by-step, and your 419 errors should be resolved! 🎉
