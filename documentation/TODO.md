# MTAV TODO List

**Last Updated**: 2025-11-28

---

## P1 (High Priority - Should Complete Soon)

- [ ] **Fix avatar upload functionality**
  - Avatar upload from profile settings page is failing
  - Need to debug why upload request is not succeeding
  - Status: Upload flow implemented but not working
  - Priority: HIGH - core user feature

- [ ] **Fix avatar UI when error occurs**
  - Avatar component moves/shifts position when error message is displayed
  - Should stay fixed in place with error appearing without layout shift
  - Status: Attempted fix with `self-start` and `min-h-[3rem]` but still broken
  - Priority: MEDIUM - UX polish

- [ ] **File upload reactivity issue**
  - Files added to form don't show in UI immediately (reactivity not triggering)
  - When adding another file, previously "missing" files still don't appear
  - Object-based files storage works for duplicates but reactivity still broken
  - Likely issue with Vue reactivity and File objects in nested form state
  - Priority: MEDIUM - upload works but UX is confusing

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

---

## P2 (Medium Priority - Nice to Have)

### Docker Infrastructure
- [ ] **Create and publish `mtav-php-base-dev` image**
  - Separate dev base image with Node.js, npm, Playwright, and browser dependencies
  - Based on production base, adding dev-only tools on top
  - Would speed up dev container builds (currently rebuilds Playwright each time)
  - Push to `ghcr.io/devvir/mtav-php-base-dev:latest`
  - Status: Not implemented - currently installing in `.docker/php/Dockerfile`
  - Impact: Faster dev container rebuilds, cleaner separation of concerns

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
  - Desired features:
    - Preview before upload
    - Drag-drop upload
    - Crop functionality
    - Avatar management for: Members, Admins, Families
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
