# Quick Fix Steps - 419 CSRF Error

## Immediate Actions Required

### Step 1: Run Migration
```bash
cd c:\laragon\www\company_web\mhrpci-backend
php artisan migrate
```
This creates the required `sessions` table for database session storage.

### Step 2: Clear All Caches
```bash
php artisan optimize:clear
```
Or run individually:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Restart Server
If running `php artisan serve`:
- Press `Ctrl+C` to stop
- Run `php artisan serve` again

### Step 4: Clear Browser Data
- Open DevTools (F12)
- Application/Storage tab
- Clear cookies for your domain
- Refresh the page

## Verify the Fix

### Check 1: CSRF Token Present
Open browser console:
```javascript
document.querySelector('meta[name="csrf-token"]').content
```
Should output a token string.

### Check 2: Session Cookie
DevTools > Application > Cookies
Should see `laravel_session` cookie.

### Check 3: Test POST Request
Try submitting a form or making a POST request.
Should work without 419 error.

## What Was Fixed

✅ Created sessions table migration
✅ Added custom CSRF verification middleware
✅ Configured SANCTUM_STATEFUL_DOMAINS in .env
✅ Updated CORS to allow CSRF token headers
✅ Added missing session security settings
✅ Fixed session configuration for production

## If Still Not Working

1. Check `storage/logs/laravel.log` for errors
2. Verify database connection is working
3. Ensure `.env` changes are loaded (restart server)
4. Try in incognito/private browser window
5. Check the full guide: `CSRF_FIX_GUIDE.md`

## Production Deployment

Before deploying to production:
1. Update CORS allowed origins in `HandleCors.php`
2. Ensure HTTPS is enabled
3. Verify `.env.prod` settings are correct
4. Test thoroughly in staging environment

## Need Help?

See detailed documentation in `CSRF_FIX_GUIDE.md`
