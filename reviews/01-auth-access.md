# Authentication, accounts, invitations & authorization
_Review of main · scope: Auth/Settings/Invitation controllers, middleware, policies, AccountService, InvitationService, routes/web.php + channels.php, bootstrap/app.php, config/session.php, AppServiceProvider (gates, auth listeners), Platform\UserController (account/role management)_

## Summary
The way access is wired up holds. Every `/projects/{project}` route goes through `ResolveProject`, which returns 404 to anyone outside the project, and through `scopeBindings`. The admin area is behind `can:update,project`, and the platform console is behind `EnsureSuperadmin`, which also returns 404. Everything except the auth and verify routes sits in the `auth` + `verified` group. Invitation tokens are random, stored only as a SHA-256 hash, and throttled on accept. Google sign-in takes back an account someone opened but never confirmed, and it ends that account's sessions.

The biggest risk is session lifetime. Resetting or changing a password never ends other sessions (sessions are stored in the database, and there is no `AuthenticateSession` middleware). Neither changing the email nor deleting the account asks for a password. Together these allow a classic pre-account-hijack: someone opens an account on another person's email before that person signs up, and keeps it after the real owner takes it over.

The other issues are smaller:
- Several endpoints have no rate limit and leak information (password reset, profile email, mail sent on email change).
- The deployment's owner is not protected against other superadmins.
- A few UX and edge-case gaps in invitations, Google-created accounts and error responses.

## Findings

| # | Severity | Category | Title | Location |
|---|---|---|---|---|
| 01-01 | high | security | Password reset and password change leave every other session signed in | `app/Http/Controllers/Auth/NewPasswordController.php:58-68` |
| 01-02 | medium | security | Changing the email address or deleting the account needs no password | `app/Http/Controllers/Settings/ProfileController.php:36-67` |
| 01-03 | medium | security | Any superadmin can demote the deployment's owner, then block or delete them | `app/Http/Controllers/Platform/UserController.php:97-108` |
| 01-04 | medium | security | Email enumeration with no rate limit through POST /reset-password and the profile email check | `routes/web.php:57` |
| 01-05 | medium | security | Changing the email in the profile sends mail to any address, with no limit | `app/Http/Controllers/Settings/ProfileController.php:57-61` |
| 01-06 | low | correctness | Accounts opened with Google cannot set a password from Settings | `app/Http/Controllers/Settings/PasswordController.php:15` |
| 01-07 | low | i18n | Guest invitations read "join the  family", on the page and in the email | `app/Notifications/InvitationNotification.php:38-42` |
| 01-08 | low | security | A blocked account with the right password is signed in for a moment and logged as signed_in | `app/Http/Requests/Auth/LoginRequest.php:35-49` |
| 01-09 | low | security | The superadmin role can be given to an account whose email is unconfirmed | `app/Http/Controllers/Platform/UserController.php:85-95` |
| 01-10 | low | security | In-app invitation notices reach accounts whose email was never confirmed | `app/Services/InvitationService.php:85-96` |
| 01-11 | low | practices | 409 and 429 responses render outside the app | `bootstrap/app.php:75` |
| 01-12 | low | correctness | A pending email change cannot be cancelled | `app/Http/Controllers/Settings/ProfileController.php:57` |
| 01-13 | low | correctness | A double-submitted invitation acceptance can fail with a 500 | `app/Http/Controllers/InvitationController.php:60-68` |
| 01-14 | low | correctness | Two co-admins deleting their accounts at once can leave a project with no admin | `app/Services/AccountService.php:23-33` |
| 01-15 | low | performance | Avatar uploads (decoding up to 50 MP in GD) have no rate limit | `routes/web.php:101` |
| 01-16 | low | performance | Every page load for a superadmin lists, and works out the features of, every project on the platform | `app/Http/Middleware/HandleInertiaRequests.php:41,93-105` |
| 01-17 | low | tests | No tests for password reset, confirming a new email, ending sessions, or protecting the owner | `tests/UseCases/Accounts/` |

