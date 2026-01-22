// Copilot - Pending review

import { auth, projects } from '@/composables/useAuth';
import type {
  BroadcastMessage,
  BroadcastMessageType,
  OnlineUser,
  PresenceInfo,
} from '@/types/broadcasting';
import { echo } from '@laravel/echo-vue';
import type { PresenceChannel } from 'laravel-echo';

/**
 * Map to store cleanup functions for channel subscriptions.
 */
const subscriptions = new Map<string, () => void>();

/**
 * Map to store online users per project channel.
 */
const onlineUsersByProject = reactive<Map<number, OnlineUser[]>>(new Map());

/**
 * Known message types that we should listen for.
 */
const MESSAGE_TYPES: BroadcastMessageType[] = [
  'resource.created',
  'resource.updated',
  'resource.deleted',
  'user.joined',
  'user.left',
  'user.typing',
  'user.navigation',
  'notification',
  'system.message',
  'lottery.started',
  'lottery.completed',
];

/**
 * Composable for broadcasting with automatic channel management.
 *
 * Provides a zero-configuration API for listening to broadcast messages.
 * Automatically connects to user's private channel and project channels.
 *
 * Usage:
 *   const { onMessage, onProjectMessage, onlineUsers } = useBroadcasting();
 *
 *   onMessage('resource.created', (data) => {
 *     console.log('Resource created:', data);
 *   });
 *
 *   onProjectMessage((data) => {
 *     console.log('Project message:', data);
 *   });
 */
export function useBroadcasting() {
  /**
   * Initialize channels when composable is used.
   * Connects to private channel and all project channels.
   */
  const initializeChannels = () => {
    if (!auth.value.user) return;

    console.log('[useBroadcasting] Initializing channels for user:', auth.value.user.id);
    console.log('[useBroadcasting] Available projects:', auth.value.projects);

    // Connect to private channel
    connectToPrivateChannel();

    // Connect to project channels
    if (projects.value.length) {
      projects.value.forEach((project: any) => {
        console.log('[useBroadcasting] Connecting to project channel:', project.id);
        connectToProjectChannel(project.id);
      });
    }

    // Connect to global channel if user has multiple projects
    if (projects.value.length > 1) {
      connectToGlobalChannel();
    }
  };

  /**
   * Connect to user's private channel.
   */
  const connectToPrivateChannel = () => {
    const userId = auth.value.user?.id;
    if (!userId) return;

    const channelName = `private.${userId}`;
    const key = `private-${channelName}`;

    if (subscriptions.has(key)) return; // Already subscribed

    const channel = echo().private(channelName);

    // Listen to all known message types
    MESSAGE_TYPES.forEach((type) => {
      channel.listen(`.${type}`, (data: BroadcastMessage) => {
        console.log('[useBroadcasting] Private channel event:', { type, data });
        triggerPrivateCallbacks(data);
        triggerMessageCallbacks(data);
        triggerAnyCallbacks(data);
      });
    });

    // Store cleanup function
    subscriptions.set(key, () => {
      echo().leave(channelName);
    });
  };

  /**
   * Connect to a project presence channel.
   */
  const connectToProjectChannel = (projectId: number) => {
    const channelName = `projects.${projectId}`;
    const key = `presence-${channelName}`;

    console.log('[useBroadcasting] Attempting to join presence channel:', channelName);

    if (subscriptions.has(key)) return; // Already subscribed

    const channel = echo().join(channelName) as PresenceChannel;

    console.log('[useBroadcasting] Joined presence channel:', channelName);

    // Initialize online users list for this project
    onlineUsersByProject.set(projectId, []);

    // Handle initial presence
    channel.here((users: any[]) => {
      console.log('[useBroadcasting] Users already in project channel:', users);
      const onlineUsers = users.map((user) => ({
        id: user.id,
        name: user.name,
        joinedAt: new Date(),
      }));
      onlineUsersByProject.set(projectId, onlineUsers);
    });

    // Handle user joining
    channel.joining((user: any) => {
      console.log('[useBroadcasting] User joining project channel:', user);
      const users = onlineUsersByProject.get(projectId) || [];
      const newUser: OnlineUser = {
        id: user.id,
        name: user.name,
        joinedAt: new Date(),
      };
      onlineUsersByProject.set(projectId, [...users, newUser]);
    });

    // Handle user leaving
    channel.leaving((user: any) => {
      console.log('[useBroadcasting] User leaving project channel:', user);
      const users = onlineUsersByProject.get(projectId) || [];
      onlineUsersByProject.set(
        projectId,
        users.filter((u: OnlineUser) => u.id !== user.id),
      );
    });

    // Listen to all known message types
    MESSAGE_TYPES.forEach((type) => {
      channel.listen(`.${type}`, (data: BroadcastMessage) => {
        console.log('[useBroadcasting] Project channel event:', { type, data, projectId });
        triggerProjectCallbacks(data, projectId);
        triggerMessageCallbacks(data);
        triggerAnyCallbacks(data);
      });
    });

    // Store cleanup function
    subscriptions.set(key, () => {
      echo().leave(channelName);
      onlineUsersByProject.delete(projectId);
    });
  };

  /**
   * Connect to global channel (multi-project admins).
   */
  const connectToGlobalChannel = () => {
    const channelName = 'global';
    const key = `private-${channelName}`;

    if (subscriptions.has(key)) return; // Already subscribed

    const channel = echo().private(channelName);

    // Listen to all known message types
    MESSAGE_TYPES.forEach((type) => {
      channel.listen(`.${type}`, (data: BroadcastMessage) => {
        console.log('[useBroadcasting] Global channel event:', { type, data });
        triggerGlobalCallbacks(data);
        triggerMessageCallbacks(data);
        triggerAnyCallbacks(data);
      });
    });

    // Store cleanup function
    subscriptions.set(key, () => {
      echo().leave(channelName);
    });
  };

  /**
   * Disconnect all channels and cleanup.
   */
  const disconnectAll = () => {
    subscriptions.forEach((cleanup) => cleanup());
    subscriptions.clear();
    onlineUsersByProject.clear();
  };

  /**
   * Get online users for a specific project.
   */
  const getOnlineUsers = (projectId: number): Readonly<Ref<OnlineUser[]>> => {
    return computed(() => onlineUsersByProject.get(projectId) || []);
  };

  /**
   * Get presence info for a specific project.
   */
  const getPresenceInfo = (projectId: number): Readonly<Ref<PresenceInfo>> => {
    return computed(() => {
      const users = onlineUsersByProject.get(projectId) || [];
      return {
        users,
        count: users.length,
      };
    });
  };

  // Initialize channels on mount
  onMounted(() => {
    initializeChannels();
  });

  // Cleanup on unmount
  onUnmounted(() => {
    disconnectAll();
  });

  return {
    onMessage: registerMessageCallback,
    onAnyMessage: registerAnyCallback,
    onProjectMessage: registerProjectCallback,
    onGlobalMessage: registerGlobalCallback,
    onPrivateMessage: registerPrivateCallback,
    getOnlineUsers,
    getPresenceInfo,
    disconnectAll,
  };
}

/**
 * Callback storage for different message types.
 */
type MessageCallback = (data: BroadcastMessage) => void;
type ProjectMessageCallback = (data: BroadcastMessage, projectId: number) => void;

const messageCallbacks = new Map<BroadcastMessageType, Set<MessageCallback>>();
const anyCallbacks = new Set<MessageCallback>();
const projectCallbacks = new Set<ProjectMessageCallback>();
const globalCallbacks = new Set<MessageCallback>();
const privateCallbacks = new Set<MessageCallback>();

/**
 * Register a callback for a specific message type.
 */
function registerMessageCallback(
  type: BroadcastMessageType,
  callback: MessageCallback,
): () => void {
  if (!messageCallbacks.has(type)) {
    messageCallbacks.set(type, new Set());
  }
  messageCallbacks.get(type)!.add(callback);

  // Return cleanup function
  return () => {
    messageCallbacks.get(type)?.delete(callback);
  };
}

/**
 * Register a callback for any message.
 */
function registerAnyCallback(callback: MessageCallback): () => void {
  anyCallbacks.add(callback);
  return () => {
    anyCallbacks.delete(callback);
  };
}

/**
 * Register a callback for project channel messages.
 */
function registerProjectCallback(callback: ProjectMessageCallback): () => void {
  projectCallbacks.add(callback);
  return () => {
    projectCallbacks.delete(callback);
  };
}

/**
 * Register a callback for global channel messages.
 */
function registerGlobalCallback(callback: MessageCallback): () => void {
  globalCallbacks.add(callback);
  return () => {
    globalCallbacks.delete(callback);
  };
}

/**
 * Register a callback for private channel messages.
 */
function registerPrivateCallback(callback: MessageCallback): () => void {
  privateCallbacks.add(callback);
  return () => {
    privateCallbacks.delete(callback);
  };
}

/**
 * Trigger callbacks for a specific message type.
 */
function triggerMessageCallbacks(message: BroadcastMessage): void {
  const callbacks = messageCallbacks.get(message.type);
  if (callbacks) {
    callbacks.forEach((callback) => callback(message));
  }
}

/**
 * Trigger callbacks for any message.
 */
function triggerAnyCallbacks(message: BroadcastMessage): void {
  anyCallbacks.forEach((callback) => callback(message));
}

/**
 * Trigger callbacks for project channel messages.
 */
function triggerProjectCallbacks(message: BroadcastMessage, projectId: number): void {
  projectCallbacks.forEach((callback) => callback(message, projectId));
}

/**
 * Trigger callbacks for global channel messages.
 */
function triggerGlobalCallbacks(message: BroadcastMessage): void {
  globalCallbacks.forEach((callback) => callback(message));
}

/**
 * Trigger callbacks for private channel messages.
 */
function triggerPrivateCallbacks(message: BroadcastMessage): void {
  privateCallbacks.forEach((callback) => callback(message));
}
