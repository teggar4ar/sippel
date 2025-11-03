# Authentication Architecture - Single Login with Role-Based UI

## Overview

SIPPEL uses a **single authentication system** with **role-based UI rendering**. All users log in through the same URL, but see different interfaces based on their role.

## Architecture Diagram

```
┌─────────────────────────────────────────────────┐
│           /app/login (Single Login)             │
│        FilamentPHP Authentication Panel          │
└──────────────────┬──────────────────────────────┘
                   │
                   │ Authenticate User
                   │
        ┌──────────▼──────────┐
        │   Check User Role   │
        └─────────┬───────────┘
                  │
        ┌─────────┼─────────┐
        │         │         │
    Admin     Teacher    Student
        │         │         │
        ▼         ▼         ▼
   ┌────────┐  ┌────────┐  ┌────────┐
   │ /app   │  │/teacher│  │/student│
   │FilamentPHP  │Flux UI │  │Flux UI │
   │Desktop │  │ Mobile │  │ Mobile │
   └────────┘  └────────┘  └────────┘
```

## Implementation Details

### Phase 1-2: Foundation (Current)

**Login URL:** `/app/login` (FilamentPHP login page)

**Status:** ✅ Already implemented
- Single FilamentPHP panel at `/app`
- All roles can log in through `/app/login`
- All roles currently see FilamentPHP interface (temporary)
- `canAccessPanel()` allows all roles with: admin, teacher, student