### 01-01. Password reset and password change leave every other session signed in
- **Severity / category:** high / security
- **Location:** `app/Http/Controllers/Auth/NewPasswordController.php:58-68`, `app/Http/Controllers/Settings/PasswordController.php:19`, `bootstrap/app.php:42-48` (web group without `AuthenticateSession`), `config/session.php:21` (default driver `database`)
- **Problem:** The reset callback only sets a new password hash and a new `remember_token`. `PasswordController::update` sets only the hash and leaves `remember_token` unchanged. Nothing calls `Auth::logoutOtherDevices`, deletes rows from `sessions`, or uses the `auth.session` middleware. The `PasswordReset` listener (`AppServiceProvider.php:98`) only writes to the activity log. The code ends sessions elsewhere (`GoogleController.php:63`, `AccountService.php:55`), but not here.
- **Failure scenario:**
  1. The attacker registers `victim@coop.org` (`RegisteredUserController.php:35-41`) and stays signed in, unverified.
  2. The victim is invited. `InvitationController::show` (lines 36-40) sees an account exists and sends them to the login page. They use "forgot password", and the reset link reaches their real inbox.
  3. After the reset, the attacker's database session is untouched. The idle lifetime is 120 minutes, so a polling script keeps it alive.
  4. The victim signs in and accepts the invitation. That marks the email verified (`InvitationService.php:130`).
  5. The attacker's session now passes `verified`. It can read the victim's project, chats and people. Through 01-02 it can move the email to an inbox the attacker controls, which takes the account for good.

  Separately, a stolen session survives the owner changing their password in Settings, and so do remember-me cookies (`remember_token` is not rotated).
- **Recommended fix:** After a reset, delete every row in `sessions` for that user. After a Settings change, delete all of them except the current one, and rotate `remember_token` (or call `Auth::logoutOtherDevices`). Alternatively, add `AuthenticateSession` to the web group so every session is tied to the password hash. Consider marking the email verified when a reset succeeds, since the link proves the person owns the inbox.
- **Verification note:** Read both controllers, the session config, the web middleware stack and the `PasswordReset` listener. Nothing ends other sessions on either path.

### 01-02. Changing the email address or deleting the account needs no password
- **Severity / category:** medium / security
- **Location:** `app/Http/Controllers/Settings/ProfileController.php:36-67`, `app/Http/Controllers/Settings/ProfileController.php:75-93`, `app/Http/Controllers/Auth/EmailVerificationController.php:133-140`, `routes/web.php:99-100`
- **Problem:** `PATCH settings/profile` stores `new_email` and mails the confirmation link to that new address without asking for the current password. `verify()` only checks that the signed-in user's id matches the link. `DELETE settings/profile` asks only for the account's own email, which is shown on the same page. No route uses `password.confirm` or `current_password`.
- **Failure scenario:** Someone holding a live session (a shared computer, a stolen cookie, or the leftover session from 01-01) sets the email to `attacker@evil.test` and confirms it from their own inbox. They now own the account, and the real owner only gets an `EmailChangeNotice` at the old address with nothing they can do about it. Or they type the displayed email into the deletion box, and the account and all its memberships are deleted.
- **Recommended fix:** Require `current_password` (or put the `password.confirm` middleware on the route) when the email changes and for account deletion. Google-only accounts never knew a password, so give them a fitting re-authentication step, such as signing in with Google again.
- **Verification note:** Traced the validation rules in `update` and `destroy` and the `verify()` branch for `new_email`. No re-authentication step exists.

