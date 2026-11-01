# Forum, chat, media, notifications, push & assistant
_Review of main · scope: ForumService, ChatService, MediaService, MediaThumbnailService, NotificationService, Services/Push, Services/Assistant, Events, Jobs, Listeners, NotificationController, PushSubscriptionController, Member chat/forum/media controllers, routes/channels.php, config/filesystems.php, config/reverb.php, deploy worker/php.ini_

## Summary
The fundamentals of this area are solid. Every project route sits behind `ResolveProject` plus `scopeBindings()` (routes/web.php:134-136), and `Project` has the `media`, `topics`, `posts`, `conversations` and `messages` relations those bindings need. Uploads are checked by content (finfo `mimetypes:`) against an allow-list with no SVG, checked for pixel count, stored under UUID names on a private `local` disk (storage/app/media), and served with a fixed `Content-Type` and `nosniff`. Non-media types are downloaded as attachments. User content reaches the page only as React text: there is no `dangerouslySetInnerHTML` in chat, forum or media. Broadcasts carry IDs only, and channel authorisation is per user or per project. Assistant links are rebuilt from a server-side list of places, and user-typed fields in the prompt are sanitised.

The real risks are abuse and scale controls, not authorisation holes:
- Archived media is not counted against the storage quota.
- Forum replies keep notifying, and pushing to, people who have left the project.
- One malformed push subscription can stop pushes for a whole project.
- One user can put the Assistant to rest for the whole deployment.
- A single queue worker carries realtime broadcasts behind lottery runs and Assistant calls.
- Each Community message makes every online member's browser reload.

There is also a user-visible chat bug: adding people to an unnamed group saves the viewer's generated title as the group's permanent name. Note: vendor/ is not installed, so statements about third-party behaviour (minishlink/web-push, the Laravel queue worker) come from knowledge of those libraries and are marked PLAUSIBLE.

## Findings

| # | Severity | Category | Title | Location |
|---|---|---|---|---|
| 04-01 | medium | security | Storage quota bypassed through archived media (files stay on disk, not counted) | app/Services/MediaService.php:154 |
| 04-02 | medium | security | Forum reply notifications and pushes reach people no longer in the project | app/Services/ForumService.php:49 |
| 04-03 | medium | correctness | A push subscription with malformed keys can stop push delivery for a whole project | app/Http/Controllers/PushSubscriptionController.php:17-21 |
| 04-04 | medium | security | One user can put the Assistant to rest for the whole deployment, at no cost to their quota | app/Services/Assistant/Assistant.php:80-87 |
| 04-05 | medium | correctness | Adding people to (or renaming) an unnamed group saves the viewer's generated name as its title | resources/js/components/domain/Chat.tsx:727-730 |
| 04-06 | medium | performance | Realtime broadcasts and pushes share one queue worker with 600s lottery runs and 120s Assistant calls | deploy/slot.yml:56 |
| 04-07 | medium | performance | Every Community message makes every online member reload project props (and the chat panel when open) | resources/js/lib/realtime.ts:127-130 |
| 04-08 | medium | accessibility | Chat thread is not announced to screen readers and does not say who wrote what | resources/js/components/domain/Chat.tsx:521-590 |
| 04-09 | low | performance | Thumbnailing and ffprobe/ffmpeg run synchronously inside the upload request; the batch is not atomic | app/Services/MediaService.php:90-95 |
| 04-10 | low | performance | Forum reply fan-out runs synchronously in the request, three writes per participant | app/Services/ForumService.php:58-69 |
| 04-11 | low | security | Push endpoint accepts any https URL, so the worker POSTs to attacker-chosen hosts (blind SSRF) | app/Http/Controllers/PushSubscriptionController.php:18 |
| 04-12 | low | correctness | Superseded questions still count against the person's daily Assistant quota | app/Services/Assistant/Assistant.php:49-51 |
| 04-13 | low | performance | Assistant's deployment-wide daily count scans all of today's messages without an index | app/Services/Assistant/Assistant.php:150-160 |
| 04-14 | low | i18n | The Assistant changes the queue worker's locale and never restores it | app/Services/Assistant/Assistant.php:46 |
| 04-15 | low | correctness | An unsent chat message stays visible in others' open windows | app/Services/ChatService.php:126-132 |
| 04-16 | low | correctness | Push title for unnamed groups ends with a dangling separator | app/Services/Push/PushService.php:62 |
| 04-17 | low | correctness | Turning the gallery off does not stop its photos and videos being served | app/Http/Controllers/Member/MediaFileController.php:17-38 |
| 04-18 | low | correctness | Notification list starts at account creation, not at joining the project; read-all is one unbounded insert | app/Models/Notification.php:79 |
| 04-19 | low | correctness | Search query given as an array crashes forum and media list pages | app/Http/Controllers/Member/ForumController.php:32 |
| 04-20 | low | correctness | Two simultaneous requests to open the same 1:1 chat make one of them a 500 | app/Services/ChatService.php:35-59 |
| 04-21 | low | tests | Risky paths in this area lack tests | tests/UseCases/Membership/ProjectIsolationTest.php:43-54 |

### 04-01. Storage quota bypassed through archived media (files stay on disk, not counted)
- **Severity / category:** medium / security
- **Location:** `app/Services/MediaService.php:154` (also `app/Services/MediaService.php:97-108`, `app/Models/Concerns/Archivable.php:33-36`, `app/Policies/MediaPolicy.php:32-35`, `app/Services/ArchiveService.php:50-56`)
- **Problem:**
  - Deleting media more than `MISTAKE_MINUTES` (60) after upload only soft-deletes the row, and the file and thumbnail stay on disk.
  - `ensureStorageAvailable` sums `$project->media()`, and SoftDeletes leaves trashed rows out of that sum. Nothing purges archived media (there is no `Prunable` and no scheduled cleanup).
  - Restoring from the archive (`ArchiveService::restore`) skips the quota check, so a project can end up over its limit.
  - Thumbnails are never counted.
  - The check-then-store sequence is not atomic, so parallel uploads can each pass the check.
- **Failure scenario:** Any member of an unapproved project (500 MB limit, config/limits.php:13) uploads 500 MB, waits an hour, and deletes their own files, which the owner is allowed to do. The quota then shows 0 used, so they upload another 500 MB, and repeat. Disk use for the project grows without bound while the quota meter stays at or below 500 MB.
- **Recommended fix:** Count trashed media in the quota (`media()->withTrashed()->sum('file_size')`), or purge archived files after a retention period and count them until then. Check the quota on restore. Serialise check and insert per project (a lock), or re-check after storing and roll back.
- **Verification note:** Read MediaService::delete/ensureStorageAvailable, Archivable::isFresh, and the Media model (uses Archivable, which uses SoftDeletes). Grepped app/ and routes/console.php for prune and purge logic and found none.

### 04-02. Forum reply notifications and pushes reach people no longer in the project
- **Severity / category:** medium / security (information exposure)
- **Location:** `app/Services/ForumService.php:49` (also `app/Services/ForumService.php:58-69`, `app/Models/Topic.php:57-60`, `app/Models/Notification.php:71-73`, `app/Services/Push/PushService.php:80`)
- **Problem:** The audience for a reply is `Topic::participants()`, meaning every user who ever posted in the topic, with no membership filter. Each one gets a PRIVATE notification. `Notification::scopeFor` lists PRIVATE notifications by `target_id` alone, and `PushService::recipients` pushes PRIVATE notifications to `User::whereKey($id)` without checking that the user still belongs to the project.
- **Failure scenario:** A member posts in a topic and is then removed from the project, or leaves it. Every later reply still drops a notification in their bell and sends a push to their phone: "Ana replied in "<topic title>"". They keep learning who is active and what is being discussed in a project they can no longer open. Clicking the notification gives a 404 from ResolveProject.
- **Recommended fix:** Limit participants to current members, for example `->whereHas('memberships', fn ($q) => $q->where('project_id', $topic->project_id))` in `ForumService::reply`. Add a test that a removed member receives nothing.
- **Verification note:** Traced reply, then participants (no membership join), then NotificationService::send(PRIVATE), then scopeFor and PushService::recipients; none of them filter by project.

