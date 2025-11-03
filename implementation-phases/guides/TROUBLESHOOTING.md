# 🔧 Troubleshooting Guide: Migration & Common Issues

**Purpose:** Quick reference for resolving common issues during the FilamentPHP → Livewire + FluxUI migration and general development problems.

---

## 🚨 Critical Migration Issues

### Issue 1: Teacher/Student Still Sees FilamentPHP Panel After Login

**Symptoms:**
- Teacher logs in but sees FilamentPHP dashboard instead of FluxUI
- Can access `/app` routes even after redirect middleware
- FilamentPHP navigation is visible

**Diagnosis:**
```bash
# Check User::canAccessPanel() method
cat app/Models/User.php | grep -A 5 "canAccessPanel"
```

**Causes:**
1. `canAccessPanel()` still allows teachers/students
2. Middleware not registered or not catching requests
3. Route caching issues

**Solutions:**

**Solution A:** Update `User::canAccessPanel()` (Most Common)
```php
// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    // Only admins can access FilamentPHP panel
    return $this->hasRole('admin');  // ✅ Correct
    
    // ❌ Wrong (allows all roles):
    // return $this->hasAnyRole(['admin', 'teacher', 'student']);
}
```

**Solution B:** Verify Middleware Registration
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\RedirectBasedOnRole::class,  // ✅ Must be here
    ]);
})
```

**Solution C:** Clear Route Cache
```bash
php artisan route:clear
php artisan config:clear
php artisan optimize:clear
```

**Verification:**
```bash
# Test authentication flow
# 1. Login as teacher → should redirect to /teacher
# 2. Manually visit /app → should redirect to /teacher
# 3. Check network tab for redirect responses (302)
```

---

### Issue 2: Flux UI Components Not Rendering

**Symptoms:**
- `<flux:button>` appears as plain text
- No Flux styles applied
- Console errors: "Unknown component 'flux:button'"

**Diagnosis:**
```bash
# Check if Flux is installed
composer show livewire/flux

# Check if components exist
ls vendor/livewire/flux/resources/views/components/
```

**Causes:**
1. Flux not installed or composer autoload not refreshed
2. Assets not compiled after installation
3. `@fluxStyles` or `@fluxScripts` missing from layout

**Solutions:**

**Solution A:** Verify Installation
```bash
# Reinstall Flux
composer require livewire/flux

# Refresh autoloader
composer dump-autoload
```

**Solution B:** Include Flux Directives in Layout
```blade
<!-- resources/views/layouts/teacher.blade.php -->
<head>
    <!-- ... -->
    @fluxStyles  <!-- ✅ Required -->
    @vite('resources/css/app.css')
</head>
<body>
    {{ $slot }}
    
    @fluxScripts  <!-- ✅ Required -->
    @vite('resources/js/app.js')
</body>
```

**Solution C:** Rebuild Assets
```bash
npm run build

# Or for development:
npm run dev
```

**Solution D:** Clear View Cache
```bash
php artisan view:clear
php artisan optimize:clear
```

**Verification:**
```bash
# Create test route
Route::get('/test-flux', fn() => view('test-flux'));

# Create test view: resources/views/test-flux.blade.php
<html>
<head>
    @fluxStyles
</head>
<body>
    <flux:button variant="primary">Test Button</flux:button>
    @fluxScripts
</body>
</html>

# Visit http://localhost/test-flux
# Expected: Styled button appears
```

---

### Issue 3: Routes Not Found (404) for `/teacher` or `/student`

**Symptoms:**
- Accessing `/teacher` returns 404
- Routes not registered in `php artisan route:list`

**Diagnosis:**
```bash
# Check if routes are defined
php artisan route:list | grep teacher
php artisan route:list | grep student
```

**Causes:**
1. Routes not defined in `routes/web.php`
2. Route cache outdated
3. Middleware blocking access (returns 404 instead of 403)

**Solutions:**

**Solution A:** Define Routes in `routes/web.php`
```php
// routes/web.php