### 01-03. Any superadmin can demote the deployment's owner, then block or delete them
- **Severity / category:** medium / security
- **Location:** `app/Http/Controllers/Platform/UserController.php:97-108`, `app/Http/Controllers/Platform/UserController.php:68-73`, `resources/js/pages/Platform/Superadmins.tsx:78-88`
- **Problem:** `superadmin()` refuses only to revoke one's own role and never checks `is_owner`. `ensureNotProtected` protects only current superadmins. `superadmins()` doesn't even send `is_owner` to the page, which offers "Remove the role" for everyone but the viewer. Elsewhere the owner is meant to be permanent: `ProfileController.php:82` refuses self-deletion, and `ErrorLog.php:53` mails only the owner.
- **Failure scenario:** Superadmin B, rogue or using a stolen session, sends `PUT admin/users/{owner}/superadmin` with `grant=0`, then `PUT admin/users/{owner}/blocked` with `block=1` (or `DELETE admin/users/{owner}`). `SignOutBlocked` throws the owner out for good. `RequireOwner` never recreates an owner while accounts exist (`RequireOwner.php:63`), and error notices go to nobody. Recovering takes direct database access.
- **Recommended fix:** In `superadmin()`, refuse to revoke the role when the target has `is_owner`, and make `ensureNotProtected` reject `is_owner` too. Send `is_owner` to the Superadmins page and hide the revoke button for the owner.
- **Verification note:** Read `UserController` end to end and the Superadmins page. No `is_owner` check exists on these paths. The original report said the page already receives `is_owner`; it does not (only id, name, email).

### 01-04. Email enumeration with no rate limit through POST /reset-password and the profile email check
- **Severity / category:** medium / security
- **Location:** `routes/web.php:57`, `app/Http/Controllers/Auth/NewPasswordController.php:44-50`, `lang/en/passwords.php` (`user` vs `token`), `app/Http/Controllers/Settings/ProfileController.php:43`, `routes/web.php:99`
- **Problem:** `PasswordResetLinkController` deliberately gives the same answer whether an account exists or not. But `POST reset-password` has no throttle and returns `__($status)`. An unknown email gets `passwords.user` ("We can't find a user with that email address."), a known one gets `passwords.token`. `PATCH settings/profile` runs `Rule::unique` on the email, also without a throttle, so its error tells whether an address has an account.
- **Failure scenario:** A script posts `{token:'x', email:<candidate>, password:'aaaaaaaa', password_confirmation:'aaaaaaaa'}` to `/reset-password` for thousands of addresses. The error text tells which ones have accounts, and no 429 ever comes. Any verified account can do the same through the profile form.
- **Recommended fix:** Throttle `password.store` and `profile.update`. In `NewPasswordController`, show the same message (`passwords.token`) for both `INVALID_USER` and `INVALID_TOKEN`. For the profile, accept any address and handle a clash when it is confirmed, as `verify()` already does with its 409.
- **Verification note:** Neither route has throttle middleware, the controller returns the broker status as is, and the two language strings differ.

### 01-05. Changing the email in the profile sends mail to any address, with no limit
- **Severity / category:** medium / security
- **Location:** `app/Http/Controllers/Settings/ProfileController.php:57-61`, `app/Notifications/VerifyEmailNotification.php:38`, `routes/web.php:99`
- **Problem:** Every change to an email that is neither the current one nor the pending one sends a `VerifyEmailNotification` to that address, plus an `EmailChangeNotice` to the old one. The route has no throttle. The mail's greeting is "Hello :name!", with the account's `firstname` (up to 80 characters, chosen by whoever owns the account).
- **Failure scenario:** A self-registered, verified account alternates `email` through a list of addresses many times a second, with its first name set to a spam line. Each request sends a platform-branded mail to a stranger that opens with the attacker's text. That hurts the deployment's sending reputation and floods the targets.
- **Recommended fix:** Throttle `profile.update` (for example `throttle:6,1`), and add a per-user limit on email changes per hour through `RateLimiter`, answered with a validation message.
- **Verification note:** Read the condition at line 57 and the route definition, and confirmed the greeting interpolates `firstname`.

### 01-06. Accounts opened with Google cannot set a password from Settings
- **Severity / category:** low / correctness (downgraded from medium: signing in with Google keeps working, and forgot-password is a workaround)
- **Location:** `app/Http/Controllers/Auth/GoogleController.php:61-62,72`, `app/Http/Controllers/Settings/PasswordController.php:15`, `resources/js/components/account/PasswordForm.tsx:18-23`
- **Problem:** Google sign-up stores `Str::password(40)`, which nobody knows, with the comment "a password can be set later". So does taking an account back. But `PasswordController` requires `current_password`, and the form always asks for it. The only way left is forgot-password, which is in the `guest` group, so the person must sign out first.
- **Failure scenario:** Ana signs up with Google and opens Settings > Password to set one for another device. She always gets "The password is incorrect", and nothing tells her to sign out and use "forgot password".
- **Recommended fix:** Record whether a password was ever chosen (a nullable `password_set_at`, for example). When it was not, skip `current_password` and hide that field. Or offer, from Settings, to email a reset link to the signed-in user.
- **Verification note:** Confirmed the random passwords in `GoogleController`, the `current_password` rule, and the field that is always shown.