### 04-03. A push subscription with malformed keys can stop push delivery for a whole project
- **Severity / category:** medium / correctness (availability)
- **Location:** `app/Http/Controllers/PushSubscriptionController.php:17-21` (also `app/Services/Push/PushService.php:30-40`, `app/Services/Push/WebPushSender.php:18-35`, `app/Jobs/PushNotification.php:15-24`)
- **Problem:**
  - `keys.p256dh` and `keys.auth` are only validated as strings of at most 255 characters.
  - WebPushSender calls `sendOneNotification`, which encrypts the payload while flushing (`prepare`, then `Encryption::encrypt`). There, an undecodable key, or one that is not a 65-byte uncompressed P-256 point, throws instead of being returned as a failed report.
  - Nothing in `PushService::deliver` catches the exception, so the loop over recipients stops and the job fails. `$tries = 3` re-runs the whole loop from the start.
- **Failure scenario:** A member POSTs `/push-subscriptions` with a valid-looking https endpoint and `p256dh: "x"`. From then on, every PROJECT notification job throws when it reaches that user. Recipients after them never get pushes, and recipients before them get the same push three times, once per try. Group chats that include the user are affected the same way through PushMessage. (PLAUSIBLE: library behaviour is inferred because vendor/ is not installed.)
- **Recommended fix:** Validate the keys: both are base64url, p256dh decodes to 65 bytes starting 0x04, and auth decodes to 16 bytes. Wrap each `sender->send` in try/catch that reports the error and deletes the bad subscription. Consider one job per recipient, so retries do not re-send to people who already got the push.
- **Verification note:** Read the validation rules, the WebPushSender call, and the PushService loops (no try/catch). The library's throwing path is from knowledge of minishlink/web-push v8 to v10.

### 04-04. One user can put the Assistant to rest for the whole deployment, at no cost to their quota
- **Severity / category:** medium / security (availability abuse)
- **Location:** `app/Services/Assistant/Assistant.php:80-87` (also `app/Services/Assistant/Assistant.php:119-126`, `app/Services/Assistant/Brains/ApiBrain.php:31-37`, `config/assistant.php:24`)
- **Problem:**
  - Any empty or unparsable answer counts as a "stumble" on one global cache counter. Three stumbles within 10 minutes set `assistant.resting` for everyone for 15 minutes.
  - The failed question is marked `free`, so it does not count against the asker's daily limit.
  - ApiBrain returns `json_decode($text)`, which is null for structured output cut off at `max_tokens` (700), and null when there is no text block (for example a refusal). So the asker's own input can cause a stumble, not only a provider outage.
  - Any successful answer resets the counter, so the three stumbles must come with no success in between.
- **Failure scenario:** On a quiet deployment, a member of any project, approved or not, sends three questions in 10 minutes that push the model past 700 tokens (or into a refusal). Each one stumbles and costs them nothing. Every user on the deployment then gets "resting" replies for 15 minutes, and the member can repeat this indefinitely. (PLAUSIBLE: depends on the model truncating or refusing, which the 700-token cap makes easy.)
- **Recommended fix:** Count only failures that point to the provider (exceptions, 5xx or 429, timeouts) on the global counter. Treat content problems (`stop_reason` of `max_tokens` or `refusal`, invalid JSON) as per-user failures that do not trigger the global rest, and charge them to the user's daily quota. Inspect `stop_reason` in ApiBrain.
- **Verification note:** Read Assistant::answer, stumbled() and free(), ApiBrain::ask, and config/assistant.php (max_tokens 700).

