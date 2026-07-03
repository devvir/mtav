# Broadcasting System

## Overview

This document describes the Echo/Reverb broadcasting system implementation and how to test it.

## Architecture

### Backend

#### Configuration
- **Reverb Service**: Running on port 8080 (Docker service `reverb`)
- **Channels**: Defined in `routes/channels.php`
  - `private.{user}` - Private channel for each user
  - `projects.{project}` - Presence channel for project members
  - `global` - Global channel for multi-project admins

#### BroadcastService

Located in `app/Services/BroadcastService.php`, this service provides a clean API for broadcasting messages:

```php
use App\Services\BroadcastService;
use App\Services\Broadcast\DataObjects\Message;
use App\Services\Broadcast\Enums\BroadcastMessage;
use App\Services\Broadcast\Enums\BroadcastChannel;

// Send to a user's private channel
app(BroadcastService::class)->toUser(
    $userId,
    Message::make(BroadcastMessage::NOTIFICATION, ['text' => 'Hello!'])
);

// Send to a project channel
app(BroadcastService::class)->toProject(
    $projectId,
    Message::make(BroadcastMessage::RESOURCE_CREATED, ['id' => 123])
);

// Send to global channel
app(BroadcastService::class)->toGlobal(
    Message::make(BroadcastMessage::SYSTEM_MESSAGE, ['text' => 'Maintenance'])
);
```

#### Supporting Files

- `app/Services/Broadcast/Enums/BroadcastChannel.php` - Available channels
- `app/Services/Broadcast/Enums/BroadcastMessage.php` - Message types
- `app/Services/Broadcast/DataObjects/Message.php` - Message data object

### Frontend

#### useBroadcasting Composable

Located in `resources/js/composables/useBroadcasting.ts`, this composable provides zero-configuration broadcasting:

```typescript
import { useBroadcasting } from '@/composables/useBroadcasting';

const {
  onMessage,           // Listen for specific message type
  onAnyMessage,        // Listen for any message
  onProjectMessage,    // Listen for project channel messages
  onGlobalMessage,     // Listen for global channel messages
  onPrivateMessage,    // Listen for private channel messages
  getOnlineUsers,      // Get online users for a project
  getPresenceInfo,     // Get presence info for a project
} = useBroadcasting();

// Listen for specific message type (any channel)
onMessage('resource.created', (message) => {
  console.log('Resource created:', message.data);
});

// Listen for project messages
onProjectMessage((message, projectId) => {
  console.log('Project message:', message, projectId);
});

// Get online users for project
const onlineUsers = getOnlineUsers(1);
console.log('Online users:', onlineUsers.value);
```

#### Features

- **Auto-connection**: Automatically connects to private and project channels on mount
- **Auto-cleanup**: Automatically disconnects on unmount
- **Presence tracking**: Tracks online users in project channels
- **Type-safe**: Full TypeScript support with type definitions

#### Supporting Files

- `resources/js/types/broadcasting.d.ts` - TypeScript definitions

## Testing

There is no app code that auto-broadcasts on every request. To exercise the pipeline
manually, follow these steps.

1. **Start the development environment**:
   ```bash
   mtav up
   ```

2. **Verify Reverb is running**:
   ```bash
   docker ps | grep reverb
   ```
   Should show the reverb service running on port 8080.

3. **Check environment variables** (in `.env` or Docker environment):
   ```
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=your-app-id
   REVERB_APP_KEY=your-app-key
   REVERB_APP_SECRET=your-app-secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http
   ```

4. **Open the application** in a browser and log in. Keep the browser DevTools open on the
   Network tab → WS to see the WebSocket frames.

5. **Send a test broadcast** from `tinker` to the logged-in user (replace `1` with the
   user id):
   ```bash
   mtav artisan tinker
   >>> use App\Services\BroadcastService;
   >>> use App\Services\Broadcast\DataObjects\Message;
   >>> use App\Services\Broadcast\Enums\BroadcastMessage;
   >>> app(BroadcastService::class)->toUser(1, Message::make(BroadcastMessage::NOTIFICATION, ['text' => 'hello']));
   ```

   You should see the corresponding WebSocket frame arrive in the browser, and any
   component registered via `useBroadcasting().onMessage('notification', …)` should fire.

6. **Test presence** by opening the app in multiple browser tabs / windows logged in as
   different users in the same project — `useBroadcasting().getOnlineUsers(projectId)`
   should track them joining and leaving.

## Troubleshooting

### Messages Not Received

1. **Check Reverb logs**:
   ```bash
   mtav logs reverb
   ```

2. **Check browser console** for WebSocket connection errors

3. **Verify channel authorization**:
   ```bash
   # Check if user can access the channel
   # Look for 403 errors in browser network tab
   ```

4. **Check broadcasting connection**:
   ```bash
   mtav artisan tinker
   >>> config('broadcasting.default')
   ```

### WebSocket Connection Failed

1. **Verify Reverb is running**: `docker ps | grep reverb`

2. **Check port mapping**: Ensure port 8080 is accessible

3. **Check CORS settings**: Reverb should allow `*` origins by default (see `config/reverb.php`)

### Channel Authorization Failed

1. **Check `routes/channels.php`** - ensure authorization logic is correct

2. **Check user authentication** - user must be logged in

3. **Check project access** - user must have access to the project for `projects.{id}` channels

## Next Steps

1. Implement real features using the broadcasting system
2. Add backend tracking of online users (if needed)
3. Add visual indicators for online presence
4. Add notification UI components
5. Add real-time resource updates

## API Reference

### Backend

#### BroadcastService Methods

- `send(BroadcastChannel $channel, Message $message, $identifier)` - Send to any channel
- `toUser(int $userId, Message $message)` - Send to user's private channel
- `toProject(int $projectId, Message $message)` - Send to project channel
- `toGlobal(Message $message)` - Send to global channel

#### BroadcastMessage Types

- `RESOURCE_CREATED` - Resource was created
- `RESOURCE_UPDATED` - Resource was updated
- `RESOURCE_DELETED` - Resource was deleted
- `USER_JOINED` - User joined
- `USER_LEFT` - User left
- `USER_TYPING` - User is typing
- `NOTIFICATION` - General notification
- `SYSTEM_MESSAGE` - System message
- `LOTTERY_STARTED` - Lottery started
- `LOTTERY_COMPLETED` - Lottery completed

### Frontend

#### useBroadcasting Methods

- `onMessage(type, callback)` - Listen for specific message type
- `onAnyMessage(callback)` - Listen for any message
- `onProjectMessage(callback)` - Listen for project messages
- `onGlobalMessage(callback)` - Listen for global messages
- `onPrivateMessage(callback)` - Listen for private messages
- `getOnlineUsers(projectId)` - Get online users for project
- `getPresenceInfo(projectId)` - Get presence info for project
- `disconnectAll()` - Disconnect all channels

All listener methods return a cleanup function that can be called to stop listening.