**Why this works for now:**
- Admins can use FilamentPHP resources (Tasks 2.1-2.2 completed)
- Teachers/students can log in (even if UI isn't optimized yet)
- Foundation is ready for Phase 3 migration

---

### Phase 3: Add Role-Based Redirect + Flux UI

**What Changes:**
1. **Install Flux UI** for mobile interfaces
2. **Add custom redirect logic** after login
3. **Create teacher routes** with Livewire + Flux UI
4. **Middleware** prevents teachers/students from accessing `/app` after login

**Task 3.1: Authentication Redirect** (NEW - 2 hours)

Create `RedirectBasedOnRole` middleware:
```php
if (auth()->check()) {
    $user = auth()->user();
    
    // Redirect based on role after login
    if ($user->hasRole('admin')) {
        // Allow access to /app (FilamentPHP)
        return $next($request);
    }
    
    if ($user->hasRole('teacher')) {
        // Redirect to /teacher (Flux UI)
        if ($request->is('app') || $request->is('app/*')) {
            if (!$request->is('app/login', 'app/logout')) {
                return redirect('/teacher');
            }
        }
        return $next($request);
    }
    
    if ($user->hasRole('student')) {
        // Redirect to /student (Flux UI)
        if ($request->is('app') || $request->is('app/*')) {
            if (!$request->is('app/login', 'app/logout')) {
                return redirect('/student');
            }
        }
        return $next($request);
    }
}
```

**Custom Login Page:**
```php
// app/Filament/Pages/Auth/Login.php
protected function getRedirectUrl(): string
{
    $user = auth()->user();
    
    return match(true) {
        $user->hasRole('admin') => '/app',
        $user->hasRole('teacher') => '/teacher',
        $user->hasRole('student') => '/student',
        default => '/app',
    };
}
```

---

### Routes Structure (After Phase 3)

**FilamentPHP Routes** (Admins only):
```
/app/login          → Login page (all roles)
/app/logout         → Logout (all roles)
/app                → Admin dashboard (FilamentPHP)
/app/tahun-ajaran   → Academic years (FilamentPHP)
/app/kelas          → Classes (FilamentPHP)
/app/siswa          → Students (FilamentPHP)
/app/mata-pelajaran → Subjects (FilamentPHP)
```

**Teacher Routes** (Livewire + Flux UI):
```
/teacher            → Teacher dashboard (Flux UI)
/teacher/aktivitas  → Activity list (Flux UI)
/teacher/aktivitas/create → Create activity (Flux UI)
/teacher/aktivitas/{id}/edit → Edit activity (Flux UI)
/teacher/laporan    → Reports (Flux UI)
```

**Student Routes** (Livewire + Flux UI):
```
/student            → Student dashboard (Flux UI)
/student/kehadiran  → Attendance history (Flux UI)
/student/nilai      → Grade history (Flux UI)
/student/laporan    → Personal report (Flux UI)
```

---

## Benefits of This Approach

### ✅ **Single Authentication System**
- One login URL for all users: `/app/login`
- Simpler for users (no confusion about which URL to use)
- Easier to maintain (single authentication logic)

### ✅ **Role-Based UI Rendering**
- Admins get FilamentPHP (powerful desktop tools)
- Teachers get Flux UI (mobile-optimized for daily operations)
- Students get Flux UI (mobile-first for viewing progress)

### ✅ **Security Benefits**
- Middleware prevents unauthorized access
- Teachers can't access admin routes
- Students can't access teacher routes
- All use same authentication guards

### ✅ **Development Benefits**
- No need for separate panels in FilamentPHP
- Can use best tool for each role (FilamentPHP vs Flux UI)
- Easier testing (single login endpoint)
- Cleaner codebase

---

## User Experience Flow

### Admin Login:
1. Visit `/app/login`
2. Enter credentials (email + password)
3. Click "Login"
4. ✅ Redirected to `/app` (FilamentPHP dashboard)
5. See navigation: Master Data, Manajemen, etc.
6. Can manage all resources via FilamentPHP

### Teacher Login:
1. Visit `/app/login` (same URL)
2. Enter credentials
3. Click "Login"
4. ✅ Redirected to `/teacher` (Flux UI dashboard)
5. See mobile-friendly interface with cards
6. Can record activities, view classes, generate reports

### Student Login:
1. Visit `/app/login` (same URL)
2. Enter credentials
3. Click "Login"
4. ✅ Redirected to `/student` (Flux UI dashboard)
5. See personal stats, attendance, grades
6. Can view progress and download reports

---

## Implementation Timeline

| Phase | Task | Status | Time |
|-------|------|--------|------|
| **Phase 1** | Single FilamentPHP panel | ✅ Complete | 15h |
| **Phase 2** | Admin resources (TahunAjaran, Kelas) | ✅ Complete | 30h |
| **Phase 3.1** | Auth redirect + middleware | ��� Pending | 2h |
| **Phase 3.2** | Install Flux UI | ��� Pending | 1h |
| **Phase 3.3-3.7** | Teacher interface (Flux UI) | ��� Pending | 43h |
| **Phase 4** | Student interface (Flux UI) | ��� Pending | 24h |

---

## FAQ

### Q: Why not use separate FilamentPHP panels?
**A:** FilamentPHP panels are great for desktop admin interfaces but not optimized for mobile. Flux UI provides better mobile UX for teachers/students who use smartphones.

### Q: Can teachers still use desktop?
**A:** Yes! Flux UI is responsive and works on desktop too. It's just optimized for mobile-first experience.

### Q: What if user has multiple roles?
**A:** Laravel Spatie Permission supports multiple roles. Redirect logic uses priority: admin > teacher > student.

### Q: Can we add a "switch role" feature later?
**A:** Yes! You can add a role switcher in the user profile dropdown for users with multiple roles.

### Q: Do we need separate databases?
**A:** No! All roles use the same database. Separation is only in the UI layer.

---

## Security Checklist

- [ ] ✅ Single authentication guard (`web`)
- [ ] ✅ CSRF protection on all forms
- [ ] ✅ Role-based middleware on routes
- [ ] ✅ `canAccessPanel()` checks in FilamentPHP
- [ ] ✅ Authorization policies on Livewire components
- [ ] ✅ Session-based authentication (no API tokens)
- [ ] ✅ Logout clears sessions properly

---

## Conclusion

This architecture provides the best of both worlds:
- **Admins** get powerful FilamentPHP tools (desktop)
- **Teachers** get mobile-optimized Flux UI (daily operations)
- **Students** get simple, touch-friendly Flux UI (view progress)

All through a **single login URL** with **automatic role-based routing**.

Simple. Clean. Maintainable. ���
