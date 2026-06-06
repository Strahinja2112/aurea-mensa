# Project Plan — Web Restaurant App

## Status: ~60% complete

---

## ✅ What's Already Done

| Area | Files | Status |
|------|-------|--------|
| Backend Classes | `Auth`, `Database`, `User`, `Mailer`, `ORMTable`, `Helpers`, `Env` | Complete |
| Database Schema | `seed.sql` with 7 tables, FKs, indexes | Complete |
| Auth System | Register, Login, Logout, Activation, Password Reset | Complete |
| Landing & About | `index.php`, `pages/about/index.php` | Complete |
| Registration | `pages/auth/register/` - form + validation | Complete |
| Profile Page | Edit name/phone/password, view+cancel reservations | Complete |
| Reservation Form | Date/time/duration/table selection with Fetch API | Complete |
| Create Reservation Action | Overlap check, email confirmation | Complete |
| Cancel Reservation Action | 4h cancellation rule | Complete |
| Staff Dashboard | View today's reservations, check-in, add comments | Complete |
| Seed System | Database seeding UI + actions | Complete |
| CSS | 8 CSS files (globals, auth, profile, landing, dashboard, etc.) | Complete |
| Components | Header (role-aware nav), Footer, Reservation Card | Complete |
| Mailer | Activation, password reset, reservation confirmation emails via Mailtrap | Complete |

---

## ❌ What's Missing / Needs Fixing

### Priority: HIGH

| # | Task | Details |
|---|------|---------|
| 1 | **Admin Dashboard — table CRUD** | Currently a placeholder. Need: add/edit/delete tables, view all reservations by date, manage staff users. File: `pages/dashboard/admin/index.php` |
| 2 | **Reservation form bug** | `pages/reserve-table/index.php` lines 188-196: JSON response is never parsed (commented out), `return;` blocks all success/error handling. The form submits but user never sees the result. |
| 3 | **External JS file** | Requirement: "obavezna upotreba spoljašnjeg fajla sa JavaScript kodom". All JS is currently inline. Need to extract into e.g. `src/scripts/app.js`. |

### Priority: MEDIUM

| # | Task | Details |
|---|------|---------|
| 4 | **Profile: view cancelled/completed reservations** | Currently only shows ACTIVE reservations (`pages/profile/index.php` line 67). User can't see their history. |
| 5 | **Reservation edit feature** | Requirement says user can "izmeni, pregleda i obriše svoje rezervacije" — only cancel exists. |
| 6 | **Staff: check-in by reservation code** | Requirement says staff should verify the reservation code. Currently just clicks a button. |
| 7 | **Staff dashboard filter/order bug** | `pages/dashboard/staff/index.php` line 24: `'order'` should be `'orderBy'` per ORMTable API. |
| 8 | **Profile cancel URL wrong** | `pages/profile/index.php` line 273: fetches from `server/actions/...` but actual path is `src/actions/...`. |
| 9 | **Client-side validation improvements** | Fetch API for email availability check during registration, password strength indicator, etc. |

### Priority: LOW

| # | Task | Details |
|---|------|---------|
| 10 | **Auth auto-verifies users** | `src/classes/Auth.php` line 46 sets `email_verified => 1` but still sends activation email — contradictory. |
| 11 | **Debug code left in** | `pages/auth/activate-account/index.php` line 13: `var_dump($token);` |
| 12 | **Dead code in Mailer** | `src/classes/Mailer.php` line 127-128: `return true;` after `return $mail->send();` is unreachable. |
| 13 | **Restaurant map** | "Poželjno mapa restorana sa rasporedom stolova" — optional feature. |

---

## Recommended Work Order

1. Fix the reservation form JS bug (highest impact on functionality)
2. Build the admin dashboard (CRUD tables, view reservations, manage staff)
3. Create an external JS file and move inline scripts there
4. Fix profile to show all reservation statuses + add edit capability
5. Fix the minor bugs (cancel URL, orderBy, var_dump, Mailer dead code)
6. Add check-in by reservation code for staff
7. Better client-side validation via Fetch API
8. Restaurant map (optional)
