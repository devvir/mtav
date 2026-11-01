# Frontend components, design system, lib & tours
_Review of main · scope: resources/js/components, resources/js/design, resources/js/lib, resources/js/tours, resources/js/types, resources/js/lab, resources/css_

## Summary
The shared frontend layer is strictly typed (almost no `any` or unsafe casts). All text goes through `_()` and React escaping. There is no `dangerouslySetInnerHTML` in scope (the two in `pages/` render trusted repo HTML). User-supplied URLs are checked on the server (online event links must be `url:http,https`, `app/Http/Requests/EventRequest.php:17`), and toast and notification links are app-generated. Mantine supplies most widget semantics, with correct focus handling in its modals, menus and popovers. The dnd-kit ranking list has keyboard support and announcements.

The biggest risks are on shared devices and in error paths:
- Sign-out is a client-side Inertia visit. The chat cache, chat window state and Echo subscriptions stay in memory for the next person in the same tab.
- The device's Web Push subscription stays tied to the previous user, so their chat messages keep showing on that device as system notifications.
- Chat's raw `fetch` calls have no error handling, so one network failure leaves the composer or a dialog spinning.
- The plan editor's unsaved-changes guard fires a native confirm on background partial reloads.
- Flash toasts, and the lottery's server-side "Before running" confirmation, come back on browser Back.
- The guided tour says it is `aria-modal` but neither traps nor restores focus.

## Findings
| # | Severity | Category | Title | Location |
|---|---|---|---|---|
| 07-01 | medium | security | Per-user chat cache, chat state and websocket subscriptions survive sign-out/sign-in in the same tab | resources/js/components/domain/Chat.tsx:108 |
| 07-02 | medium | security | Web Push subscription outlives sign-out: the previous person's chat messages keep appearing on the device | resources/js/lib/push.ts:22-26 |
| 07-03 | medium | correctness | Chat network failures leave the composer and dialogs stuck in a loading state | resources/js/components/domain/Chat.tsx:405-421 |
| 07-04 | medium | correctness | Plan editor's unsaved-changes guard pops a native confirm on background partial reloads | resources/js/components/domain/PlanPanel.tsx:164-172 |
| 07-05 | medium | correctness | Flash toasts and the server's lottery "Before running" confirmation replay on browser Back | resources/js/components/layout/page.ts:18-25 |
| 07-06 | medium | accessibility | Guided tour is an aria-modal dialog without a focus trap or focus restore | resources/js/components/help/TourHost.tsx:157-165 |
| 07-07 | low | accessibility | Existing plan shapes cannot be selected from the keyboard | resources/js/components/plan/PlanEditor.tsx:759-771 |
| 07-08 | low | performance | Every page, including Platform/UI Lab and the plan editor, is bundled eagerly into the entry chunk | resources/js/app.tsx:29 |
| 07-09 | low | correctness | `stopListening(event)` without a callback removes every listener on a shared channel; channels are never left | resources/js/lib/realtime.ts:61-65 |
| 07-10 | low | correctness | Plan lock failures other than 423 open the editor anyway; a lost lock only toasts | resources/js/components/domain/PlanPanel.tsx:89-94 |
| 07-11 | low | correctness | Push switch reports "on" even when the server rejected or never received the subscription | resources/js/lib/push.ts:36-63 |
| 07-12 | low | accessibility | Chat window does not manage focus, and new messages are not announced | resources/js/components/layout/ChatLauncher.tsx:51-57 |
| 07-13 | low | accessibility | Unread and done states are shown only visually | resources/js/components/layout/NotificationsMenu.tsx:112-117 |
| 07-14 | low | accessibility | Disabled buttons inside Tooltip: the reason is never shown or reachable | resources/js/components/domain/UnitsByType.tsx:74-89 |
| 07-15 | low | accessibility | Moving a ranked unit to the top or bottom with its arrow button drops keyboard focus | resources/js/components/domain/RankingList.tsx:229-244 |
| 07-16 | low | correctness | Plan editor's window-wide shortcuts hijack Ctrl+C/V/D and Backspace outside the editor | resources/js/components/plan/PlanEditor.tsx:510-541 |
| 07-17 | low | i18n | Chat shows times in the browser's locale, and the same empty-list message on every tab | resources/js/components/domain/Chat.tsx:567-569 |
| 07-18 | low | performance | UI Lab permanently twins every stateful CSS rule and leaves a MutationObserver running | resources/js/lab/pseudo.ts:77-84 |
| 07-19 | low | practices | Duplicated helpers that have drifted apart | resources/js/design/components/FileDrop.tsx:7-10 |

