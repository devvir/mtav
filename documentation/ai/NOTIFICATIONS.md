# Notifications System

## Overview

The notifications system provides real-time updates about resource lifecycle events (CRUD operations) and system events. It supports three notification channels: **private** (user-specific), **project** (all users in a project), and **global** (multi-project admins and superadmins).

## Architecture

### Core Models

#### Notification Model
[app/Models/Notification.php](../../app/Models/Notification.php)

The main notification model with the following fields:
- `id`: Primary key
- `type`: NotificationType enum (e.g., RESOURCE_CREATED, RESOURCE_UPDATED)
- `target`: NotificationTarget enum (PRIVATE, PROJECT, or GLOBAL)
- `target_id`: ID of the target (user ID for PRIVATE, project ID for PROJECT, null for GLOBAL)
- `data`: JSON array containing notification payload (title, message, action, resource info)
- `triggered_by`: User ID who triggered the event (nullable)
- `created_at`, `updated_at`: Timestamps

**Key Methods:**
- `isReadBy(User $user): bool` - Check if user has read notification
- `markAsReadBy(User $user): void` - Mark as read for user
- `markAsUnreadBy(User $user): void` - Mark as unread for user
- `newCollection()` - Returns custom `NotificationCollection` instead of standard Eloquent Collection

**Query Scopes:**
- `global()` - Filter global notifications
- `project(Project|int|null)` - Filter project notifications (optionally by project)
- `projects(iterable|null)` - Filter project notifications in multiple projects
- `private(User|int|null)` - Filter private notifications (optionally by user)
- `forUser(User)` - Get all notifications visible to user (private + their projects + global if applicable)
- `readBy(User)` - Filter notifications read by user
- `unreadBy(User)` - Filter notifications NOT read by user
- `olderThan(?int $id)` - Pagination helper: get notifications older than given ID

#### NotificationRead Model (Pivot)
[app/Models/NotificationRead.php](../../app/Models/NotificationRead.php)

Tracks which users have read which notifications.

**Fields:**
- `notification_id` (FK to notifications)
- `user_id` (FK to users)
- `read_at`: Timestamp when notification was read
- `created_at`, `updated_at`: Standard timestamps

**Static Methods:**
- `markManyAsReadBy(Collection $notifications, User $user)` - Batch mark multiple notifications as read

### Enums

#### NotificationType
[app/Enums/NotificationType.php](../../app/Enums/NotificationType.php)

Defines all notification types:

**Resource Lifecycle:**
- `RESOURCE_CREATED`
- `RESOURCE_UPDATED`
- `RESOURCE_DELETED`
- `RESOURCE_RESTORED`

**User/Member Events:**
- `REGISTRATION_CONFIRMED`

**Unit/Housing Events:**
- `UNIT_ASSIGNED`
- `LOTTERY_COMPLETED`

**Event/RSVP Events:**
- `RSVP_CONFIRMED`
- `EVENT_REMINDER`

**Project/Construction Events:**
- `CONSTRUCTION_UPDATE`
- `MILESTONE_REACHED`

**General/Admin Events:**
- `NEWS_POSTED`
- `SYSTEM_ANNOUNCEMENT`
- `SYSTEM_MAINTENANCE`

#### NotificationTarget
[app/Enums/NotificationTarget.php](../../app/Enums/NotificationTarget.php)

Defines notification delivery channels:
- `PRIVATE`: For a specific user (`target_id` = user ID)
- `PROJECT`: For all users in a project (`target_id` = project ID)
- `GLOBAL`: For multi-project admins and superadmins (`target_id` = null)

### Services

#### NotificationService
[app/Services/NotificationService.php](../../app/Services/NotificationService.php)

Main service for fetching notifications. Uses cursor-based pagination (via `oldestId`).

**Public Methods:**
- `privateNotifications(User $user, int $limit = 10, ?int $oldestId = null): Notifications`
  - Fetch user's private notifications
- `projectNotifications(Project $project, int $limit = 10, ?int $oldestId = null): Notifications`
  - Fetch project-scoped notifications
- `globalNotifications(int $limit = 10, ?int $oldestId = null): Notifications`
  - Fetch global notifications