### 04-05. Adding people to (or renaming) an unnamed group saves the viewer's generated name as its title
- **Severity / category:** medium / correctness
- **Location:** `resources/js/components/domain/Chat.tsx:727-730` (also `resources/js/components/domain/Chat.tsx:670`, `app/Http/Controllers/Member/ChatController.php:207-211`, `app/Services/ChatService.php:97-108`)
- **Problem:**
  - The client's `conversation.title` is the display title built by `ChatController::summary`. For an unnamed group, that is the other participants' first names as seen by the viewer.
  - `AddPeople` always PUTs `title: conversation.title`. `RenameGroup` is prefilled with the same value, so saving without editing stores it too.
  - `ChatService::update` stores whatever title it receives, and it does so before `ensureRoomForAGroup` can throw, outside any transaction.
- **Failure scenario:** Ana, Beto and Carla share an unnamed group. Ana adds Dora, and the request carries `title: "Beto, Carla"`, which is stored. Everyone now sees a group named "Beto, Carla": the name leaves out Ana and Dora and no longer follows membership changes. If Dora is at the group limit, the 422 still leaves the title overwritten.
- **Recommended fix:** Expose the raw stored name (nullable) separately from the display title, and leave `title` out of the AddPeople request. On the server, update the title only when the key is present (`$request->has('title')`). Run the people check first and wrap both updates in one transaction.
- **Verification note:** Read Chat.tsx RenameGroup/AddPeople, ChatController::update/summary, and ChatService::update. The existing test (tests/UseCases/Community/ChatTest.php:114-130) always sends an explicit title.

### 04-06. Realtime broadcasts and pushes share one queue worker with 600s lottery runs and 120s Assistant calls
- **Severity / category:** medium / performance
- **Location:** `deploy/slot.yml:56` (also `app/Events/MessageSent.php:15`, `app/Events/NotificationSent.php:63`, `app/Jobs/RunLottery.php:24-30`, `app/Jobs/AnswerInChat.php:18-19`)
- **Problem:**
  - MessageSent, NotificationSent and ProjectChanged implement `ShouldBroadcast`, so they are queued on the default queue.
  - Production runs exactly one `queue:work` process on the default (database) queue. The same process also runs RunLottery (timeout 600s), AnswerInChat (timeout 120s, external API), PushNotification/PushMessage and the queued mail.
  - No job sets `onQueue`.
- **Failure scenario:** An admin starts a lottery run that takes several minutes to solve. Meanwhile chat messages are saved but nobody sees them arrive: no websocket events, no pushes, no Assistant answers. Likewise, one slow Assistant call (up to 120s) holds every other user's live chat behind it. This contradicts ChatService's promise that a message "reaches the others at once" (app/Services/ChatService.php:18-19).
- **Recommended fix:** Use `ShouldBroadcastNow` for the small realtime events (their dispatch is already wrapped in `rescue`), or put broadcasts and pushes on a `realtime` queue with its own worker. Move RunLottery and AnswerInChat to their own queues with separate workers.
- **Verification note:** Read the event classes, job timeouts, deploy/slot.yml (single worker service) and deploy/env.example (`QUEUE_CONNECTION=database`). Grepped app/ for `onQueue` and found nothing.

### 04-07. Every Community message makes every online member reload project props (and the chat panel when open)
- **Severity / category:** medium / performance
- **Location:** `resources/js/lib/realtime.ts:127-130` (also `app/Services/ChatService.php:74`, `resources/js/components/domain/Chat.tsx:867-898`, `app/Http/Controllers/Member/ChatController.php:142-176`, `app/Http/Middleware/HandleInertiaRequests.php:110-145`)
- **Problem:**
  - MessageSent goes to every participant's user channel, and Community includes the whole project.
  - On every event from someone else, each client calls `router.reload({ only: ['project'] })`, a full Laravel request that recomputes unread, invitation and join-request counts.
  - Each client also calls `refreshChat()`. For anyone with the chat mounted, that re-fetches `chat.panel`: every conversation, every project member (`people`), and 50 to 1000 messages of the open conversation.