### 01-07. Guest invitations read "join the  family", on the page and in the email
- **Severity / category:** low / i18n (downgraded from medium: the wording is wrong, nothing breaks)
- **Location:** `app/Http/Requests/InvitationRequest.php:27`, `app/Notifications/InvitationNotification.php:38-42`, `resources/js/pages/Auth/Invitation.tsx:39-44`, `lang/en/emails.php:8`
- **Problem:** In a project with `allows_guests`, the request lets an invitation have neither a family nor the admin role. Both the email's `match` and the page's ternary then fall through to the "family" wording with an empty family name. No guest wording exists in any language file.
- **Failure scenario:** An admin invites `ana@x.test` as a guest. The email reads "Bea invites you to join the  family in Coop Sur.", and the invitation page says the same. Ana expects to join a family that does not exist.
- **Recommended fix:** Add an `emails.invitation_guest` string ("to join :project") and a matching frontend string, and pick them when there is no family and `is_admin` is false.
- **Verification note:** Read both branches, and grepped `lang/` and `resources/js` for guest wording; there is none.

### 01-08. A blocked account with the right password is signed in for a moment and logged as signed_in
- **Severity / category:** low / security
- **Location:** `app/Http/Requests/Auth/LoginRequest.php:35-49`, `app/Providers/AppServiceProvider.php:90-93`
- **Problem:** `Auth::attempt` signs the blocked user in, which fires `Login` and writes a `signed_in` row to the activity log. It also clears the rate limiter. Only then is the user signed out (another log row, `signed_out`) and shown `auth.blocked`. That message differs from `auth.failed`, so it confirms the password was right.
- **Failure scenario:** Someone guessing the password of a blocked account learns when a guess is right, because the message changes, and can reuse that password elsewhere. Anyone auditing the activity log sees sign-ins on closed accounts.
- **Recommended fix:** Use `Auth::attemptWhen($credentials, fn ($u) => ! $u->isBlocked(), $remember)`, which never signs a blocked account in, and answer with `auth.failed`.
- **Verification note:** Traced the attempt, the rate-limiter clear, the logout order and the `Login`/`Logout` listeners.

### 01-09. The superadmin role can be given to an account whose email is unconfirmed
- **Severity / category:** low / security
- **Location:** `app/Http/Controllers/Platform/UserController.php:85-95`, `app/Providers/AppServiceProvider.php:59`, `routes/channels.php:19`
- **Problem:** `grant()` sets `is_superadmin` on any account with that exact email, without checking `email_verified_at`.
- **Failure scenario:** An attacker registers `new.colleague@coop.org` before the colleague does. A superadmin grants the role "to that email". While still unverified, the attacker's session joins the `superadmins` broadcast channel (it checks only the role) and receives the names of every project in the shared `projects` prop on `verify-email`. If the real colleague then takes the account back by resetting the password and verifying, 01-01 leaves the attacker holding a full superadmin session. (The original report also said a blocked account given the role could never be unblocked. That is wrong: revoking the role first, as `ensureNotProtected`'s comment intends, allows unblocking. Blocked users are also signed out by `SignOutBlocked`.)
- **Recommended fix:** Refuse, with a validation message, unless the account is verified and not blocked.
- **Verification note:** Read `grant()`, `Gate::before`, the channel definitions and the routes reachable without verification (`routes/web.php:80-93`).