// Teacher Routes
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', \App\Livewire\Teacher\Dashboard::class)->name('dashboard');
    Route::get('/aktivitas', \App\Livewire\Teacher\AktivitasPembelajaran\ListAktivitas::class)
        ->name('aktivitas.list');
    // ... other routes
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', \App\Livewire\Student\Dashboard::class)->name('dashboard');
    // ... other routes
});
```

**Solution B:** Clear Route Cache
```bash
php artisan route:clear
php artisan route:cache  # Optional: Cache routes for production
```

**Solution C:** Verify Livewire Component Exists
```bash
# Check if component class exists
ls app/Livewire/Teacher/Dashboard.php

# Check namespace
cat app/Livewire/Teacher/Dashboard.php | grep "namespace"
# Expected: namespace App\Livewire\Teacher;
```

**Verification:**
```bash
php artisan route:list --name=teacher
# Should show:
# teacher.dashboard
# teacher.aktivitas.list
# etc.
```

---

### Issue 4: Teachers/Students Can Access `/app` Directly by URL

**Symptoms:**
- Teacher types `/app` in URL → can access admin panel
- Middleware redirect not working for direct URL access

**Diagnosis:**
```bash
# Check if middleware is running
# Add debug log to RedirectBasedOnRole middleware
\Log::info('RedirectBasedOnRole middleware triggered', ['user' => auth()->id()]);
```

**Causes:**
1. Middleware not catching direct URL access
2. Middleware only runs on certain routes
3. Authentication check happening before middleware

**Solutions:**

**Solution A:** Verify Middleware Logic
```php
// app/Http/Middleware/RedirectBasedOnRole.php

public function handle(Request $request, Closure $next)
{
    if (auth()->check()) {
        $user = auth()->user();
        
        // ✅ Catch /app access for non-admins
        if ($request->is('app') || $request->is('app/*')) {
            // Allow login/logout routes
            if ($request->is('app/login', 'app/logout')) {
                return $next($request);
            }
            
            // Redirect non-admins
            if ($user->hasRole('teacher')) {
                return redirect('/teacher');
            }
            
            if ($user->hasRole('student')) {
                return redirect('/student');
            }
        }
    }
    
    return $next($request);
}
```

**Solution B:** Ensure Middleware Runs on All Web Routes
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    // ✅ Add to 'web' middleware group (runs on all web routes)
    $middleware->web(append: [
        \App\Http\Middleware\RedirectBasedOnRole::class,
    ]);
})
```

**Solution C:** Update `canAccessPanel()` as Backup
```php
// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    // Double-check: only admins can access panel
    return $this->hasRole('admin');
}
```

**Verification:**
```bash
# Test direct URL access
# 1. Login as teacher
# 2. Manually type: http://localhost/app
# 3. Should redirect to: http://localhost/teacher

# Check network tab for 302 redirect
```

---

## ⚠️ Common Development Issues

### Issue 5: Asset Not Found / 404 for CSS/JS Files

**Symptoms:**
- Browser console shows 404 for `app.css` or `app.js`
- Styles not applied

**Solutions:**
```bash
# Rebuild assets
npm run build

# Check if Vite is running (development)
npm run dev

# Verify public/build directory exists
ls public/build/
```

---

### Issue 6: Livewire Component Not Updating After Changes

**Symptoms:**
- Changes to Blade view not reflecting
- Component logic not running

**Solutions:**
```bash
# Clear view cache
php artisan view:clear

# Restart php artisan serve
# Ctrl+C then php artisan serve

# Hard refresh browser
# Ctrl+Shift+R (Windows/Linux)
# Cmd+Shift+R (Mac)
```

---

### Issue 7: Session/Authentication Issues After Migration

**Symptoms:**
- Users logged out unexpectedly
- "Session expired" errors

**Solutions:**
```bash
# Clear sessions
php artisan cache:clear
php artisan session:flush  # If available

# Regenerate application key (CAUTION: logs out all users)
php artisan key:generate

# Clear browser cookies and try again
```

---

### Issue 8: Database Query Performance Issues

**Symptoms:**
- Pages load slowly
- Laravel Debugbar shows 50+ queries per page

**Solutions:**

**Solution A:** Add Eager Loading
```php
// ❌ N+1 problem
$aktivitas = AktivitasPembelajaran::all();
foreach ($aktivitas as $a) {
    echo $a->mataPelajaran->nama_mapel;  // Triggers query for each item
}

// ✅ Eager loading (1 query)
$aktivitas = AktivitasPembelajaran::with('mataPelajaran')->get();
foreach ($aktivitas as $a) {
    echo $a->mataPelajaran->nama_mapel;
}
```