- `forUser(User $user, int $limit = 10, ?int $oldestId = null): Notifications`
  - Fetch all notifications visible to user (combines private, project, and global)
- `handleResourceEvent(Model $model, NotificationType $type): void`
  - Delegates to ResourceLifecycle service to create notifications

**Return Type:** Returns `Notifications` DTO (Data Transfer Object) containing:
- `data`: NotificationCollection with read state loaded
- `hasMore`: Boolean indicating if more notifications exist

#### ResourceLifecycle Service
[app/Services/Notifications/ResourceLifecycle.php](../../app/Services/Notifications/ResourceLifecycle.php)

Automatically creates notifications for resource lifecycle events (CRUD operations).

**Handled Resources:**
- `Event`
- `Family`
- `Media`
- `Project`
- `Unit`
- `UnitType`

**Notification Targeting:**
- **Project CRUD** → GLOBAL (except updates, which are PROJECT-scoped)
- **All other resources** → PROJECT-scoped

**Notification Payload Structure:**
```php
[
    'resource'    => 'event',           // Lowercase model name
    'resource_id' => 123,                // Model ID
    'title'       => 'Admin Name published a new Event',
    'message'     => 'Event: "Team Meeting"',
    'action'      => 'https://app.com/events/123',  // Route to view resource
]
```

**Special Handling:**
- Auto-created Lottery events (when Project is created) are NOT notified
- Non-handled models (Member, Admin, Log, etc.) do NOT generate notifications
- Soft-delete updates are handled by the `deleted()` observer, not `updated()`

**Translation Keys:** Uses `lang/*/events.php` for i18n:
- Pattern: `events.resource_{resource}_{event}_title`
- Pattern: `events.resource_{resource}_{event}_message`
- Events: `created`, `updated`, `deleted`, `restored`

#### SystemNotifications Service
[app/Services/Notifications/SystemNotifications.php](../../app/Services/Notifications/SystemNotifications.php)

Placeholder for future system-generated notifications (announcements, maintenance, etc.). Currently empty.

### Collections & Data Objects

#### NotificationCollection
[app/Services/Notifications/NotificationCollection.php](../../app/Services/Notifications/NotificationCollection.php)

Custom Eloquent Collection for Notification models.

**Key Method:**
- `withReadState(?User $user = null): self`
  - Efficiently loads read state for all notifications in collection using a single query
  - Adds a `read` attribute (boolean) to each notification
  - Uses authenticated user if none provided

#### Notifications DTO
[app/Services/Notifications/DataObjects/Notifications.php](../../app/Services/Notifications/DataObjects/Notifications.php)

Data Transfer Object returned by NotificationService methods.

**Properties:**
- `notifications`: NotificationCollection
- `hasMore`: boolean (for pagination)

**Methods:**
- `list(): NotificationCollection`
- `hasMore(): bool`
- `toArray(): array`

### Resources

#### NotificationResource
[app/Http/Resources/NotificationResource.php](../../app/Http/Resources/NotificationResource.php)

JSON API Resource for Notification model.

**Output:**
```php
[
    'id'        => 1,
    'data'      => [...],        // Notification payload
    'target'    => 'project',
    'target_id' => 1,
    'created_at' => Carbon,
    'is_read'   => true,         // Only included when 'read' attribute is set
]
```

### Controllers

#### NotificationController
[app/Http/Controllers/Resources/NotificationController.php](../../app/Http/Controllers/Resources/NotificationController.php)

**Route:** `GET /notifications`

Displays notifications page with paginated notifications.

**Query Parameters:**
- `oldestId` (optional): Integer for cursor-based pagination

**Returns:** Inertia response with:
- Component: `'Notifications'`
- Props: `{ notifications: Notifications }` (contains data + hasMore)

#### NotificationReadController
[app/Http/Controllers/NotificationReadController.php](../../app/Http/Controllers/NotificationReadController.php)

Handles marking notifications as read/unread.

**Routes:**
- `POST /notifications/{notification}/read` - Mark as read
- `POST /notifications/{notification}/unread` - Mark as unread
- `POST /notifications/read-all` - Mark all user's notifications as read

**Authorization:** Uses `NotificationPolicy` to verify user can view the notification before marking read/unread.

### Policies

#### NotificationPolicy
[app/Policies/NotificationPolicy.php](../../app/Policies/NotificationPolicy.php)

