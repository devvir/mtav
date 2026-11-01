# Frontend pages & layouts (UX, accessibility, i18n)
_Review of main · scope: resources/js/pages/**, resources/js/layouts, resources/js/app.tsx, resources/views (guides), lang/ (en, es_UY, fr, it, pt_BR)_

## Summary
The pages are in good shape. Every visible string goes through `_()`/`_n()`. A static extraction found 1,009 distinct keys used in `resources/js`, and es_UY, fr, it and pt_BR each translate all of them (1,049 keys in each locale, identical key sets with matching placeholders; `en.json` is empty on purpose). Forms use labels, server errors and loading buttons. Images have alt text, most icon-only controls have names, and per-role trimming of props happens on the server. The biggest risks:
- **A lost lottery ranking.** A ranking change waiting in the 600 ms debounce is dropped when the member navigates away, while the page says changes are saved as you make them.
- **The landing carousel.** It auto-rotates with an `aria-live` caption and has no lasting pause control.
- **Navigation.** Inertia page changes never move focus or announce the new page, and there is no skip link.

Everything else is low severity: double-submits, identical page titles, a few i18n and formatting problems, the full Ziggy route map sent to anonymous visitors, and a locale test that never compares the locales with the code.

## Findings
| # | Severity | Category | Title | Location |
|---|---|---|---|---|
| 06-01 | medium | correctness | Lottery ranking change silently lost on navigation (debounced save dropped on unmount) | resources/js/pages/Member/Lottery.tsx:89 |
| 06-02 | medium | accessibility | Landing carousel announces its caption every 6 s, no lasting pause control | resources/js/pages/Welcome.tsx:340 |
| 06-03 | medium | accessibility | No focus move or announcement after client-side navigation; no skip link | resources/js/components/layout/AppShell.tsx:151 |
| 06-04 | low | correctness | One-click actions without an in-flight guard; double tap on "Join" shows a 404 page | resources/js/pages/Projects/Index.tsx:42 |
| 06-05 | low | security | Full Ziggy route map (admin/deployment routes included) sent to anonymous visitors | app/Http/Middleware/HandleInertiaRequests.php:60 |
| 06-06 | low | correctness | App stays blank if the locale chunk fails to load | resources/js/app.tsx:63 |
| 06-07 | low | accessibility | Three Manage pages share the title and h1 "Settings" | resources/js/pages/Manage/Invitations.tsx:88 |
| 06-08 | low | accessibility | Every row action in invitation lists is named just "Withdraw" | resources/js/pages/Manage/Invitations.tsx:222 |
| 06-09 | low | accessibility | Ranking "Saving" status is not a live region; no "Saved" confirmation | resources/js/pages/Member/Lottery.tsx:269 |
| 06-10 | low | accessibility | Notification rows put `<p>` blocks inside a `<button>`; huge accessible names | resources/js/pages/Notifications/Index.tsx:64 |
| 06-11 | low | accessibility | Two h1s on the Guide page; Preferences groups without headings | resources/js/pages/Documentation/Guide.tsx:9 |
| 06-12 | low | performance | Background prefetch requests pages of features the project turned off (404s) | resources/js/layouts/ProjectLayout.tsx:158 |
| 06-13 | low | correctness | Pager of the live list still shown while the archive is displayed | resources/js/pages/Member/Forum/Index.tsx:152 |
| 06-14 | low | correctness | Empty states ignore an active search | resources/js/pages/Member/Events/Index.tsx:87 |
| 06-15 | low | practices | "Deploy dev" wipes dev data without confirmation, unlike "Start over" | resources/js/pages/Platform/Deployments.tsx:263 |
| 06-16 | low | i18n | Number and size formatting ignores the app language | resources/js/pages/Member/LotteryRecord.tsx:102 |
| 06-17 | low | i18n | Hard-coded "MTAV" in a white-labelled app (accessible name does not match the visible label) | resources/js/pages/PublicDocument.tsx:19 |
| 06-18 | low | i18n | Sentences built from translated fragments around values | resources/js/pages/Member/Dashboard.tsx:198 |
| 06-19 | low | tests | Locale test never checks the keys used in code; about 25 stale keys | resources/js/tests/unit/languages.test.ts:15 |
| 06-20 | low | practices | Dead props, stale type fields and orphan comments | resources/js/pages/Documentation/Guide.tsx:6 |

### 06-01. Lottery ranking change silently lost on navigation (debounced save dropped on unmount)
- **Severity / category:** medium / correctness
- **Location:** `resources/js/pages/Member/Lottery.tsx:89` (save), `:104` (rank), `:76-80` (prop reset), `:269` (badge)
- **Problem:** `rank()` updates the list on screen right away. The PUT is sent only after `useDebouncedCallback(..., 600)` fires, and that callback is created without `flushOnUnmount`, so Mantine (`@mantine/hooks` ^9) clears the pending timer when the page unmounts. The "Saving" badge depends on `saving`, which only becomes true inside the debounced callback. A second, smaller effect: every new `ranking` prop replaces the local order (lines 76-80). That includes the save's own `back()` response with `only: ['ranking','flash']`, even when a newer local move is still waiting to be sent.
- **Failure scenario:**
  - A member drags their first choice to the top and within about half a second taps "Home" in the bottom tabs (or reloads, or closes the tab). The Lottery component unmounts, the timer is cleared, no PUT is sent, and the family's ranking stays as before, although the page says "Changes are saved as you make them". Nothing ever showed "Saving".
  - Second case: a member reorders while the previous PUT is in flight. That PUT's response resets the list to the older order, and the newest move visibly disappears until its own debounce fires about 0.5 s later.
- **Recommended fix:** Create the debounced save with `flushOnUnmount: true`, and/or call `save.flush()` from a `router.on('before')` handler. Show the pending state as soon as a change is queued. Ignore an incoming `ranking` prop while a local change is pending or in flight, for example with a sequence counter. Add a test that navigates right after a reorder.
- **Verification note:** Read Lottery.tsx:66-108 and 255-285. The debounce options carry no flush, and the prop reset is unconditional. The endpoint (LotteryController::rank:85-101) returns `back()`, so the page's `ranking` prop is re-sent.

### 06-02. Landing carousel announces its caption every 6 s, no lasting pause control
- **Severity / category:** medium / accessibility (WCAG 2.2.2, 4.1.3 misuse)
- **Location:** `resources/js/pages/Welcome.tsx:310` (timer), `:340` (`<figcaption aria-live="polite">`)
- **Problem:** HeroPhotos (two photos, vicman and mesa1) advances every 6 s. Its caption is a polite live region, so every automatic change is announced. Rotation pauses only while the figure is hovered or contains focus, or when the user prefers reduced motion. There is no pause/play control, and leaving the figure restarts the rotation. The WAI-ARIA carousel pattern sets the live region to `off` while the carousel rotates on its own.
- **Failure scenario:** A screen-reader user on `/` (also `/login` and `/register`, which render Welcome with a dialog) reads the feature sections. Every 6 s "VICMAN…" or "Mesa 1…" is queued and spoken between paragraphs, for as long as the page is open. A touch user cannot stop the motion, because tapping a dot moves focus there only until it blurs.
- **Recommended fix:** Use `aria-live="off"` while it rotates on its own and `polite` only after the user picks a dot. Add a visible pause/play button.
- **Verification note:** Read Welcome.tsx:298-352. Only hover, focus and reduced motion hold it; no other control exists.

### 06-03. No focus move or announcement after client-side navigation; no skip link
- **Severity / category:** medium / accessibility (WCAG 2.4.1, 2.4.3)
- **Location:** `resources/js/components/layout/AppShell.tsx:151` (`<main>`), `:213`; `resources/js/app.tsx:47`; `resources/js/layouts/ProjectLayout.tsx:209`
- **Problem:** The `router.on('navigate')` handlers that exist close the drawer (AppShell:213), stop tours (TourHost:93), reset the trip indicator (AppShell:90) and track offline state (offline.ts:68). None of them moves focus to `<main>` or the h1, or announces the new title. A search for skip links finds none. The sidebar nav (up to 8 project places plus Settings) and a header with several controls come before `<main>` on every page.
- **Failure scenario:** A screen-reader user activates "Lottery" in the sidebar. Inertia swaps the page, but focus stays on the link and nothing is announced. To reach the content they must tab through the rest of the nav and every header control, on every page.
- **Recommended fix:** On `navigate`, after render, focus the page h1 (`tabIndex=-1`) or `<main>`, or announce `document.title` in a polite live region. Add a visually hidden "Skip to content" link as AppShell's first focusable element.
- **Verification note:** Searched all `router.on(` uses and `.focus()` in layout/lib, plus "skip" across resources/js; no such handling exists.

### 06-04. One-click actions without an in-flight guard; double tap on "Join" shows a 404 page
- **Severity / category:** low / correctness
- **Location:** `resources/js/pages/Projects/Index.tsx:42,105,108`; `resources/js/pages/Projects/Directory.tsx:42,49`; `resources/js/pages/Manage/Invitations.tsx:122`; `resources/js/pages/Platform/Errors.tsx:59`
- **Problem:** These buttons call `router.post/delete` directly, with no loading or disabled state: invitation Join/Decline, directory Join/Ask, join-request Accept, error "Solved". `InvitationController::respond` aborts with 404 when the invitation is no longer pending (InvitationController.php:79), and bootstrap/app.php:94 renders 403/404/500/503 as the full Inertia `Error` page. Decline deletes the invitation with no confirmation (InvitationController.php:81-84), unlike the admin-side Decline, which asks first (Manage/Invitations.tsx:74).
- **Failure scenario:** On a slow connection a person double-taps "Join". Inertia cancels the first visit on the client, but the server may already have accepted it. The second POST then finds the invitation not pending and gets a 404, so the person sees "Page not found" instead of the project they just joined. A mistaken tap on "Decline" destroys the invitation, and a new one is needed from the admins.
- **Recommended fix:** Track a per-action pending state (`onStart`/`onFinish`) and pass it as `loading`/`disabled`, as Deployments.tsx already does. Confirm before declining an invitation.
- **Verification note:** Read each cited onClick and InvitationController::respond.

### 06-05. Full Ziggy route map (admin/deployment routes included) sent to anonymous visitors
- **Severity / category:** low / security (information disclosure)
- **Location:** `app/Http/Middleware/HandleInertiaRequests.php:60`; `resources/js/app.tsx:57`
- **Problem:** `'routes' => Inertia::once(fn () => (new Ziggy())->toArray())` is shared on every Inertia response, including the public Welcome page. There is no `config/ziggy.php` with groups or `except`.
- **Failure scenario:** An unauthenticated visitor reads `data-page` on `/` and gets every route name and URI, e.g. `admin.deployments.*`, `admin.superadmins.store`, `admin.errors.*` (routes/web.php:281-290). That makes it easy to map the attack surface. The routes are authorised server-side, so nothing beyond disclosure follows.
- **Recommended fix:** Define Ziggy groups (guest, member, admin) and share the group for the viewer's role, or at least exclude `admin.*` for non-superadmins.
- **Verification note:** Read the middleware share. No ziggy config exists in config/.

### 06-06. App stays blank if the locale chunk fails to load
- **Severity / category:** low / correctness
- **Location:** `resources/js/app.tsx:63`; `resources/js/lib/translations.ts:20-25`
- **Problem:** `createRoot(...).render` runs only inside `void loadLocale(...).then(...)`, with no `catch`. For non-English locales `loadLocale` awaits a dynamic import of `/lang/<locale>.json`.
- **Failure scenario:** A Spanish-speaking member on a flaky mobile connection opens the app. The es_UY chunk request fails (for example, the first visit before the service worker has cached it). The promise rejects, nothing is rendered, and the page stays white with no message or retry.
- **Recommended fix:** Add a `.catch` that calls `setLocale('en')` and renders anyway (optionally retrying the import), or render first and swap the words when they arrive.
- **Verification note:** Read app.tsx:56-71 and translations.ts:9-25.

### 06-07. Three Manage pages share the title and h1 "Settings"
- **Severity / category:** low / accessibility (WCAG 2.4.2)
- **Location:** `resources/js/pages/Manage/Overview.tsx:104`, `resources/js/pages/Manage/Invitations.tsx:88`, `resources/js/pages/Manage/Settings.tsx:50`
- **Problem:** PageHeader sets both `<Head title>` and the h1 from `title` (components/layout/PageHeader.tsx:31-33), and all three pages pass `_('Settings')`.
- **Failure scenario:** An admin with Invitations and Project open in two tabs sees "Settings · MTAV" on both and in history. Moving between them, a screen reader hears no change of heading.
- **Recommended fix:** Use distinct titles ("Invitations", "Project settings", "Setting up"), keeping "Settings" as the section label.
- **Verification note:** Searched `title=` in the three files.

### 06-08. Every row action in invitation lists is named just "Withdraw"
- **Severity / category:** low / accessibility (WCAG 2.4.6)
- **Location:** `resources/js/pages/Manage/Invitations.tsx:222`; `resources/js/pages/Member/Invitations/Index.tsx:101`
- **Problem:** Each pending invitation has an icon-only `ActionIcon aria-label={_('Withdraw')}`, so every button in the list has the same accessible name. The confirm dialog names the email, but the button does not.
- **Failure scenario:** A screen-reader user lists the buttons on the Invitations page and hears "Withdraw, Withdraw, Withdraw…". They cannot pick ana@example.com's without exploring each row.
- **Recommended fix:** `_('Withdraw the invitation to {email}', { email })`. The confirm title (`'Withdraw the invitation to {email}?'`) is already translated.
- **Verification note:** Read both cited lines.

### 06-09. Ranking "Saving" status is not a live region; no "Saved" confirmation
- **Severity / category:** low / accessibility (WCAG 4.1.3)
- **Location:** `resources/js/pages/Member/Lottery.tsx:269`; `resources/js/design/components/Status.tsx:18`
- **Problem:** `<Status tone="info" live>` uses `live` only to animate the dot. Status renders plain spans, with no `role="status"` or `aria-live`. Nothing announces that a save started or finished. A refused save (LotteryExecutionException) does flash an error toast, so failures are not fully silent, but a successful save is never confirmed.
- **Failure scenario:** A blind member reorders units with the keyboard. RankingList announces the move, but they never learn whether it was saved, which matters given 06-01.
- **Recommended fix:** Put the save state in a `role="status"` region ("Saving…" / "Saved").
- **Verification note:** Read Status.tsx in full and LotteryController::rank:94-100 (the error goes to the flash).

### 06-10. Notification rows put `<p>` blocks inside a `<button>`; huge accessible names
- **Severity / category:** low / accessibility
- **Location:** `resources/js/pages/Notifications/Index.tsx:64-77`
- **Problem:** Each row is an `UnstyledButton`, which renders `<button>`. Inside it are three Mantine `<Text>` (each a `<p>`) and a VisuallyHidden read state. `<p>` is not phrasing content, so this is invalid HTML, and the button's accessible name becomes title, full message, relative time and read state.
- **Failure scenario:** A screen-reader user tabbing through notifications hears the entire message on every Tab press, instead of a short name with the details available on request.
- **Recommended fix:** Render the Texts with `component="span"` (block via CSS). Give the button `aria-labelledby` the title plus read state and `aria-describedby` the message.
- **Verification note:** Read lines 60-80.

### 06-11. Two h1s on the Guide page; Preferences groups without headings
- **Severity / category:** low / accessibility (WCAG 1.3.1)
- **Location:** `resources/js/pages/Documentation/Guide.tsx:9`; `resources/views/guides/en/guide.member.html:2`; `resources/js/pages/Settings/Profile.tsx:208,216,222`
- **Problem:** Guide renders PageHeader (h1 "Guide") and then injects HTML that starts with its own `<h1>`. All 15 guide files (5 locales × member/admin/superadmin) contain one. Profile > Preferences labels "Appearance", "Help" and "Notifications" with bold `<Text>`, not headings.
- **Failure scenario:** Listing headings on /documentation/guide gives two level-1 headings ("Guide", "Member guide"). On Preferences, heading navigation cannot jump to "Notifications".
- **Recommended fix:** Demote the guides' h1 to h2, or drop the PageHeader title. Render the group labels as `Title order={2}`.
- **Verification note:** `grep -l "<h1" resources/views/guides/*/*.html` returns 15 files. Read the Profile lines.

### 06-12. Background prefetch requests pages of features the project turned off (404s)
- **Severity / category:** low / performance
- **Location:** `resources/js/layouts/ProjectLayout.tsx:158`, `:199`; `resources/js/lib/offline.ts:80-83`
- **Problem:** `tabsOf(id)` calls `projectPlaces({ id })` without `features`, so `on()` treats every feature as on (line 35, "unknown: as if on"). Four seconds after entering a project, `keepPlaces` calls `router.prefetch` on Events, Media and Forum. EnsureFeature aborts those with 404 when the feature is off (app/Http/Middleware/EnsureFeature.php:21).
- **Failure scenario:** In a project with forum and events disabled, every member's browser sends two extra full Inertia requests on entry, and both come back 404. Each one runs the full middleware stack, costing server work and mobile data.
- **Recommended fix:** Pass `project.features` (and `family`/`can`) into `tabsOf`, or reuse the `tabs` already built in the useMemo.
- **Verification note:** Read ProjectLayout.tsx:24-140 and 155-205 and EnsureFeature.

### 06-13. Pager of the live list still shown while the archive is displayed
- **Severity / category:** low / correctness
- **Location:** `resources/js/pages/Member/Forum/Index.tsx:152`; `resources/js/pages/Member/Media/Index.tsx:137`; `resources/js/pages/Member/Families/Index.tsx:204`
- **Problem:** `<Pagination page={…}>` sits outside the `archive?.shown` branch. Events/Index.tsx:92 puts it inside, which is correct. The server still paginates the live list (ForumController paginate(30), MediaController paginate(24), `withQueryString()`).
- **Failure scenario:** An admin of a forum with 45 topics turns on "Archived". Under the archived list they see "1–30 of 45" with prev/next arrows for the hidden active topics. "Next" reloads with `archived` still in the query, and the same archived list is shown, so the control appears to do nothing.
- **Recommended fix:** Render Pagination only when the archive is not shown.
- **Verification note:** Read the four pages, Pagination.tsx (hidden only when last_page ≤ 1) and the controllers.

### 06-14. Empty states ignore an active search
- **Severity / category:** low / correctness
- **Location:** `resources/js/pages/Member/Events/Index.tsx:87-90`; `resources/js/pages/Member/Media/Index.tsx:133`; `resources/js/pages/Projects/Index.tsx:124-158`; `resources/js/pages/Platform/Users.tsx:114`
- **Problem:** Events shows "No upcoming events" and Media shows "Nothing here yet / Be the first to share something" even when `filters.q` is set. Projects/Index filters on the client (`shown`) but renders an empty grid when nothing matches, because the branch tests `projects.length`. Platform/Users has no empty state. Forum and Families do handle this.
- **Failure scenario:** A member searches events for a mistyped word, and the page claims there are no upcoming events at all. On Projects with 12 projects, a search with no match leaves a blank area.
- **Recommended fix:** When `q` is active, show the "No … matches “{q}”" variant, and add one to Projects/Index (`!shown.length`) and Platform/Users.
- **Verification note:** Read each branch.

### 06-15. "Deploy dev" wipes dev data without confirmation, unlike "Start over"
- **Severity / category:** low / practices
- **Location:** `resources/js/pages/Platform/Deployments.tsx:263`
- **Problem:** The page says "Every deploy starts dev over, with the demo data" (line 256). "Start over" (line 117) and "Roll back" (line 104) ask for confirmation, but "Deploy dev" sends on one click. The branch defaults to `'main'` (line 89), so the button is enabled from the start.
- **Failure scenario:** A superadmin who meant to open the branch Select clicks "Deploy dev". Dev is redeployed and the testers' data there is replaced by demo data.
- **Recommended fix:** Use the same `confirm()` as `startOver`, naming the branch and the data loss.
- **Verification note:** Read lines 87-125 and 240-300.

### 06-16. Number and size formatting ignores the app language
- **Severity / category:** low / i18n
- **Location:** `resources/js/pages/Member/LotteryRecord.tsx:102`; `resources/js/lib/app.ts:12`
- **Problem:** The average place uses `toLocaleString(undefined, …)`, which follows the browser locale, while every date uses `languageTag()`. `megabytes()` hard-codes the "." decimal separator. It is used in Media/Index.tsx:142, ShareFilesForm.tsx:90 and Projects/Create.tsx:149.
- **Failure scenario:** An es_UY user with an English browser sees "Lugar promedio 2.33". Every es/fr/it/pt user reads storage as "1.5 GB".
- **Recommended fix:** `toLocaleString(languageTag(), …)`, and `Intl.NumberFormat(languageTag(), { maximumFractionDigits: 1 })` in `megabytes`.
- **Verification note:** Searched `toLocaleString|NumberFormat|megabytes(`. This is the only number formatting.

### 06-17. Hard-coded "MTAV" in a white-labelled app (accessible name does not match the visible label)
- **Severity / category:** low / i18n (WCAG 2.5.3)
- **Location:** `resources/js/pages/PublicDocument.tsx:19`; `resources/views/guides/*/guide.member.html`, `guide.admin.html`
- **Problem:** The deployment name is configurable (`config('app.name')` shared as `props.name`, `setAppName`, and the privacy page fills in `{app}` at DocumentationController.php:71). PublicDocument still sets `aria-label={_('MTAV home')}` on the logo link, which overrides the Logo's visible name. The member and admin guides in all five locales hard-code "MTAV".
- **Failure scenario:** On a deployment named "Cooperativas FUCVAM", the manual page shows that name, but the link's accessible name is "Inicio de MTAV", so a voice-control user saying the visible name gets no match. The guide tells members they are using "MTAV".
- **Recommended fix:** `_('{app} home', { app: props.name })` or no aria-label. Use an `{app}` placeholder in the guides, filled as privacy() already does.
- **Verification note:** Read PublicDocument.tsx and DocumentationController, and grepped MTAV in the guides (10 files).

### 06-18. Sentences built from translated fragments around values
- **Severity / category:** low / i18n
- **Location:** `resources/js/pages/Member/Dashboard.tsx:198,212`; `resources/js/pages/Member/Lottery.tsx:151,178,219`; `resources/js/pages/Manage/Overview.tsx:193`; `resources/js/pages/Platform/Errors.tsx:46`
- **Problem:** Sentences are built as `_('Your family received')` + `<strong>unit</strong>` + `'.'`, `_('Scheduled for')` + date, `_('Unlimited asked for')` + relative date + `'.'`, and `_('Last') … _('first')`. Word order and punctuation are fixed in code, and the locale files bend to fit: "Previsto para el", "Ilimitado pedido", "Illimité demandé".
- **Failure scenario:** French currently reads "Illimité demandé il y a 3 jours.", a fragment rather than a sentence. A locale that needs the value before the verb cannot be translated correctly at all.
- **Recommended fix:** Use whole-sentence keys with placeholders, with a small helper that interpolates React nodes.
- **Verification note:** Read each site and the fr/es_UY entries (lang/fr.json:381,518,671).

### 06-19. Locale test never checks the keys used in code; about 25 stale keys
- **Severity / category:** low / tests
- **Location:** `resources/js/tests/unit/languages.test.ts:15`
- **Problem:** The test only compares the locale files with one another and checks that values are not blank and placeholders match. A new `_('…')` with no translation passes CI and silently falls back to English. A static scan found 30 keys in each locale JSON that appear nowhere in app/, resources/ or config. About 5 of those are Laravel mail/error template strings ("Regards,", "Whoops!", "All rights reserved.") used by vendor views, so about 25 are stale, e.g. "Register the families", "Set the date", "Run it", "New here?", "Welcome to {project}".
- **Failure scenario:** A developer adds `_('Download the ranking')` and forgets the JSON files. CI stays green, and Uruguayan users see one English button.
- **Recommended fix:** Add a test that extracts `_()`/`_n()` literals from resources/js and asserts each one exists in every locale, and that reports keys nothing uses.
- **Verification note:** A Python scan found 1,009 keys used in JS, all present in fr/es_UY/it/pt_BR, and 30 unreferenced keys.

### 06-20. Dead props, stale type fields and orphan comments
- **Severity / category:** low / practices
- **Location:** `resources/js/pages/Documentation/Guide.tsx:6`; `app/Http/Controllers/DocumentationController.php:32,53`; `resources/js/pages/Notifications/Index.tsx:16`; `resources/js/pages/Member/Dashboard.tsx:41,88`; `resources/js/pages/Welcome.tsx:43-44,360`
- **Problem:**
  - `userRole` is sent to Guide and Faq, and neither page uses it.
  - Notifications' Props declares `filters.q`, but NotificationController.php:25 sends only `unread`, so the field is a stale type with no runtime effect.
  - Dashboard has two orphan doc comments about a removed "no family" feature (line 41 sits on top of the `setup` doc).
  - Welcome repeats the PHOTOS doc comment, and `Photo`'s `eager` prop is never passed.
- **Failure scenario:** A maintainer reads Dashboard Props and takes line 41 ("the types it can have") as the meaning of `setup`, which is wrong. Someone wiring a search into Notifications trusts a `q` the server never sends.
- **Recommended fix:** Remove the unused props, the stale type field and the orphan comments.
- **Verification note:** Read each file and the controllers. Corrected from the first draft: the "toggle drops q" claim does not hold, because the server never sends q.

## Dropped
- **Invite form defaults to the first family when a guest or admin invite is allowed** (Manage/Invitations.tsx:47). The pre-selected family is visible in a labelled select right above the submit button. Defaulting to a family is a product choice, not a defect, and the failure assumes the admin ignores a visible field.

## Checked, nothing to report
- **Hard-coded strings:** none in JSX text or label/aria/placeholder attributes in pages or layouts.
- **Locale coverage:** identical key sets with matching placeholders across es_UY, fr, it and pt_BR. The lang/<locale>/ PHP directories are present for all five locales.
- **Image alt text:** present on all images. The hero images use the caption.
- **Form labels and server errors:** forms use `field()` helpers with labels and errors.
- **Confirmations:** destructive admin actions (withdraw, decline request, rollback, start over) confirm first.
- **Raw HTML:** `dangerouslySetInnerHTML` only renders static HTML from resources/views written by the authors.
- **Data exposure:** no page receives obviously excessive props beyond 06-05 and 06-20. Spot checks: NotificationController, DocumentationController, InvitationController.
