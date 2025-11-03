# MTAV TODO List

**Last Updated**: 2025-11-03

## P0 (Critical - Blocking Production)
✅ All P0 items completed

---

## P1 (High Priority - Should Complete Soon)

### Invitation System Stability
- [ ] **Manual testing - Full invitation flow**
  - [ ] Test admin invitation (happy path)
  - [ ] Test member invitation (happy path)
  - [ ] Test edge cases (invalid tokens, already verified users, logout during invitation, etc.)
  - Status: Currently working on this - fixing bugs to get happy path working first
  - Priority: IMMEDIATE - needed to validate implementation before moving forward

### Storage & File Management
- [ ] **Verify storage:link in deployment**
  - Added to `.docker/scripts/setup.sh` and `.docker/scripts/setup-first.sh`
  - Status: ✅ Added to setup scripts
  - Priority: HIGH - needed for avatar uploads

### Queue Workers (Production + Dev)
- [ ] **Configure Laravel queue workers for production and development**
  - **Production**: Set up Supervisord to run `php artisan queue:work` processes
    - Configure in `.docker/` setup for production container
    - Files to modify: Add supervisord config for queue workers in Docker setup
  - **Development**: Set up automatic queue workers for local development
    - Options: Laravel Sail's queue worker, or separate Docker service, or supervisord in dev container
    - Currently using `QUEUE_CONNECTION=sync` as workaround (immediate execution, not testing real async behavior)
  - Currently: Emails are queued but not processed (no workers running)
  - Status: Not implemented - using sync driver for dev, prod needs automation
  - Priority: HIGH - needed before production deployment (invitation emails won't send without this)
  - Note: Both dev and prod should use async queues to test real conditions

### Spanish Localization
- [ ] **Review Spanish email templates with native speaker**
  - Files: `resources/views/emails/es_UY/{admin,member}-invitation.blade.php`
  - Note: Currently using "tú" form (correct)
  - Status: Pending review from you (native Uruguayan speaker)
  - Priority: MEDIUM - functional but may need polish

---

## P2 (Medium Priority - Nice to Have)

### Invitation System Enhancements
- [ ] **Token expiration**
  - Add configurable expiration (e.g., 7 days)
  - Add re-send capability for expired invitations
  - Status: Deferred - not urgent, can add when needed
  - Impact: Security improvement, user experience enhancement

- [ ] **Invitation resend functionality**
  - Allow admin to resend invitation email if user lost it
  - UI: Add "Resend Invitation" button for unverified users
  - Backend: Generate new token, send new email
  - Status: Not implemented
  - Impact: Reduces support burden for lost invitation emails

- [ ] **Email queueing for invitations**
  - Convert `Mail::send()` → `Mail::queue()` in `SendUserInvitation` listener
  - Requires queue configuration (Redis/database)
  - Status: Currently synchronous (blocking)
  - Impact: Performance improvement for user creation flow
  - Question: Is this already queued somehow? Need to verify.

---

## P3 (Low Priority - Polish)

### Avatar System (Complete Overhaul Needed)
- [ ] **Implement full avatar upload system**
  - Currently using placeholders everywhere
  - Not implemented in factories
  - Desired features:
    - Placeholder images until user uploads real photo
    - Preview before upload
    - Drag-drop upload
    - Crop functionality
    - Avatar management for: Members, Admins, Families
  - Status: Completely unimplemented - procrastinated due to many ideas
  - Priority: DEFERRED - remind after tomorrow's meeting
  - Impact: Visual polish, user experience

### Email Templates
- [ ] **HTML email client testing**
  - Test in various email clients (Gmail, Outlook, Apple Mail, etc.)
  - Test dark mode rendering
  - Test accessibility (screen readers)
  - Note: Currently using simple table-based markup (good for old clients like Outlook)
  - Status: Not tested beyond basic rendering
  - Priority: LOW - community tool, not commercial app, some artsiness tolerated
  - Impact: Improved compatibility, professional appearance

---

## Completed Items

### Invitation System Core
- ✅ Event/Listener pattern implemented (`UserRegistration` → `SendUserInvitation`)
- ✅ Admin and Member invitation emails (Spanish + English)
- ✅ Invitation acceptance flow (`InvitationController`)
- ✅ Service layer for business logic (`InvitationService`)
- ✅ Complete test suite (7 test files with comprehensive coverage)
- ✅ Redirect handling for authenticated but unverified users
- ✅ Email verification on invitation acceptance

### Code Quality & Documentation
- ✅ Knowledge Base updated with correct field names (`email_verified_at`, `invitation_accepted_at`)
- ✅ Documentation updated with correct route (`/invitation`, not `/confirm-account`)
- ✅ Flash message prop renamed (`no-dismiss` → `no-auto-dismiss`)
- ✅ Spanish email translations use correct "tú" form (not "vos")
- ✅ Deleted redundant test documentation (tests are self-documenting)

### Infrastructure
- ✅ Added `storage:link` to Docker setup scripts

---

## Notes

- **No self-registration**: Invitation is THE ONLY way to create users. Without invitations, there are no users, no admins, no app.
- **Three-phase flow**: Pre-invitation (UI + controller) → Invitation (email) → Post-invitation (acceptance + completion)
- **Named route**: Use `home` (not `dashboard`) - may update in future to reduce confusion
- **Testing approach**: Manual testing for happy path first, then edge cases - not using manual testing as TDD
- **Prioritization**: Community tool, not commercial app - tolerate some rough edges for speed

