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
- [x] **Configure Laravel queue workers for production and development**
  - **Production**: DONE (2026-07-12 infra rewrite) — supervisord runs 3 `queue:work`
    processes in the prod `queue` service (`docker/services/queue/`)
  - **Development**: uses `QUEUE_CONNECTION=deferred` (no workers needed)
  - Remaining consideration: dev doesn't exercise real async behavior; add a dev
    queue worker service only if that ever matters for testing

---

## P2 (Medium Priority - Nice to Have)

### Docker Infrastructure
- [x] **~~Create and publish `mtav-php-base-dev` image~~** — OBSOLETE
  - Superseded by the 2026-07-12 infra rewrite: `docker/services/php/Dockerfile`
    is multi-stage (base → dev/prod → queue) built locally with layer caching;
    no registry images involved, rebuilds only pay for changed layers

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
