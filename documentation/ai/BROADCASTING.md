# Broadcasting System - AI Agent Reference

## Overview

This document provides comprehensive technical details about the Echo/Reverb broadcasting system implementation for AI agents. It includes architectural decisions, data structures, gotchas, and patterns used.

## Architecture

### Backend

#### Configuration Files

**Location**: `config/broadcasting.php`, `config/reverb.php`
- Default broadcaster: `reverb` (via `BROADCAST_CONNECTION` env var)
- Reverb runs as a Docker service on port 8080
- Configuration uses environment variables for flexibility

**Environment Variables**:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### Channel Definitions

**Location**: `routes/channels.php`

Three channel types:

1. **Private User Channel**: `private.{user}`
   - Authorization: User can only access their own channel
   - Returns: `true` (boolean authorization)
   - Used for: User-specific notifications and messages

2. **Project Presence Channel**: `projects.{project}`
   - Authorization: Uses Project global scope (automatically applied)
   - Returns: Array with `['id' => $user->id, 'name' => $user->fullname]`
   - **CRITICAL**: Must return user data array for presence to work
   - **CRITICAL**: User model has `fullname` attribute, NOT `name` property
   - Used for: Project-scoped messages and tracking online users

3. **Global Admin Channel**: `global`
   - Authorization: Only users with multiple projects
   - Returns: `true` (boolean authorization)
   - Used for: Multi-project admin notifications

**Key Points**:
- Presence channels MUST return user data as an array
- Private channels return boolean for authorization
- Route model binding automatically applies global scopes
- User model uses `fullname` attribute accessor, not a `name` property

#### BroadcastService

**Location**: `app/Services/BroadcastService.php`

**Purpose**: Abstraction layer to hide broadcasting implementation details from consumers.

**Public API**:
```php
// Send to specific channel with identifier
send(BroadcastChannel $channel, Message $message, int|string|null $identifier)

// Convenience methods
toUser(int $userId, Message $message)
toProject(int $projectId, Message $message)
toGlobal(Message $message)
```

**Implementation Details**:
- Uses Laravel's `Broadcast::on()` facade method which returns `AnonymousEvent`
- `AnonymousEvent` uses fluent API: `via()`, `as()`, `with()`, `send()`
- **CRITICAL**: The order matters - channel instance passed to `on()`, then methods chained
- Automatically selects `PrivateChannel` vs `PresenceChannel` based on channel type
- Project channels are `PresenceChannel` for user tracking
- Private and global channels are `PrivateChannel`

**Correct Usage Pattern**:
```php
BroadcastFacade::on($channelInstance)
    ->via($connection)
    ->as($eventName)
    ->with($payload)
    ->send();
```

**Gotchas**:
- Don't try to call `.channel()` on the result of `Broadcast::on()` - the channel is passed to `on()` directly
- Message event name is the `BroadcastMessage` enum value (e.g., "user.navigation")
- Frontend listens with `.{eventName}` prefix

#### Supporting Enums and Data Objects

**BroadcastChannel Enum** (`app/Services/Broadcast/Enums/BroadcastChannel.php`):
- Defines channel types: PRIVATE, PROJECT, GLOBAL
- Has `channel(int|string|null $identifier)` method to build full channel names
- Has `requiresIdentifier()` method to validate usage

**BroadcastMessage Enum** (`app/Services/Broadcast/Enums/BroadcastMessage.php`):
- Defines message types as string values (e.g., "resource.created", "user.navigation")
- Values must match exactly on frontend for listeners to work
- Include dot notation for namespacing (e.g., "user.navigation", "lottery.started")

**Message Data Object** (`app/Services/Broadcast/DataObjects/Message.php`):
- Immutable data object pattern
- Always includes metadata with timestamp
- `toArray()` returns structure: `['type' => string, 'data' => array, 'metadata' => array]`
- Fluent methods `withMetadata()` and `withData()` return new instances

### Frontend

#### Data Structure - Critical Understanding

**IMPORTANT**: Projects are NOT at `auth.value.projects`. The correct structure is:

```typescript
auth.value = {
  user: {
    id: number,
    fullname: string,
    projects: ApiResource<Project>[],  // <-- Projects are HERE
    can: { ... },
    ...
  },
  notifications: { ... }
}
```