- **Failure scenario:** A 200-member project has 100 people online, 30 of them with the chat open. One Community message causes about 100 project reloads plus 30 full panel loads within the same second. A lively conversation at 10 messages a minute produces more than 1,300 heavy requests a minute.
- **Recommended fix:** Put what the client needs in the event (conversation id, last-message preview) and apply it locally. Fetch only the open conversation's new messages (`?after=id`). Debounce or skip `router.reload` and recompute the unread count client-side. Load `people` lazily, when starting a conversation.
- **Verification note:** Read useLiveMessages, the Chat loader (`version` dependency triggers `load()`), and the panel's `data()`. The original estimate assumed every online client fetches the panel; it has been reduced to the clients with the chat mounted.

### 04-08. Chat thread is not announced to screen readers and does not say who wrote what
- **Severity / category:** medium / accessibility
- **Location:** `resources/js/components/domain/Chat.tsx:521-590`
- **Problem:**
  - The `.chat-messages` container has no `role="log"` and no `aria-live`. The toast is suppressed while the conversation is open (resources/js/lib/realtime.ts:134-135), so incoming messages are silent for screen-reader users.
  - The author's name is rendered only for others' messages in groups, and only on the first message of a run. In 1:1 chats, and for one's own messages, authorship is conveyed only by bubble alignment (`data-mine`).
  - The time is a bare formatted string with no date context.
- **Failure scenario:** A blind member using VoiceOver has a 1:1 conversation open. Beto's reply arrives and nothing is announced. Reading back through the thread, every bubble sounds the same, so the member cannot tell their own lines from Beto's.
- **Recommended fix:** Give the scroller `role="log" aria-live="polite" aria-relevant="additions"`. Add a visually hidden author prefix to each message ("You:", "Beto:", "Assistant:"). Wrap times in `<time dateTime=…>`.
- **Verification note:** Read the message rendering block; there is no aria-live, role="log" or visually-hidden text in Chat.tsx (grep).

### 04-09. Thumbnailing and ffprobe/ffmpeg run synchronously inside the upload request; the batch is not atomic
- **Severity / category:** low / performance (downgraded from medium)
- **Location:** `app/Services/MediaService.php:90-95` (also `app/Services/MediaService.php:122-143`, `app/Services/MediaService.php:174-177`, `app/Services/MediaThumbnailService.php:41-78`, `deploy/php.ini:24-30`)
- **Problem:**
  - For each of up to 20 files, the request stores the file, probes videos with FFProbe, then opens them again with FFMpeg to grab a frame. Images up to 50 MP are decoded with GD.
  - Each file is saved on its own. A fatal error (timeout or memory) part-way through leaves the earlier files stored with no "files added" notification, plus one orphaned file on disk that has no Media row.
  - The batch is capped at `post_max_size=105M`, which bounds the worst case, so this is downgraded to low.
- **Failure scenario:** A member shares 20 short phone clips. The request holds a FrankenPHP thread for tens of seconds. If it then hits `max_execution_time=60` or the memory limit, the user retries and the first N files end up duplicated. An orphan sits in storage/app/media/projects/{id}, outside the quota (see 04-01) and never cleaned up.
- **Recommended fix:** Store the files and Media rows in the request, then queue thumbnail and probe work per file (the UI already handles `thumbnail: null`). Delete files already stored if the batch fails part-way.
- **Verification note:** Read MediaService::store/storeOne/dimensions, MediaThumbnailService and deploy/php.ini. Thumbnail exceptions are caught, so only fatals produce partial batches.

### 04-10. Forum reply fan-out runs synchronously in the request, three writes per participant
- **Severity / category:** low / performance
- **Location:** `app/Services/ForumService.php:58-69` (also `app/Services/NotificationService.php:39-50`)
- **Problem:** For each earlier participant, `NotificationService::send` performs one `notifications` INSERT, one queued broadcast (an INSERT into `jobs`), and one PushNotification job (another INSERT) when push is enabled. All of this runs inside the reply request.
- **Failure scenario:** A long assembly thread with 150 participants: each reply performs about 450 INSERTs before redirecting and adds 300 jobs to the single worker's queue (see 04-06).
- **Recommended fix:** Move the fan-out into one queued job, or bulk-insert the notifications and send one broadcast/push job that covers all recipients.
- **Verification note:** Read ForumService::reply and NotificationService::send.