**view(User, Notification):**
- `PRIVATE`: Only if `target_id` matches user ID
- `PROJECT`: Only if user has access to project (via Project scope)
- `GLOBAL`: Only if user manages multiple projects

**Other Actions:**
- `create`, `update`, `delete`, `restore`: All return `false` (notifications are system-managed)

### User Integration

#### HasNotifications Trait
[app/Models/Concerns/HasNotifications.php](../../app/Models/Concerns/HasNotifications.php)

Used by User model to add notification-related query builders.

**Query Builders:**
- `notifications()` - All notifications visible to user
- `privateNotifications()` - Private notifications only
- `projectNotifications()` - Project notifications only
- `globalNotifications()` - Global notifications only
- `readNotifications()` - Read notifications
- `unreadNotifications()` - Unread notifications

**Pseudo-Attributes** (for convenience):
- `$user->notifications` - Collection of all visible notifications
- `$user->privateNotifications`
- `$user->projectNotifications`
- `$user->globalNotifications`
- `$user->readNotifications`
- `$user->unreadNotifications`

**Note:** This trait overrides Laravel's built-in `Notifiable` trait methods to use the custom notification system instead of Laravel's default database notifications.

### Middleware Integration

#### HandleInertiaRequests
[app/Http/Middleware/HandleInertiaRequests.php](../../app/Http/Middleware/HandleInertiaRequests.php)

Shares recent notifications with every Inertia page load.

**Shared Prop:**
```php
'notifications' => Inertia::defer(fn () => $this->recentNotifications($request))
```

**Implementation:**
- Deferred loading (loaded on-demand, not on initial page load)
- Returns `null` if user not authenticated
- Fetches last 10 notifications via `NotificationService::forUser()`
- Automatically includes read state via `NotificationCollection::withReadState()`

### Observer Integration

#### ModelObserver
[app/Observers/ModelObserver.php](../../app/Observers/ModelObserver.php)

Automatically observes all models extending `App\Models\Model`.

**Observed Events:**
- `created()` → Calls `NotificationService::handleResourceEvent()` with `RESOURCE_CREATED`
- `updated()` → Calls with `RESOURCE_UPDATED` (but ignores soft-deletes)
- `deleted()` → Calls with `RESOURCE_DELETED`
- `restored()` → Calls with `RESOURCE_RESTORED`

**Note:** Soft-delete status changes (`deleted_at` only) are ignored by `updated()` since they're handled by `deleted()` and `restored()` events.

## Database Schema