### 07-01. Per-user chat cache, chat state and websocket subscriptions survive sign-out/sign-in in the same tab
- **Severity / category:** medium / security
- **Location:**
  - `resources/js/components/domain/Chat.tsx:108`
  - `resources/js/components/domain/Chat.tsx:859`
  - `resources/js/lib/chat.ts:14`
  - `resources/js/components/layout/ChatLauncher.tsx:13`
  - `resources/js/lib/realtime.ts:61-65,103-107,156-160`
  - `resources/js/lib/offline.ts:34-43,68`
  - `resources/js/components/layout/AppShell.tsx:343-349`
- **Problem:** Sign-out is an Inertia `<Link method="post">` (`AppShell.tsx:343-349`). The server answers with `to_route('home')`, and sign-in likewise redirects through Inertia, so the page never reloads. Several pieces of module-level state stay in memory:
  - `seen`, the per-project conversation list plus the open conversation's messages
  - the chat store (`open`, `conversation`)
  - the `welcomed` flag
  - Echo private channels. They are only `stopListening`'d, never `leave()`'d, so the socket stays subscribed to `private-users.{A}` and `private-projects.{id}`.

  On a user change, `keepFor` (`offline.ts:34-43`) only tells the service worker to forget its pages. Nothing else is reset.
- **Failure scenario:** On a shared family computer, person A has the chat window open and signs out. Person B signs in in the same tab and enters the same project. `ChatLauncher` renders with `open: true`, and `useState(initial ?? seen.get(projectId))` immediately draws A's conversation titles, last-message bodies and A's open thread. This lasts until B's `chat.panel` fetch answers 403/404 or returns B's data; it stays longer if that fetch fails. The socket keeps receiving A's `.message` events (conversation id, author first name). No handler runs for them, but they reach B's browser. B also never gets the first-arrival Assistant welcome in that tab, because `welcomed` is already true.
- **Recommended fix:** When the signed-in user id changes (the same `navigate` hook that calls `keepFor`):
  - clear `seen`
  - reset the chat store and `welcomed`
  - leave all Echo channels, or disconnect Echo and configure it again.

  Simpler: end sign-out with a full page load (`Inertia::location` / `window.location`).
- **Verification note:** Traced the logout Link and `AuthenticatedSessionController::destroy` (redirect, not location), the module-level `seen`/`state`/`welcomed`, and the realtime cleanups that never call `leave()`.

### 07-02. Web Push subscription outlives sign-out: the previous person's chat messages keep appearing on the device
- **Severity / category:** medium / security
- **Location:**
  - `resources/js/lib/push.ts:22-26`
  - `resources/js/components/account/DeviceNotifications.tsx:15-17,39`
  - `app/Http/Controllers/Auth/AuthenticatedSessionController.php:29-36`
  - `app/Http/Controllers/PushSubscriptionController.php:23-27`
  - `app/Services/Push/PushService.php:55-70`
- **Problem:** Sign-out neither unsubscribes the browser nor deletes the server's `push_subscriptions` row, so the device stays registered to user A. `pushState()` reads only the browser's subscription, so the switch shows "on" for whoever is signed in next. The server reassigns the endpoint only when someone toggles it on again (`store`). `destroy` deletes only rows of the current user, so B toggling it off leaves A's row in place until the push service rejects the dead endpoint.
- **Failure scenario:** A turns on "Notifications on this device" on the family tablet, then signs out. For as long as the browser keeps the subscription, every chat message to A arrives as a system notification on that tablet, with the author's name and up to 140 characters of the body (`PushService.php:62-63`), along with A's project notifications. B signs in and sees the switch already on, so B never re-subscribes. B gets none of their own pushes while A's private messages keep appearing.
- **Recommended fix:** Before the logout request, call `turnOff()` (unsubscribe the browser and delete the row), or delete the endpoint's row server-side on `Logout`. Make `pushState()` confirm with the server that the endpoint belongs to the current user.
- **Verification note:** Read the push lib, the switch component, the subscription controller, the logout controller and PushService delivery. No sign-out hook touches push.

### 07-03. Chat network failures leave the composer and dialogs stuck in a loading state
- **Severity / category:** medium / correctness
- **Location:**
  - `resources/js/components/domain/Chat.tsx:405-421` (send)
  - `resources/js/components/domain/Chat.tsx:673-682` (rename)
  - `resources/js/components/domain/Chat.tsx:723-739` (add people)
  - `resources/js/components/domain/Chat.tsx:786-805` (new conversation)
  - `resources/js/components/domain/Chat.tsx:173-177,434-458` (pin, delete, leave)
  - `resources/js/lib/json.ts:9-26`
- **Problem:** `sendJson` is a bare `fetch`, which rejects on network errors. None of these async handlers has try/catch/finally, and they are called with `void`. So `setSending(false)` and `setSaving(false)` never run, and the rejection goes unhandled. `heldOffline()` only checks a store that changes on service-worker messages, the `offline` event or Inertia `networkError`, never on a failed raw `fetch`.
- **Failure scenario:** On a flaky mobile connection, the user presses Send and the POST rejects. `sending` stays `true`, the send button spins forever, and every later Enter returns early at `if (... sending ...) return`. The thread cannot send until it remounts (switching conversation or minimizing). The Rename, Add people and New conversation buttons spin the same way. Pin, delete and leave fail silently, without the "That did not work" toast.
- **Recommended fix:** Wrap each request in try/catch/finally: reset the busy flag in `finally` and call `refused()` in `catch`. Better: have one helper that turns a rejection into a failed `Response`-like result, so every caller handles a failure the same way.
- **Verification note:** Read every `sendJson` caller in Chat.tsx and `sendJson` itself. No catch exists on any path.

### 07-04. Plan editor's unsaved-changes guard pops a native confirm on background partial reloads
- **Severity / category:** medium / correctness
- **Location:**
  - `resources/js/components/domain/PlanPanel.tsx:164-172`
  - `resources/js/components/domain/PlanPanel.tsx:231`
  - `resources/js/lib/realtime.ts:54,84,130`
- **Problem:** The `router.on('before')` guard prompts on every GET visit while `editor.dirty()` is true. That includes `router.reload({ only: [...] })` partial reloads, which never leave the page. Each of these issues one:
  - `useLiveMessages`, on every incoming chat message
  - `useLiveNotifications`, on every notification
  - `useProjectChanges`, on every project change
  - `onUnitAdded`.
- **Failure scenario:** An admin drags a shape, and the editor stays dirty for the autosave delay (or indefinitely while offline or while saves fail). A teammate sends a chat message, and a blocking `window.confirm("Leave without saving the plan?")` appears although nobody is leaving. Choosing Cancel drops the reload, so the unread counters and the notification toast go stale. Choosing OK changes nothing either, since the page never leaves.
- **Recommended fix:** Guard only visits that change page: skip visits with `only`/`except` (partial reloads) and visits whose `url.pathname` equals `location.pathname`.
- **Verification note:** Confirmed that all three realtime hooks and `onUnitAdded` call `router.reload`, a GET visit that fires `before`. `model.dirty` stays true until `markSaved`.

### 07-05. Flash toasts and the server's lottery "Before running" confirmation replay on browser Back
- **Severity / category:** medium / correctness
- **Location:**
  - `resources/js/components/layout/page.ts:18-25`
  - `resources/js/components/domain/LotteryAdminTools.tsx:110-125`
  - `app/Http/Middleware/HandleInertiaRequests.php:49-54`
  - `app/Http/Controllers/Member/LotteryController.php:77`
- **Problem:** Flash messages and the lottery `confirm` are ordinary page props, read from the session. Inertia stores the page object in `history.state`, and on popstate it restores it with fresh object identities and remounted pages. `useEffect(..., [flash])` then toasts again, and LotteryAdminTools' effect fires again on mount while `confirm` is non-null.
- **Failure scenario:** An admin saves a family and sees "Saved", goes to People, then presses Back: "Saved" toasts again. On the lottery page, the server refused a run with `confirm` (for example, more units than families), and the admin navigated away without answering. Pressing Back reopens the "Before running … Run anyway" dialog. One click there posts `manage.lottery.run`, skipping the type-RUN confirmation of the normal path.
- **Recommended fix:** Use Inertia's flash channel, which is not persisted to history, for `flash` and `confirm`. Otherwise give each flash a one-time id and skip ids already shown.
- **Verification note:** Traced the props from middleware and controller to the effects keyed on prop identity. The popstate restore is Inertia's documented history behaviour; it could not be run here (no node_modules).