### 04-11. Push endpoint accepts any https URL, so the worker POSTs to attacker-chosen hosts (blind SSRF)
- **Severity / category:** low / security
- **Location:** `app/Http/Controllers/PushSubscriptionController.php:18` (also `app/Services/Push/WebPushSender.php:20-28`)
- **Problem:** The endpoint is validated only with `url:https`. The worker then POSTs an encrypted payload to it, from inside the deployment network, for every notification the user receives.
- **Failure scenario:** A user registers `https://10.0.0.5:8443/hook` or an internal hostname as their endpoint. Every project notification makes the worker connect there. The body is not attacker-controlled, but timing and the `Push not delivered: <reason>` entries in the error log give host and port probing, and the attacker can point traffic at third parties.
- **Recommended fix:** Allow-list the hosts of known push services (fcm.googleapis.com, updates.push.services.mozilla.com, *.push.apple.com, *.notify.windows.com, …), or reject IP literals and private or link-local addresses after resolving.
- **Verification note:** Read the validation and the sender; there is no host restriction anywhere.

### 04-12. Superseded questions still count against the person's daily Assistant quota
- **Severity / category:** low / correctness
- **Location:** `app/Services/Assistant/Assistant.php:49-51` (also `app/Services/Assistant/Assistant.php:150-159`)
- **Problem:** When a later user message exists, the earlier question returns without calling the model but is not marked `free`. `overLimit` counts every user message whose `extras` is null.
- **Failure scenario:** A member writes "hola" / "una pregunta" / "¿cómo ordeno las unidades?" as three bubbles. Only one model call is made, but 3 of their 40 daily messages are used (3 of 20 in an unapproved project), so they hit "limit reached" early.
- **Recommended fix:** Call `$this->free($question)` in that early return.
- **Verification note:** Read answer() and overLimit(); every other early return calls free().

### 04-13. Assistant's deployment-wide daily count scans all of today's messages without an index
- **Severity / category:** low / performance
- **Location:** `app/Services/Assistant/Assistant.php:150-160` (also `database/migrations/2026_10_30_000005_create_chat.php:42-50`)
- **Problem:**
  - The global count (`created_at >= today`, `whereHas(conversation.is_assistant)`, `from_assistant = false`, `extras IS NULL`) has no usable index. `messages` is indexed only on (conversation_id, id) and the `user_id` foreign key.
  - The count runs for every question that is not answered from the ready answers.
- **Failure scenario:** Once `messages` holds millions of rows across all projects' chats, every Assistant question starts with a full scan. The scan runs serially in the only worker, which adds to 04-06.
- **Recommended fix:** Add an index on `messages(created_at)`, or better, keep a daily counter in the cache or a small table. Index (user_id, created_at) for the per-person count.
- **Verification note:** Read both chat migrations (the assistant migration adds columns, not indexes).

### 04-14. The Assistant changes the queue worker's locale and never restores it
- **Severity / category:** low / i18n
- **Location:** `app/Services/Assistant/Assistant.php:46` (also `app/Jobs/RunLottery.php:57,69`, `app/Events/MessageSent.php:42`)
- **Problem:** `App::setLocale($user->preferredLocale())` runs inside the long-lived worker, and the worker does not reset the locale between jobs.
- **Failure scenario:** After answering a Spanish speaker, the same worker runs a RunLottery job that fails, and `__('lottery.execution_failed')` is stored on the run in Spanish. Queued MessageSent broadcasts also carry `author: "Asistente"` to English users' toasts. (PLAUSIBLE: worker behaviour is from framework knowledge.)
- **Recommended fix:** Wrap the answer in `App::withLocale($locale, fn () => …)`. Store translation keys, not translated text, in `lottery_runs.failure`.
- **Verification note:** Read the setLocale call and the `__()` calls in jobs and events.