**Correct Access Patterns**:
- Via useAuth composable: `projects.value` (exported computed property)
- Direct access: `auth.value.user.projects` or `currentUser.value.projects`
- **WRONG**: `auth.value.projects` (this doesn't exist)

**Why This Matters**:
- Projects belong to the user entity, not the auth object
- The useAuth composable exports `projects` as a convenience
- Import it as: `import { projects } from '@/composables/useAuth'`

#### useBroadcasting Composable

**Location**: `resources/js/composables/useBroadcasting.ts`

**Design Philosophy**: Zero-configuration API. Automatically connects to all relevant channels on mount, automatically disconnects on unmount. Consumers just register callbacks.

**Imports Required**:
```typescript
import { echo } from '@laravel/echo-vue';
import { auth, projects } from '@/composables/useAuth';
```

**State Management**:
- `subscriptions: Map<string, () => void>` - Cleanup functions per channel
- `onlineUsersByProject: Map<number, OnlineUser[]>` - Presence data per project
- All callback registrations are module-level Maps/Sets (shared across instances)

**Channel Connection Logic**:

1. **Private Channel**:
   - Channel name: `private.{userId}`
   - Echo method: `echo().private(channelName)`
   - Key format: `private-${channelName}`

2. **Project Presence Channels**:
   - Channel name: `projects.{projectId}`
   - Echo method: `echo().join(channelName)` (returns PresenceChannel)
   - Key format: `presence-${channelName}`
   - Hooks: `here()`, `joining()`, `leaving()`
   - **CRITICAL**: Cast to `PresenceChannel` type for proper TypeScript support

3. **Global Channel**:
   - Channel name: `global`
   - Echo method: `echo().private(channelName)`
   - Key format: `private-${channelName}`

**Event Listening Pattern**:

Laravel Echo doesn't have a `listenToAll()` method. Instead, we:
1. Define all possible message types in `MESSAGE_TYPES` array
2. Loop through and call `channel.listen(.${type}, callback)` for each
3. The `.` prefix is required for custom event names in Laravel Echo

```typescript
MESSAGE_TYPES.forEach((type) => {
  channel.listen(`.${type}`, (data: BroadcastMessage) => {
    // Handle event
  });
});
```

**Callback Triggering Pattern**:

When an event is received, we trigger callbacks in this order:
1. Channel-specific callbacks (private/project/global)
2. Message-type-specific callbacks
3. "Any message" callbacks

This allows flexible listening patterns without duplication.

**Cleanup Pattern**:

Each channel connection stores a cleanup function in the `subscriptions` Map:
```typescript
subscriptions.set(key, () => {
  echo().leave(channelName);
  // Additional cleanup (e.g., delete presence data)
});
```

On unmount, all cleanup functions are executed.

**Public API**:

```typescript
const {
  // Callback registration (all return cleanup functions)
  onMessage,           // (type, callback) => () => void
  onAnyMessage,        // (callback) => () => void
  onProjectMessage,    // (callback) => () => void  // callback gets projectId
  onGlobalMessage,     // (callback) => () => void
  onPrivateMessage,    // (callback) => () => void

  // Presence tracking
  getOnlineUsers,      // (projectId) => Ref<OnlineUser[]>
  getPresenceInfo,     // (projectId) => Ref<PresenceInfo>

  // Manual control (rarely needed)
  disconnectAll,       // () => void
} = useBroadcasting();
```

**Usage Examples**:

```typescript
// Listen for specific message type (any channel)
const cleanup = onMessage('user.navigation', (message) => {
  console.log('User navigated:', message.data);
});

// Listen for all project messages
onProjectMessage((message, projectId) => {
  console.log('Project event:', { message, projectId });
});

// Get online users for a project
const onlineUsers = getOnlineUsers(1);
watch(onlineUsers, (users) => {
  console.log('Online users changed:', users);
});

// Cleanup when done (automatically done on unmount)
onUnmounted(() => {
  cleanup();
});
```

#### Type Definitions

**Location**: `resources/js/types/broadcasting.d.ts`

**BroadcastMessage Interface**:
```typescript
interface BroadcastMessage<T = any> {
  type: BroadcastMessageType;
  data: T;
  metadata: {
    timestamp: string;
    [key: string]: any;
  };
}
```

This matches the structure from `Message::toArray()` in the backend.

**PresenceInfo Interface**:
```typescript
interface PresenceInfo {
  users: OnlineUser[];
  count: number;
}
```

**OnlineUser Interface**:
```typescript
interface OnlineUser {
  id: number;
  name: string;      // From User.fullname
  joinedAt: Date;    // Added by frontend when user joins
}
```

## Testing

### Temporary Test Implementation

**Test Middleware**: `app/Http/Middleware/BroadcastNavigationTest.php`
- Broadcasts on every request
- **MARKED AS TEMPORARY** - Must be removed after testing
- Registered in `bootstrap/app.php` with TEMPORARY comment

**Test Component**: `resources/js/components/layout/header/AppSidebarHeader.vue`
- Registers all callback types
- Logs to console
- **MARKED AS TEMPORARY** - Remove listeners after testing

### Testing Presence Tracking

**Critical Requirements for Presence to Work**:

1. **Both users must be in the same project**
   - Presence channel is `projects.{projectId}`
   - Users in different projects won't see each other
   - Check `session('current_project_id')` or route project parameter

2. **Projects must be loaded**
   - Check console: `[useBroadcasting] Available projects: [...]`
   - Should show array of Project resources
   - If empty, check HandleInertiaRequests middleware

3. **Channel authorization must pass**
   - Check browser Network tab → WS → Messages
   - Look for subscription success/failure
   - Common failure: User doesn't have access to project (global scope)

4. **Both users must actually navigate**
   - Presence channel is joined when component mounts
   - Both browser windows need to load the page after login
   - Refreshing the page will re-join the channel

**Expected Console Output**:

User 1 (first to join):
```
[useBroadcasting] Initializing channels for user: 1
[useBroadcasting] Available projects: [{id: 1, ...}]
[useBroadcasting] Connecting to project channel: 1
[useBroadcasting] Attempting to join presence channel: projects.1
[useBroadcasting] Joined presence channel: projects.1
[useBroadcasting] Users already in project channel: []
```

User 2 (second to join):
```
[useBroadcasting] Initializing channels for user: 2
[useBroadcasting] Available projects: [{id: 1, ...}]
[useBroadcasting] Connecting to project channel: 1
[useBroadcasting] Attempting to join presence channel: projects.1
[useBroadcasting] Joined presence channel: projects.1
[useBroadcasting] Users already in project channel: [{id: 1, name: 'User One'}]
```

User 1 then sees:
```
[useBroadcasting] User joining project channel: {id: 2, name: 'User Two'}
```

### Debugging Checklist

**No messages received at all**:
1. Check Reverb service is running: `docker ps | grep reverb`
2. Check browser console for WebSocket connection errors
3. Check `config('broadcasting.default')` returns 'reverb'
4. Check environment variables are set correctly
5. Verify user is authenticated

**Messages received but not presence**:
1. Check both users are in the same project
2. Check `projects.value` is populated (console logs)
3. Check browser Network tab → WS → look for presence subscriptions
4. Check routes/channels.php returns array for projects channel
5. Verify User model has `fullname` attribute

**Presence joins but no `joining` events**:
1. Check if users joined at same time (first user won't see themselves)
2. Reload page in second browser window after first user is already connected
3. Check console for "User joining" log
4. Check browser Network tab for presence webhooks

**Authorization failures**:
1. Check routes/channels.php authorization logic
2. Verify Project global scope isn't preventing access
3. Check user has access to the project in question
4. Look for 403 errors in browser Network tab

## Common Mistakes to Avoid

### Backend

1. **Don't convert models to resources manually in HandleInertiaRequests**
   - Resources are automatically applied when sent to frontend
   - Just return `Project::all()`, not `Project::all()->map->toResource()`

2. **Don't use non-existent User properties**
   - User model has `fullname` attribute, NOT `name` property
   - Check model definition before using properties

3. **Don't use wrong channel types**
   - Presence channels need `PresenceChannel` instance
   - Private/global channels need `PrivateChannel` instance
   - Don't mix them up in BroadcastService

4. **Don't forget to return user data in presence channels**
   - Presence channels must return array: `['id' => ..., 'name' => ...]`
   - Private channels return boolean
   - This is not optional - presence tracking requires the data

### Frontend

1. **Don't access projects at wrong location**
   - NOT: `auth.value.projects`
   - CORRECT: `currentUser.value.projects` or use `projects` from useAuth

2. **Don't forget the dot prefix in event names**
   - Echo requires `.${eventName}` for custom events
   - Example: `channel.listen('.user.navigation', callback)`

3. **Don't try to use `listenToAll()` method**
   - This doesn't exist in Laravel Echo
   - Loop through known message types instead

4. **Don't forget to cast presence channels**
   - `echo().join()` returns generic type
   - Cast to `PresenceChannel` for TypeScript: `as PresenceChannel`

5. **Don't assume projects are always loaded**
   - Check `projects.value.length` before iterating
   - Handle empty array case gracefully

6. **Don't forget cleanup functions**
   - All `on*` methods return cleanup functions
   - Store them if you need to manually unregister
   - Automatic cleanup happens on unmount

## Implementation Patterns

### Backend Broadcasting Pattern

```php
use App\Services\BroadcastService;
use App\Services\Broadcast\DataObjects\Message;
use App\Services\Broadcast\Enums\BroadcastMessage;

// In your controller/service/event handler
public function __construct(
    protected BroadcastService $broadcast,
) {}

public function doSomething()
{
    // Broadcast to user
    $this->broadcast->toUser(
        $userId,
        Message::make(
            BroadcastMessage::NOTIFICATION,
            ['text' => 'Something happened'],
            ['extra' => 'metadata']
        )
    );

    // Broadcast to project
    $this->broadcast->toProject(
        $projectId,
        Message::make(
            BroadcastMessage::RESOURCE_UPDATED,
            ['resource_id' => 123, 'resource_type' => 'Unit']
        )
    );
}
```

### Frontend Listening Pattern

```typescript
// In component setup
import { useBroadcasting } from '@/composables/useBroadcasting';

const { onMessage, onProjectMessage, getOnlineUsers } = useBroadcasting();

// Listen for specific events
onMessage('resource.updated', (message) => {
  // Handle resource update
  if (message.data.resource_type === 'Unit') {
    refreshUnits();
  }
});

// Listen for project events with project context
onProjectMessage((message, projectId) => {
  if (message.type === 'user.navigation') {
    console.log(`User navigated in project ${projectId}:`, message.data);
  }
});

// Watch online users
const currentProjectId = computed(() => page.props.project?.id);
const onlineUsers = computed(() =>
  currentProjectId.value ? getOnlineUsers(currentProjectId.value).value : []
);
```

## Data Flow

### Complete Flow Example: User Navigates

1. **Middleware** (`BroadcastNavigationTest.php`) intercepts request
2. **BroadcastService** called: `toUser($userId, Message::make(...))`
3. **BroadcastService** creates `PrivateChannel` instance
4. **BroadcastService** uses `Broadcast::on($channel)` fluent API
5. **Laravel** broadcasts to Reverb WebSocket server
6. **Reverb** sends message to connected clients on that channel
7. **Frontend** Echo receives message via WebSocket
8. **useBroadcasting** listener catches event: `.user.navigation`
9. **useBroadcasting** triggers registered callbacks in order
10. **Component** callback executes, logs to console

### Presence Flow Example: User Joins Project Page

1. **Component** mounts, calls `useBroadcasting()`
2. **useBroadcasting** reads `projects.value` from useAuth
3. **useBroadcasting** loops through projects, calls `connectToProjectChannel()`
4. **connectToProjectChannel** calls `echo().join('projects.{id}')`
5. **Laravel Echo** subscribes to presence channel via Reverb
6. **Reverb** checks authorization via `routes/channels.php`
7. **routes/channels.php** returns user data array
8. **Reverb** sends current users via `here()` callback
9. **useBroadcasting** stores users in `onlineUsersByProject` Map
10. When second user joins, Reverb triggers `joining()` on first user's client
11. **useBroadcasting** adds new user to the Map
12. **Components** watching `getOnlineUsers()` react to changes

## Extension Points

### Adding New Message Types

1. Add to `BroadcastMessage` enum
2. Add to `MESSAGE_TYPES` array in useBroadcasting.ts
3. Add to `BroadcastMessageType` union in broadcasting.d.ts

### Adding New Channels

1. Add channel definition to `routes/channels.php`
2. Add to `BroadcastChannel` enum (if reusable)
3. Update BroadcastService if needed (add convenience method)
4. Update useBroadcasting to auto-connect if appropriate

### Custom Event Handling

Don't modify the composable - register callbacks in your components:

```typescript
onMessage('custom.event', (message) => {
  // Custom handling
});
```

Or create specialized composables that use useBroadcasting:

```typescript
export function useLotteryBroadcasting() {
  const { onMessage } = useBroadcasting();

  onMessage('lottery.started', (message) => {
    // Lottery-specific logic
  });

  onMessage('lottery.completed', (message) => {
    // Lottery-specific logic
  });
}
```

## Production Considerations

1. **Remove test code**:
   - Delete `BroadcastNavigationTest.php`
   - Remove from `bootstrap/app.php`
   - Remove test listeners from `AppSidebarHeader.vue`

2. **Configure Reverb for production**:
   - Use HTTPS (wss://) instead of HTTP (ws://)
   - Set proper CORS allowed origins
   - Configure Redis for scaling if needed

3. **Rate limiting**:
   - Consider rate limiting broadcasts to prevent spam
   - Especially important for user-triggered events

4. **Error handling**:
   - Add try-catch in broadcast calls
   - Log failures but don't break application flow
   - Broadcasting should be non-blocking

5. **Testing**:
   - Write Pest tests for BroadcastService
   - Write Vitest tests for useBroadcasting
   - Test presence channel behavior with multiple users
   - Test cleanup on unmount

## References

- Laravel Broadcasting: https://laravel.com/docs/11.x/broadcasting
- Laravel Reverb: https://reverb.laravel.com/
- Laravel Echo: https://github.com/laravel/echo
- Presence Channels: https://reverb.laravel.com/docs/#/1.x/presence-channels