### 07-06. Guided tour is an aria-modal dialog without a focus trap or focus restore
- **Severity / category:** medium / accessibility
- **Location:**
  - `resources/js/components/help/TourHost.tsx:57-61`
  - `resources/js/components/help/TourHost.tsx:114`
  - `resources/js/components/help/TourHost.tsx:157-165`
  - `resources/js/components/help/TourHost.tsx:221`
- **Problem:**
  - The step card has `role="dialog"` and `aria-modal="true"` and is focused when a step starts, but nothing keeps focus inside it.
  - When the tour ends, focus is never restored.
  - The page behind is click-blocked by an overlay but stays in the tab order.
  - `TourOffer` is a `role="dialog"` that never receives focus.

  Tours start by themselves on a page's first visit (`autoTour`), so every new user meets this.
- **Failure scenario:** A keyboard user lands on a page and the tour opens. Tab past "Next" moves focus into the dimmed page, which the overlay blocks for the mouse but which screen readers treat as outside a modal. Esc or Done unmounts the card, and focus falls to `<body>`, so the user starts again from the top of the document.
- **Recommended fix:** Wrap the card in Mantine's `FocusTrap`. Save `document.activeElement` when a tour starts and restore it in `stopTour`. Focus `TourOffer` when it appears, or make it a non-modal `role="status"` region.
- **Verification note:** Read TourHost and the tour lib exports. There is no FocusTrap, `inert` or activeElement handling.

### 07-07. Existing plan shapes cannot be selected from the keyboard
- **Severity / category:** low (reduced from medium) / accessibility
- **Location:**
  - `resources/js/components/plan/PlanEditor.tsx:759-771`
  - `resources/js/components/plan/PlanEditor.tsx:304-307`
  - `resources/js/components/plan/PlanEditor.tsx:528-548`
- **Problem:** Each shape is a `<g>` that is selected only from `startMove` (pointerdown) or `onContextMenu`. The shapes have no `tabIndex`, key handler or accessible name, and nothing else calls `model.select(key)`. All keyboard commands (arrows, R, Delete, PageUp/Down, Ctrl+D) act only on the current selection.
- **Failure scenario:** A keyboard-only admin can add a shape from the palette (it gets selected) and nudge it. Once Escape deselects it, no existing street, green area or unit shape can be reached again to move, rotate, change floor or delete. The keyboard shortcuts work only on what a pointer selected.
- **Recommended fix:** Make shapes focusable (`tabIndex=0`, `aria-label` from the unit identifier, name or kind) and select them on focus. Alternatively, add a keyboard-navigable list of shapes in the details card.
- **Verification note:** Grepped every `model.select(` call: all are pointer paths, plus Escape and the boundary toggle. Severity lowered because PlanPanel deliberately limits drawing to `(pointer: fine)` large screens, and unit data stays editable outside the plan.

### 07-08. Every page, including Platform/UI Lab and the plan editor, is bundled eagerly into the entry chunk
- **Severity / category:** low (reduced from medium) / performance
- **Location:** `resources/js/app.tsx:29`, `resources/js/app.tsx:43-45`, `resources/js/pages/Platform/Lab.tsx:3`
- **Problem:** `import.meta.glob('./pages/**/*.tsx', { eager: true })` puts every page, and everything those pages import, into the entry chunk. That includes:
  - the Platform console (about 46 KB of source)
  - the UI Lab (about 26 KB, plus `lab.css`)
  - the plan editor and renderer (about 107 KB of source).

  `lab.css` is imported by Lab.tsx, so it lands in the global stylesheet.
- **Failure scenario:** A guest on the landing page, or a member on a phone, downloads and parses superadmin-only and admin-only code before first render (the render also waits for `loadLocale`).
- **Recommended fix:** Keep member pages eager (offline support relies on them being cached). Load `Platform/*`, including the Lab, lazily through a non-eager glob for that prefix, and load `PlanEditor` with `React.lazy`.
- **Verification note:** The code comment at `app.tsx:43-44` says this is a deliberate choice ("it is small"). Severity lowered because the measured superadmin/admin-only share is modest and members benefit offline from the eager pages.