### notifications Table
```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY,
    type VARCHAR,              -- NotificationType enum value
    target VARCHAR,            -- NotificationTarget enum value
    target_id BIGINT NULL,     -- User ID (private) or Project ID (project) or NULL (global)
    data JSON,                 -- Notification payload
    triggered_by BIGINT NULL,  -- FK to users.id (who triggered the event)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### notification_read Table (Pivot)
```sql
CREATE TABLE notification_read (
    notification_id BIGINT,
    user_id BIGINT,
    read_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    PRIMARY KEY (notification_id, user_id),
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Test Coverage

### Unit Tests

#### NotificationTest
[tests/Unit/Models/NotificationTest.php](../../tests/Unit/Models/NotificationTest.php)

Tests model relations, scopes, and read/unread methods.

**Coverage:**
- Relations: `readBy()` relation
- Read/Unread methods: `isReadBy()`, `markAsReadBy()`, `markAsUnreadBy()`
- Scopes: `global()`, `project()`, `projects()`, `private()`, `forUser()`, `olderThan()`
- Data casting

#### NotificationServiceTest
[tests/Unit/Services/NotificationServiceTest.php](../../tests/Unit/Services/NotificationServiceTest.php)

Tests NotificationService methods with pagination.

**Coverage:**
- `privateNotifications()` with/without oldestId
- `projectNotifications()` with/without oldestId
- `globalNotifications()` with/without oldestId
- `forUser()` with/without oldestId
- `hasMore` flag accuracy

#### NotificationCollectionTest
[tests/Unit/Services/Notifications/NotificationCollectionTest.php](../../tests/Unit/Services/Notifications/NotificationCollectionTest.php)

Tests custom collection's `withReadState()` method.

**Coverage:**
- Adding read attribute to notifications
- Using authenticated user when none provided
- Handling empty collections
- Efficient batch loading of read state

#### NotificationResourceTest
[tests/Unit/Http/Resources/NotificationResourceTest.php](../../tests/Unit/Http/Resources/NotificationResourceTest.php)

Tests JSON resource transformation.

**Coverage:**
- Basic field transformation
- Conditional `is_read` inclusion based on `read` attribute
- Integration with `NotificationCollection::withReadState()`

### Feature Tests

#### NotificationControllerTest
[tests/Feature/Http/Controllers/NotificationControllerTest.php](../../tests/Feature/Http/Controllers/NotificationControllerTest.php)

Tests HTTP endpoints and authorization.

**Coverage:**
- **index()**: Returns notifications page, pagination, read status
- **read()**: Marks notification as read, authorization
- **unread()**: Marks notification as unread, authorization
- **readAll()**: Marks all user notifications as read
- Authentication requirements

#### ResourceLifecycleTest
[tests/Feature/Services/Notifications/ResourceLifecycleTest.php](../../tests/Feature/Services/Notifications/ResourceLifecycleTest.php)

Tests automatic notification generation for resource events.

**Coverage:**
- Notification creation for all handled resources (Event, Family, Media, Unit, UnitType, Project)
- Correct payload structure (title, message, action, resource data)
- Target assignment (GLOBAL vs PROJECT)
- Special cases: Auto-created Lottery events should NOT notify
- Non-handled models (Member, Admin, Log) should NOT notify
- All CRUD operations: create, update, delete, restore
- `triggered_by` user attribution

### Test Data

#### universe.sql Fixture
[tests/Fixtures/universe.sql](../../tests/Fixtures/universe.sql)

Pre-populated test data used by all tests (20-30x faster than factories).

**Notifications (20 total):**
- **Private (8):** For users #102, #103, #136, #137 in projects 1 & 2
- **Project (10):** 5 for Project #1, 5 for Project #2
- **Global (2):** Visible to multi-project admins

**Critical:** Always use fixture data instead of creating new records with factories, except when testing creation itself.

## Routes

All routes require authentication (`auth` middleware) and email verification (`verified` middleware).

```php
// Notifications index page
GET /notifications → NotificationController@index

// Mark as read/unread
POST /notifications/{notification}/read → NotificationReadController@read
POST /notifications/{notification}/unread → NotificationReadController@unread

// Mark all as read
POST /notifications/read-all → NotificationReadController@readAll
```

**Route Names:**
- `notifications.index`
- `notifications.read`
- `notifications.unread`
- `notifications.readAll`

## Usage Examples

### Fetching User Notifications

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

// Get first page of notifications (limit 10)
$result = $notificationService->forUser($user);
$notifications = $result->list();      // NotificationCollection
$hasMore = $result->hasMore();          // bool

// Get next page (cursor-based pagination)
$oldestId = $notifications->last()->id;
$nextPage = $notificationService->forUser($user, oldestId: $oldestId);
```

### Checking Read Status

```php
// Using Notification model directly
$notification = Notification::find(1);
$isRead = $notification->isReadBy($user);

// Using User trait
$unreadCount = $user->unreadNotifications()->count();
$readNotifications = $user->readNotifications;  // Pseudo-attribute
```

### Marking as Read

```php
// Single notification
$notification->markAsReadBy($user);

// Multiple notifications (batch)
use App\Models\NotificationRead;

$notifications = Notification::forUser($user)->limit(10)->get();
NotificationRead::markManyAsReadBy($notifications, $user);
```

### Querying Notifications

```php
// Private notifications for user
$private = Notification::private($user)->get();

// Project notifications
$projectNotifs = Notification::project($project)->get();

// Global notifications
$global = Notification::global()->get();

// All notifications visible to user (private + projects + global)
$all = Notification::forUser($user)->get();

// Unread notifications for user
$unread = Notification::forUser($user)->unreadBy($user)->get();

// Pagination with cursor
$older = Notification::forUser($user)->olderThan($lastId)->limit(10)->get();
```

### Loading Read State Efficiently

```php
// Load read state for all notifications in one query
$notifications = Notification::forUser($user)->get();
$notifications->withReadState($user);

// Now each notification has a 'read' attribute (boolean)
foreach ($notifications as $notification) {
    if ($notification->read) {
        // User has read this notification
    }
}
```

### Creating Manual Notifications

```php
use App\Models\Notification;
use App\Enums\NotificationType;
use App\Enums\NotificationTarget;

// Private notification
Notification::create([
    'type'         => NotificationType::SYSTEM_ANNOUNCEMENT,
    'target'       => NotificationTarget::PRIVATE,
    'target_id'    => $user->id,
    'data'         => [
        'title'   => 'Welcome!',
        'message' => 'Welcome to the platform',
        'action'  => route('dashboard'),
    ],
    'triggered_by' => Auth::id(),
]);

// Project notification
Notification::create([
    'type'         => NotificationType::NEWS_POSTED,
    'target'       => NotificationTarget::PROJECT,
    'target_id'    => $project->id,
    'data'         => [
        'title'   => 'New Update Available',
        'message' => 'Check out the latest news',
        'action'  => route('news.show', $news),
    ],
    'triggered_by' => Auth::id(),
]);

// Global notification
Notification::create([
    'type'         => NotificationType::SYSTEM_MAINTENANCE,
    'target'       => NotificationTarget::GLOBAL,
    'target_id'    => null,
    'data'         => [
        'title'   => 'Scheduled Maintenance',
        'message' => 'System will be down on Sunday',
        'action'  => null,
    ],
    'triggered_by' => null,
]);
```

## Key Design Patterns

### 1. Three-Channel Architecture
Notifications are delivered through three channels based on target audience:
- **Private**: Direct user communication
- **Project**: Team/project-wide updates
- **Global**: System-wide announcements for admins

### 2. Automatic Resource Lifecycle Notifications
All models extending `App\Models\Model` automatically trigger notifications via `ModelObserver`. The `ResourceLifecycle` service filters which resources should generate notifications.

### 3. Custom Collection with Efficient Read State Loading
`NotificationCollection::withReadState()` loads read status for all notifications in a single query, avoiding N+1 problems.

### 4. Cursor-Based Pagination
Uses `olderThan($id)` scope for efficient pagination instead of offset-based pagination.

### 5. Data Transfer Objects
Service methods return `Notifications` DTO instead of raw collections, providing structured data with pagination metadata.

### 6. Policy-Based Authorization
`NotificationPolicy` ensures users can only view notifications they're authorized to see based on target type.

### 7. Deferred Loading in Middleware
Recent notifications are loaded via `Inertia::defer()` to avoid blocking initial page load.

## Important Notes

### Do NOT Use Laravel's Default Notifications
This system replaces Laravel's built-in notification system. The `HasNotifications` trait overrides the `Notifiable` trait methods. Do NOT use:
- `$user->notify()`
- `Notification::send()`
- Laravel's `notifications` table

### Model Conversion to Resources is Automatic
When sending notifications to the frontend via Inertia, models are AUTOMATICALLY converted to their corresponding JsonResource. Never manually call:
- `NotificationResource::make()`
- `NotificationResource::collection()`
- `$notification->toResource()`

The conversion happens automatically in Inertia middleware.

### Always Use universe.sql Fixture in Tests
The pre-populated test data is 20-30x faster than factories. Only create NEW records when testing the creation itself.

### Read State is Denormalized
The `read` attribute on Notification models is NOT stored in the database. It's added dynamically by `NotificationCollection::withReadState()`. Read state is stored in the pivot table `notification_read`.

### Resource Updates Don't Trigger on Soft-Deletes
The `ModelObserver::updated()` method explicitly ignores updates where only `deleted_at` changed, as these are handled by `deleted()` and `restored()` events.

## Future Enhancements

### SystemNotifications Service
Currently a placeholder. Could be used for:
- System-wide announcements
- Maintenance notifications
- Feature announcements
- Terms of service updates

### Real-Time Broadcasting
The architecture supports future integration with WebSockets/Reverb for real-time notification delivery.

### Email Digests
Could be extended to send email digests of unread notifications.

### Notification Preferences
Could add user preferences for which notification types to receive.

### Notification Expiry
Could add TTL (time-to-live) for notifications to auto-expire old ones.