**Solution B:** Use `select()` to Limit Columns
```php
// ❌ Fetches all columns
$siswa = Siswa::all();

// ✅ Only fetch needed columns
$siswa = Siswa::select('id', 'nis', 'user_id')->get();
```

**Solution C:** Paginate Large Datasets
```php
// ❌ Loads all records
$aktivitas = AktivitasPembelajaran::all();

// ✅ Paginate
$aktivitas = AktivitasPembelajaran::paginate(15);
```

---

### Issue 9: Flux UI Dark Mode Not Working

**Symptoms:**
- Dark mode toggle has no effect
- Styles don't switch

**Solutions:**

**Solution A:** Check CSS Configuration
```css
/* resources/css/app.css */
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';

/* ✅ Add custom dark variant */
@custom-variant dark (&:where(.dark, .dark *));
```

**Solution B:** Rebuild Assets
```bash
npm run build
```

---

### Issue 10: "Class not found" Error for Livewire Component

**Symptoms:**
- Error: `Class 'App\Livewire\Teacher\Dashboard' not found`

**Solutions:**

**Solution A:** Check Namespace
```php
// app/Livewire/Teacher/Dashboard.php

<?php

namespace App\Livewire\Teacher;  // ✅ Correct

// ❌ Wrong:
// namespace App\Http\Livewire\Teacher;
// namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    // ...
}
```

**Solution B:** Refresh Autoloader
```bash
composer dump-autoload
```

**Solution C:** Verify File Location
```bash
# Component should be at:
app/Livewire/Teacher/Dashboard.php

# NOT at:
app/Http/Livewire/Teacher/Dashboard.php  # ❌ Wrong location
```

---

## 🔍 Debugging Tools & Commands

### Quick Diagnostics

```bash
# Check Laravel version
php artisan --version

# Check installed packages
composer show

# List all routes
php artisan route:list

# List all Livewire components
php artisan livewire:list

# Check current user
php artisan tinker
>>> auth()->user()
>>> auth()->user()->roles

# Clear all caches
php artisan optimize:clear

# Check database connection
php artisan db:show

# View application logs
tail -f storage/logs/laravel.log
```

### Laravel Debugbar

```bash
# Enable Debugbar (already installed in boilerplate)
# In .env:
DEBUGBAR_ENABLED=true

# Access in browser: Bottom of page
# Check:
# - Number of queries
# - Query execution time
# - Livewire events
```

### Browser DevTools

**Network Tab:**
- Check for 404 errors (missing assets)
- Check for 302 redirects (authentication flow)
- Check for 403 errors (authorization issues)

**Console Tab:**
- Check for JavaScript errors
- Check for Livewire errors
- Check for missing component errors

**Application Tab:**
- Check cookies (session_id should exist)
- Check local storage (for Livewire persist)

---

## 📞 Getting Help

### Before Asking for Help

1. **Check this troubleshooting guide first**
2. **Check Laravel logs:** `storage/logs/laravel.log`
3. **Check browser console** for JavaScript errors
4. **Run diagnostics:**
   ```bash
   php artisan optimize:clear
   composer dump-autoload
   npm run build
   ```

### Where to Ask

1. **Filament Discord:** https://filamentphp.com/discord
2. **Livewire Discord:** https://livewire.laravel.com/discord
3. **Laravel Forge Forum:** https://forge.laravel.com/forum
4. **Stack Overflow:** Tag with `laravel`, `livewire`, `filament`

### Providing Error Details

When asking for help, include:
- Laravel version: `php artisan --version`
- PHP version: `php -v`
- Error message (full stack trace)
- Relevant code snippets
- Steps to reproduce
- What you've already tried

---

## 📚 Useful Resources

- **Laravel Docs:** https://laravel.com/docs
- **Livewire Docs:** https://livewire.laravel.com/docs
- **Flux UI Docs:** https://fluxui.dev/docs
- **Filament Docs:** https://filamentphp.com/docs
- **Spatie Permission:** https://spatie.be/docs/laravel-permission

---

**Last Updated:** _________________  
**Maintainer:** Project Team