### 01-10. In-app invitation notices reach accounts whose email was never confirmed
- **Severity / category:** low / security
- **Location:** `app/Services/InvitationService.php:85-96`, `app/Http/Middleware/HandleInertiaRequests.php:45-49`, `routes/channels.php:9`
- **Problem:** After emailing the invitation, `invite()` sends a PRIVATE in-app notification to `User::firstWhere('email', …)`, whether or not that account is verified. An unverified user can still load `verify-email` and the documentation pages. There they get the unread count, and through a partial reload, `recentNotifications`. They also receive `users.{id}` broadcasts live.
- **Failure scenario:** Someone who opened an account on `maria@coop.org` without owning it learns "Bea invites you to join Coop Sur" (who invited, which project) as soon as an admin invites the real Maria, even though the tokenized link went only to her inbox.
- **Recommended fix:** Send the in-app notice only when the invitee's email is verified.
- **Verification note:** Confirmed `Notification::scopeFor` includes PRIVATE notifications for the user, and that the shared props are evaluated on unverified-reachable pages.

### 01-11. 409 and 429 responses render outside the app
- **Severity / category:** low / practices
- **Location:** `bootstrap/app.php:75`, `app/Http/Controllers/Auth/EmailVerificationController.php:136`, `app/Http/Controllers/InvitationController.php:95`, `routes/web.php:47,50,76-78`
- **Problem:** Besides 413 and 419, the handler restyles only 403, 404, 500 and 503. Any other status is returned as the framework's raw response, and `resources/views/errors` does not exist.
- **Failure scenario:** Someone confirms a new email that another account took in the meantime: a bare 409 page appears instead of a message on their profile. A guest submits the invitation form after an account was opened with that email: the Inertia XHR gets a raw 409. The 11th sign-in POST in a minute from one IP gets a 429, and its HTML shows up in Inertia's error modal.
- **Recommended fix:** Handle 409 and 429 like a 403 on non-GET requests (`back()` with a translated flash such as `flash.too_many_attempts`), and render the `Error` page for GET. Or throw `ValidationException` instead of `abort(409)` in the two controllers.
- **Verification note:** Read the `respond` callback. The status whitelist excludes 409 and 429.

### 01-12. A pending email change cannot be cancelled
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/Settings/ProfileController.php:57`, `resources/js/pages/Settings/Profile.tsx:162-165`
- **Problem:** When the submitted email equals the current one, nothing touches `new_email`. The pending change stays, its link stays valid for its hour, and the field's hint keeps saying "Confirm {new_email} to change it".
- **Failure scenario:** A user types `ana@gmial.com` by mistake, notices, and puts the old address back. The page reports "profile updated", but the typo stays pending, with no way to clear it.
- **Recommended fix:** When the submitted email equals the current email, set `new_email` to null.
- **Verification note:** Read the condition and the frontend hint.

### 01-13. A double-submitted invitation acceptance can fail with a 500
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/InvitationController.php:60-68`, `app/Http/Controllers/InvitationController.php:93-108`, `app/Services/InvitationService.php:112-133`
- **Problem:** `isPending()` is checked before the transaction, without a lock. `Membership::firstOrNew` and `User::create` then rely on the unique indexes, `memberships(user_id, project_id)` and `users.email`, to stop concurrent requests.
- **Failure scenario:** A double tap or a retried request sends two `POST invitations/{token}` at once. Both pass `isPending()`, and the second hits a `UniqueConstraintViolationException`. The person sees a 500 even though the first request worked, and `ErrorLog` mails the owner.
- **Recommended fix:** Inside the transaction, re-read the invitation with `lockForUpdate` and return early if `accepted_at` is set. In `createAccount`, catch the unique violation and redirect to sign-in.
- **Verification note:** Confirmed both unique indexes in the migrations and the lock-free check-then-act.

### 01-14. Two co-admins deleting their accounts at once can leave a project with no admin
- **Severity / category:** low / correctness
- **Location:** `app/Services/AccountService.php:23-33`, `app/Http/Controllers/Settings/ProfileController.php:86-88`, `app/Http/Controllers/Platform/UserController.php:61-62`
- **Problem:** `ensureCanGo` counts the admins, and `delete` runs in a separate step, with no transaction or lock in between.
- **Failure scenario:** A project's only two admins submit "delete my account" at the same moment. Each check sees 2 admins, both deletions go through, and the project is left with nobody to run it.
- **Recommended fix:** Run the check and the deletion in one transaction that locks the affected projects' admin memberships `FOR UPDATE`, and check again inside it.
- **Verification note:** Read both call sites; neither is in a transaction.