### 04-15. An unsent chat message stays visible in others' open windows
- **Severity / category:** low / correctness
- **Location:** `app/Services/ChatService.php:126-132`
- **Problem:** `remove()` deletes the message and moves `last_message` back, but broadcasts nothing, so other clients never refresh. The PushMessage job, carrying the first 140 characters, has already been queued.
- **Failure scenario:** Ana sends a message to the wrong group and deletes it straight away. Beto, with the chat open, keeps seeing it in the thread and in the list preview until some other event triggers a refresh. The text is also on his lock screen.
- **Recommended fix:** Dispatch a removal event to the participants' channels. Optionally delay PushMessage briefly and skip it if the message is gone (the job already returns when `Message::find` is null).
- **Verification note:** Read ChatService::remove and ChatController::destroyMessage; neither dispatches anything.

### 04-16. Push title for unnamed groups ends with a dangling separator
- **Severity / category:** low / correctness
- **Location:** `app/Services/Push/PushService.php:62`
- **Problem:** Groups can have a null title (`ChatService::start` stores `$title ?: null`), but the push title is always `"{$author} · {$conversation->title}"`.
- **Failure scenario:** A message in an unnamed group shows on phones as "Ana · ", with no hint of which group it came from.
- **Recommended fix:** Fall back to the participants' first names, as the chat list does, or drop the separator.
- **Verification note:** Read deliverMessage and ChatService::start.

### 04-17. Turning the gallery off does not stop its photos and videos being served
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/Member/MediaFileController.php:17-38` (also `routes/web.php:202-206`, `app/Http/Middleware/EnsureFeature.php:21`)
- **Problem:** The media routes require *any one* of `gallery,files` to be on. `file()` and `thumbnail()` do not check the item's own category. Uploads do check it (app/Http/Requests/MediaRequest.php:39-43); serving does not.
- **Failure scenario:** Admins turn the gallery off to take the site photos down but keep Files on. Every photo is still downloadable at `/projects/1/media/{id}/file`, through URLs in notifications, browser history or links people have shared.
- **Recommended fix:** In MediaFileController (and `show`), return 404 when the item's category belongs to a feature that is off, using the same mapping as MediaRequest.
- **Verification note:** Read EnsureFeature (it uses `contains`, i.e. any one feature) and MediaFileController.

### 04-18. Notification list starts at account creation, not at joining the project; read-all is one unbounded insert
- **Severity / category:** low / correctness
- **Location:** `app/Models/Notification.php:79` (also `app/Http/Controllers/NotificationController.php:50-61`)
- **Problem:** `scopeFor` shows PROJECT notifications from `$user->created_at` onward, not from when the membership began. `readAll` plucks every unread ID and inserts them all in one statement.
- **Failure scenario:** A long-time user joins a second, older project and immediately sees that project's entire history as unread in their bell ("X shared 12 files", …). A user with more than about 21,800 unread notifications who presses "mark all read" exceeds the 65,535 placeholder limit (3 per row) on MariaDB and gets a 500.
- **Recommended fix:** Use the membership's `created_at` as the floor for each project's notifications. For read-all, use `INSERT … SELECT`, chunk the insert, or keep a per-user "read up to id" watermark.
- **Verification note:** Read scopeFor and readAll; the DB is MariaDB (deploy/env.example:32).

### 04-19. Search query given as an array crashes forum and media list pages
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/Member/ForumController.php:32` (also `app/Http/Controllers/Member/MediaController.php:41`, `app/Models/Media.php:79-84`)
- **Problem:** `$request->q` is used unvalidated. In the forum it is interpolated into a string ("Array to string conversion", which Laravel turns into an ErrorException). In media it is passed to `scopeSearch(string $q)`, which throws a TypeError. `%` and `_` are not escaped for LIKE.
- **Failure scenario:** `GET /projects/1/forum?q[]=x` returns a 500 and creates an error-log entry (deduplicated by fingerprint, so the owner gets one email per distinct site). Searching for "50%" matches everything that contains "50".
- **Recommended fix:** Use `$request->string('q')->toString()` (or validate `q` as a nullable string) and escape LIKE wildcards.
- **Verification note:** Read both controllers and Media::scopeSearch; ErrorLog::capture deduplicates by class, file and line.

