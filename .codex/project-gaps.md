# Project Gaps Audit

Reviewed: 2026-06-08

Scope reviewed: project PHP/CSS/JS/SQL files and `.codex` documentation files excluding `.codex/skills`.

## Summary

The project is further along than `.codex/plan.md` says. Admin table layout, staff check-in by reservation code, external `table-layout.js`, and admin reservation views are already implemented.

Current estimated completion for the assignment: **75-80%**.

The most important remaining work is reservation editing, staff creation, CSRF/security hardening, debug cleanup, inline JS cleanup, seed reset fixes, and final documentation.

## Highest Priority Gaps

1. **Reservation editing is missing**

   Users can view and cancel reservations, but the requirement says users can also edit reservations.

   Current relevant files:
   - `pages/profile/index.php`
   - `src/actions/cancel_reservation.php`
   - `pages/reserve-table/index.php`

2. **Admin staff creation appears missing**

   Admin can edit and delete staff accounts, but there is no visible create/add staff flow. `pages/dashboard/admin/staff/index.php` checks for `?added`, which suggests the feature was planned.

3. **CSRF protection is missing**

   State-changing requests rely on authentication cookies but do not include CSRF tokens.

   Affected examples:
   - profile update
   - cancel reservation
   - check-in
   - add comment
   - staff edit/delete
   - seed actions
   - table layout save

   Reference:
   - OWASP CSRF Prevention Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html

4. **Inline JavaScript still exists**

   The project has `src/scripts/table-layout.js`, but several pages still include inline scripts.

   Current examples:
   - `src/components/header.php`
   - `pages/profile/index.php`
   - `pages/reserve-table/index.php`
   - `pages/dashboard/staff/index.php`
   - `pages/auth/register/index.php`
   - `pages/auth/login/index.php`
   - `pages/auth/forgot-password/index.php`

   Since the assignment requires external JavaScript files, this should be cleaned up.

## Bugs / Fragile Parts

1. **Seed reset is broken**

   `src/actions/seed.php` calls:

   ```php
   db()->reservationTables->delete(['where' => []]);
   ```

   But `ORMTable::delete()` requires a non-empty `where`, so `seedAll()` cannot reset the database correctly.

2. **Mailer URL generation is fragile**

   `Mailer::getAppUrl()` appends `:3000` when host is `localhost`. That only works if BrowserSync is actually running on that port.

3. **Session hardening is incomplete**

   Current cookie setup uses `httponly` and `SameSite=Lax`, which is good, but there is no:
   - `secure` flag for HTTPS
   - session token rotation
   - expired session cleanup strategy
   - login attempt throttling

   Reference:
   - OWASP Session Management Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html

4. **No rate limiting**

   These flows can be spammed:
   - login
   - forgot password
   - resend activation
   - reservation code check-in

## Feature Completeness Gaps

1. **Reservation lifecycle is incomplete**

   The database has `COMPLETED`, but check-in currently sets `arrived = 1`; it does not mark reservations completed automatically after use.

2. **Admin reservation management is read-only**

   Admin can view reservations by date, but cannot edit, cancel, or override them.

3. **Staff comments are not editable in the UI**

   Staff can add a comment if none exists, but once a comment exists the UI only displays it.

4. **Client-side validation could be stronger**

   Current validation is mostly required fields and basic patterns. Useful additions:
   - duplicate email/phone check during registration
   - password strength feedback
   - clearer date/time validation
   - better feedback for unavailable reservation periods

5. **Admin table CRUD is visual, but limited**

   The table layout editor is good, but data entry is tied to the map. Consider whether the final assignment expects a more traditional table/list CRUD view as well.

## Submission Readiness

1. **Documentation is incomplete**

   `README.md` mostly repeats the assignment. Final submission should include:
   - setup steps
   - `.env` explanation
   - database import/migration order
   - seed instructions
   - test/demo accounts
   - feature list
   - screenshots
   - deployment notes
   - used assets/templates/libraries

2. **Encoding problems are visible**

   Many Serbian strings appear as broken characters such as `Ä`, `Å¡`, etc. This should be fixed before presentation.

3. **No automated tests**

   There are no tests for reservation overlap logic, auth flows, role access, or cancellation rules.

4. **No migration runner**

   SQL migrations exist, but applying them is manual.

5. **No admin view for logs**

   Error logging exists, but there is no UI for admins to view recent logs.

6. **Deployment protections are minimal**

   `.htaccess` blocks `.env`, but consider protecting other private paths like:
   - `logs/`
   - `.codex/`
   - `db/`
   - `composer.*`

## Recommended Work Order

1. Remove debug output.
2. Fix `seedAll()` reset behavior.
3. Add reservation edit flow.
4. Add admin staff creation.
5. Add CSRF tokens to all state-changing forms/actions.
6. Move remaining inline JavaScript into external files.
7. Harden auth/session behavior and add basic rate limiting.
8. Fix visible encoding issues.
9. Expand final README/project documentation.
10. Add focused tests or at least a manual test checklist.