### 01-15. Avatar uploads (decoding up to 50 MP in GD) have no rate limit
- **Severity / category:** low / performance
- **Location:** `routes/web.php:101`, `app/Http/Controllers/Settings/AvatarController.php:24-47`, `app/Services/MediaService.php:47`
- **Problem:** Each POST accepts up to 8 MB and 50 megapixels, which is roughly 200 MB of decoded GD bitmap. The image is rescaled and rewritten on every request, and the route has no throttle.
- **Failure scenario:** A verified account posts a highly compressible 50 MP PNG to `settings/avatar` in a few parallel loops. Each request ties up a PHP worker and a large memory allocation, so pages slow down or fail for everyone.
- **Recommended fix:** Add `throttle:10,1` (or similar) to `avatar.update`.
- **Verification note:** Route definition and validator confirmed; no throttle.

### 01-16. Every page load for a superadmin lists, and works out the features of, every project on the platform
- **Severity / category:** low / performance
- **Location:** `app/Http/Middleware/HandleInertiaRequests.php:41,93-105`, `app/Models/User.php:108-113`
- **Problem:** For superadmins, `reachableProjects()` is `Project::query()`. The shared `projects` closure runs on every non-partial Inertia response. It loads all projects, calls `features()` on each, and sends the whole list for the project switcher.
- **Failure scenario:** With a few hundred cooperatives, every navigation by a superadmin runs an unbounded query and adds hundreds of entries to the page props.
- **Recommended fix:** For superadmins, send only the projects they are members of, plus the one being viewed. Leave "all projects" to the paginated platform console.
- **Verification note:** Read the shared props and `reachableProjects()`.

### 01-17. No tests for password reset, confirming a new email, ending sessions, or protecting the owner
- **Severity / category:** low / tests
- **Location:** `tests/UseCases/Accounts/EmailChangeTest.php:12`, `tests/UseCases/Accounts/`
- **Problem:** `EmailChangeTest` has a single test, which checks only that the mail goes out. No test exercises `password.store` or the `new_email` branch of `verify()` (including its 409, and the old link after a second change), and none covers the owner-revocation path.
- **Failure scenario:** The gap in 01-01, and any regression in `verify()` (for example a stale `new_email` link being accepted), would ship without any test failing.
- **Recommended fix:** Add tests for these cases:
  - After a reset, another session's next request is a guest.
  - An unknown email gets the same reset message as a bad token.
  - Confirming `new_email` changes the email.
  - Confirming an address that was taken in the meantime is refused cleanly.
  - An old confirmation link stops working after a second change.
  - Revoking the owner's role is refused.
- **Verification note:** Listed `tests/UseCases/Accounts` and grepped `tests/` for these routes. Only the browser helpers build plain verification links.

## Checked, nothing to report
- **Route coverage:** every authenticated route is in `auth` + `verified`, apart from logout, the verify routes and documentation. `/projects/{project}/*` goes through `ResolveProject` (404) and `scopeBindings`, `manage/*` through `can:update,project`, and `/admin/*` through `EnsureSuperadmin` (404), with deployments behind `can:deploy`.
- **Broadcast channels:** each checks the user's own id, project access, or the superadmin role.
- **Invitations:**
  - The token is `Str::random(48)` and stored as a hash.
  - `accept` checks that the signed-in email matches the invitation.
  - `respond` is limited to verified users whose email matches.
  - Inviting a blocked address is refused.
- **Google sign-in:** it rejects unverified Google emails and blocked accounts. Taking back an unconfirmed account rotates the password and the remember token and deletes its sessions.
- **Blocking:** blocking rotates the remember token and deletes sessions, and `SignOutBlocked` runs on every web request.
- **Login:** throttled per email+IP inside the request and per IP on the route, and the session is regenerated on login.
- **Email verification:** the links are signed, expire, check the user's id, and compare the hash with `hash_equals`.
- **Password storage:** passwords use the `hashed` cast, and `Password::defaults()` applies on register, reset, invitation and Settings.