### 04-20. Two simultaneous requests to open the same 1:1 chat make one of them a 500
- **Severity / category:** low / correctness
- **Location:** `app/Services/ChatService.php:35-59` (also `database/migrations/2026_10_30_000005_create_chat.php:28`)
- **Problem:** The lookup for an existing pair runs outside the transaction. The insert relies on `unique(project_id, pair)` and nothing catches the violation.
- **Failure scenario:** Ana and Beto press "Message" on each other's profile at the same moment, or one of them double-clicks. The second request hits UniqueConstraintViolationException and gets a 500 instead of the existing conversation.
- **Recommended fix:** Catch `UniqueConstraintViolationException` and return the existing row, or use `firstOrCreate` on (project_id, pair).
- **Verification note:** Read start() and the unique index.

### 04-21. Risky paths in this area lack tests
- **Severity / category:** low / tests
- **Location:** `tests/UseCases/Membership/ProjectIsolationTest.php:43-54` (also `tests/UseCases/Community/MediaTest.php:138-150`, `tests/UseCases/Community/ChatTest.php:114-130`)
- **Problem:**
  - The test that a member cannot reach another project's records through their own project's URL covers families, people, units and events, but not media, media files and thumbnails, forum topics and posts, or chat messages. MediaTest only uses an outsider going through the victim's project prefix.
  - The rename/add test always sends an explicit title, which hides 04-05.
  - There are no tests for 04-01 (quota after archiving), 04-02 (ex-member notifications), 04-03 (malformed keys or a sender exception) or 04-04 (one user triggering the global rest).
- **Failure scenario:** Someone renames the `Project::media()` relation, or changes the `media` route parameter, so that it no longer resolves through the project. No test fails, and any member can download another project's files by ID through `/projects/<own>/media/{id}/file`.
- **Recommended fix:** Add media, file, thumbnail, topic and message routes to the cross-prefix isolation test. Add use-case tests for the five gaps above.
- **Verification note:** Read the cited tests. The original claim that removing `scopeBindings()` would fail no test was wrong (ProjectIsolationTest would catch it), so the scenario was narrowed.

## Dropped during verification
- **PDFs served inline without a sandboxing CSP** (MediaFileController.php:19-27): dropped as speculative. Current browsers render PDFs with sandboxed built-in viewers that do not run PDF JavaScript in the page's origin, and NPAPI plugin viewers are gone. Adding `Content-Security-Policy: sandbox` to file responses remains cheap hardening.
- **Reverb `allowed_origins => ['*']`** (config/reverb.php:85): dropped. Private channels still require `/broadcasting/auth` (session plus CSRF), and an origin check does not stop non-browser connection floods, so the concrete payoff is negligible.

## Checked, nothing to report
- **Cross-project access:** routes use `scopeBindings()` with the needed relations on Project. ConversationPolicy requires membership plus participation. Notification `isFor` and `scopeFor` are filtered by membership for PROJECT notifications.
- **Broadcast channels:** `users.{id}` is an identity check, `projects.{id}` uses `canAccess`, and payloads carry IDs only.
- **Upload validation:** content-based MIME allow-list with no SVG, per-project size limit, megapixel guard, feature check, MAX_FILES 20. Storage uses UUID filenames under `projects/{id}` on a non-public local disk, so there is no path traversal: no user-controlled path components, and the download name is slugged.
- **File serving:** fixed Content-Type from the stored MIME, `nosniff`, `private` cache, attachment for non-media types. Trashed media returns 404 through route binding.
- **XSS:** chat, forum and media text is rendered as React text. The only `dangerouslySetInnerHTML` uses are on documentation pages, outside this area. Assistant output is `strip_tags`'d and links are rebuilt server-side from an allow-list of places.
- **Prompt injection:** user-typed fields are sanitised by `Prompt::clean`, and the context holds no other users' data or secrets. Per-person and global daily caps exist (apart from 04-04, 04-12 and 04-13).
- **Notification redirect:** `NotificationController::read` redirects to a server-built path, so there is no open redirect.