### 07-09. `stopListening(event)` without a callback removes every listener on a shared channel; channels are never left
- **Severity / category:** low / correctness
- **Location:**
  - `resources/js/lib/realtime.ts:61-65,103-107,156-160`
  - `resources/js/pages/Member/Lottery.tsx:82`
  - `resources/js/components/domain/LotteryAdminTools.tsx:79`
- **Problem:** Echo's `stopListening('.changed')` with no callback unbinds every handler for that event on the cached channel. The Lottery page and its child LotteryAdminTools both call `useProjectChanges`, so both bind `.changed` on `projects.{id}`, and either cleanup removes both. Each live change also sends two reload requests, one per listener.
- **Failure scenario:** While the lottery page is open, an admin's role is removed. The live reload returns `admin: null`, LotteryAdminTools unmounts, and its cleanup removes the page's own `.changed` handler. From then on, status, pace and results stop updating live, which matters most during a run.
- **Recommended fix:** Pass the bound handler to `stopListening(event, handler)`. Reference-count subscriptions per channel and `leave()` when the last one goes.
- **Verification note:** Both components call `useProjectChanges` on the same page. `echo().private(name)` returns the cached channel object.

### 07-10. Plan lock failures other than 423 open the editor anyway; a lost lock only toasts
- **Severity / category:** low / correctness
- **Location:** `resources/js/components/domain/PlanPanel.tsx:89-94`, `resources/js/components/domain/PlanPanel.tsx:154-160`, `resources/js/components/domain/PlanPanel.tsx:84`
- **Problem:** `start()` opens the editor unless the status is 423, so a 419 (expired session or CSRF), 403 or 500 still opens it. When the lock is lost, renewal and saves only toast on 423, and editing continues.
- **Failure scenario:** After a long idle period the session has expired. The admin clicks "Edit the plan", the lock POST returns 419, and the editor opens without a lock. Every autosave fails, and the admin draws for minutes. If another admin takes the lock instead, the first one gets a "{name} is drawing the plan" toast every 30 s and on every 5 s save retry, while still able to draw.
- **Recommended fix:** Require `response.ok` before `setEditing`, and show the server's reason otherwise. When renewal returns 423, flush what can be saved and leave edit mode with one clear message.
- **Verification note:** `PlanController::lock` returns 200 or 423. Any other status comes from middleware, and `start()` does not check `response.ok`.

### 07-11. Push switch reports "on" even when the server rejected or never received the subscription
- **Severity / category:** low / correctness
- **Location:** `resources/js/lib/push.ts:36-48`, `resources/js/lib/push.ts:51-63`, `resources/js/components/account/DeviceNotifications.tsx:21-25`
- **Problem:** `tell()` resolves in `onFinish`, which runs on success, validation error and network error alike, so `turnOn` always returns `'on'` once the browser has subscribed. After a reload, `pushState()` still reads only the browser.
- **Failure scenario:** The subscribe POST fails validation (for example, an endpoint longer than 700 characters or a non-https one), or the network drops. In the offline case, `offline.ts:61-64` swallows the `networkError`. The switch stays on, the server has no row, and no notification ever arrives, with no hint why.
- **Recommended fix:** Resolve `tell()` with success or failure from `onSuccess`/`onError`. On failure, unsubscribe the browser, return `'off'` and show an error.
- **Verification note:** Read `tell`/`turnOn` and the controller's validation rules.

### 07-12. Chat window does not manage focus, and new messages are not announced
- **Severity / category:** low / accessibility
- **Location:**
  - `resources/js/components/layout/ChatLauncher.tsx:51-57`
  - `resources/js/components/domain/Chat.tsx:273-279`
  - `resources/js/components/domain/Chat.tsx:521-528`
- **Problem:**
  - Opening the window leaves focus on the launcher, which comes after the window in the DOM.
  - Minimize (`keepMounted={false}`) unmounts the window while its button has focus.
  - `.chat-messages` has no `role="log"` and no live region.
- **Failure scenario:** A keyboard user opens the chat and has to Shift+Tab backwards to reach it. After pressing Minimize inside it, focus drops to `<body>`. A screen-reader user waiting on the Assistant is never told that a reply arrived.
- **Recommended fix:** On open, focus the composer or the list. On minimize, return focus to the launcher. Give `.chat-messages` `role="log"`.
- **Verification note:** Read the launcher and the Thread markup. There is no focus call or live region.

### 07-13. Unread and done states are shown only visually
- **Severity / category:** low / accessibility
- **Location:**
  - `resources/js/components/layout/NotificationsMenu.tsx:112-117`
  - `resources/js/components/domain/Chat.tsx:253`
  - `resources/js/components/domain/SetupChecklist.tsx:15-25,53-61`
- **Problem:**
  - A notification's unread state is a `data-unread` attribute and an `aria-hidden` dot.
  - The chat's unread mark is an empty `<span aria-label="Unread">`. A label on a generic element with no role is ignored by most screen readers.
  - A checklist step's done state is an `aria-hidden` SVG tick plus `data-done`. Only the collapsed trigger gives a count.
- **Failure scenario:** A screen-reader user opening the bell hears every notification read the same way. The chat list never says "unread". The expanded checklist reads the five steps without saying which are done.
- **Recommended fix:** Add `VisuallyHidden` text ("Unread", "Done" / "To do") inside each item.
- **Verification note:** Read the three components. No visually hidden text exists.

### 07-14. Disabled buttons inside Tooltip: the reason is never shown or reachable
- **Severity / category:** low / accessibility
- **Location:** `resources/js/components/domain/UnitsByType.tsx:74-89`, `resources/js/components/domain/FamilyDetails.tsx:169-184`
- **Problem:** The tooltips explaining why Remove is unavailable ("In use by units or families", "It already received a unit") wrap natively `disabled` buttons. Disabled buttons fire no pointer events and cannot take focus, so the tooltip never opens. Mantine documents this limitation.
- **Failure scenario:** An admin hovers or tabs to the greyed-out trash icon of a unit type in use. No explanation appears, and keyboard users cannot reach the control at all.
- **Recommended fix:** Use `data-disabled` with `aria-disabled` and a no-op click, or show the reason as caption text.
- **Verification note:** Both sites pass `disabled` to Button/ActionIcon directly inside `Tooltip`.

### 07-15. Moving a ranked unit to the top or bottom with its arrow button drops keyboard focus
- **Severity / category:** low / accessibility
- **Location:** `resources/js/components/domain/RankingList.tsx:229-244`
- **Problem:** The up and down ActionIcons get `disabled` at the list's edges. When a move makes the focused button disabled, it loses focus to `<body>`.
- **Failure scenario:** A keyboard user presses "Move A-101 up" until A-101 is first. The button disables while focused, the screen reader loses its place, and the user has to find the row again.
- **Recommended fix:** Use `aria-disabled` and keep the button focusable, or move focus to the opposite arrow.
- **Verification note:** Read the row's ActionIcons: `disabled={index === 0}` and `disabled={last}`.

### 07-16. Plan editor's window-wide shortcuts hijack Ctrl+C/V/D and Backspace outside the editor
- **Severity / category:** low / correctness
- **Location:** `resources/js/components/plan/PlanEditor.tsx:510-526`, `resources/js/components/plan/PlanEditor.tsx:536-541`
- **Problem:** The keydown listener is on `window` and skips only input, textarea and select. While the editor is mounted, it calls `preventDefault` for Ctrl/Cmd+C, V and D everywhere on the page, and for Backspace/Delete whenever a shape is selected.
- **Failure scenario:** While editing the plan, an admin selects text in a toast or the help caption and presses Ctrl+C. Nothing reaches the clipboard; a shape is copied instead. In the open "Add a unit" modal, Backspace pressed while a button has focus deletes the non-unit shape still selected behind it.
- **Recommended fix:** Act only when `frame.current.contains(event.target)`. Let Ctrl+C through when `getSelection()` is non-empty.
- **Verification note:** Read the handler: its only target filter is `closest('input, textarea, select')`.

### 07-17. Chat shows times in the browser's locale, and the same empty-list message on every tab
- **Severity / category:** low / i18n
- **Location:** `resources/js/components/domain/Chat.tsx:567-569`, `resources/js/components/domain/Chat.tsx:221-225`
- **Problem:** Message times use `new Intl.DateTimeFormat(undefined, …)`, not `languageTag()` as everywhere else (`DateTime.tsx:14`), and a new formatter is created per message per render. The empty state always says "No one-on-one chats yet.".
- **Failure scenario:** A Spanish-speaking user in an English browser sees "Hoy" headings next to "6:30 PM". The Groups tab with no groups, or a search with no match, says "No one-on-one chats yet.".
- **Recommended fix:** Use one memoized formatter with `languageTag()`. Pick the empty message by tab and by whether a search is active.
- **Verification note:** Grepped `DateTimeFormat(`: this is the only call that uses `undefined`.

### 07-18. UI Lab permanently twins every stateful CSS rule and leaves a MutationObserver running
- **Severity / category:** low / performance
- **Location:** `resources/js/lab/pseudo.ts:77-84`, `resources/js/pages/Platform/Lab.tsx:27`
- **Problem:** `holdStates()` inserts, into every loaded stylesheet, a twin of each `:hover`/`:active`/`:focus*` rule using `:is(.ps-x, :has(.ps-x))` selectors. It also starts a `<head>` MutationObserver that is never disconnected. The effect returns `undefined`, so there is no cleanup, and Inertia navigation never reloads the page.
- **Failure scenario:** A superadmin opens the Lab once and keeps working in the app. For the rest of the session, every stylesheet carries the extra `:has()` rules. `:has()` selectors make browser style invalidation more expensive on every DOM change. The observer also keeps running on every `<head>` mutation, though it only scans sheets it has not seen yet, thanks to the `done` WeakSet.
- **Recommended fix:** Return a cleanup from `holdStates` that disconnects the observer and deletes the inserted rules, and return it from the effect. Alternatively, put the twins in one dedicated `<style>` element that is removed on unmount.
- **Verification note:** Read pseudo.ts. The original claim of a "full rescan" on each mutation was corrected: already-processed sheets are skipped.

### 07-19. Duplicated helpers that have drifted apart
- **Severity / category:** low / practices
- **Location:**
  - `resources/js/design/components/FileDrop.tsx:7-10`
  - `resources/js/components/domain/FileList.tsx:21-24,31-34`
  - `resources/js/components/domain/EventCard.tsx:12`
  - `resources/js/components/domain/MediaGrid.tsx:9`
  - `resources/js/components/forms/EventForm.tsx:21`
  - `resources/js/components/domain/LotteryAdminTools.tsx:49`
  - `resources/js/components/feedback/QuerySearch.tsx:26-36`
  - `resources/js/components/domain/Archive.tsx:26-36`
  - `resources/js/lib/listFilters.ts:4-14`
- **Problem:** The same helpers exist in several copies:
  - two byte-size formatters with different thresholds (FileDrop uses `>`, FileList uses `>=`)
  - three copies of `detailsQuery`
  - two copies of the ISO-to-local `local()` helper
  - two hand-rolled copies of `setFilter` (QuerySearch, ArchiveSwitch).
- **Failure scenario:** A 1,048,576-byte file shows as "1024 KB" in the upload dropzone but "1 MB" in the file list. Any change to the filter visit (for example, adding `only`) must be made in three places.
- **Recommended fix:** Keep one `fileSize` in `lib/app.ts`, one `detailsQuery(param, id)` and one `toLocalInput(iso)` in lib, and have QuerySearch and ArchiveSwitch call `setFilter`.
- **Verification note:** Compared the copies side by side. The original claim of three byte formatters was corrected: `lib/app.ts`'s `megabytes` takes MB, not bytes.

## Checked, nothing to report
- **XSS:** no `dangerouslySetInnerHTML` or `innerHTML` in scope. Toast and notification hrefs are server-generated. Online event links are validated `url:http,https` before they are rendered in `<Anchor target="_blank" rel="noopener">`.
- **TypeScript:** essentially no `any`. The one notable cast (`as never` in `lib/dialogs.ts:52`) is harmless in practice. `DialogHost` computes each answer from the request of the question that is showing, and the old question unmounts as soon as the queue moves.
- **Keys and lists:** stable ids are used throughout. No index keys were found on reorderable lists.
- **Mantine overlays:** Modal, Menu and Popover give correct focus trap and restore, and `data-autofocus` is used in the dialogs.
- **Dropped:**
  - "settle() answers the wrong question": the value type always comes from the question shown, so `{ reason: true }` cannot happen.
  - "No tests for the riskiest components": `tests/Browser/Features/ChatTest.php`, `PlanTest.php`, `GuidedHelpTest.php` and `AccessibilityTest.php` cover these flows end to end. Unit tests for the error paths above would still be worth adding alongside the fixes.
