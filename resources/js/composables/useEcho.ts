// Copilot - Pending review
import { echo } from '@laravel/echo-vue';
import type { Channel } from 'laravel-echo';

/**
 * Composable for using Laravel Echo with abstraction layer.
 * Provides a clean API for listening to broadcast events without
 * coupling components directly to Echo's implementation details.
 */
export function useEcho() {
  /**
   * Listen to a public channel event.
   * @param channelName - The channel name (e.g., 'projects')
   * @param eventName - The event class name (e.g., 'ProjectCreated' or 'App\\Events\\ProjectCreated')
   * @param callback - The callback to execute when the event is received
   * @returns A function to stop listening
   */
  function listen(
    channelName: string,
    eventName: string,
    callback: (data: any) => void,
  ): () => void {
    const channel: Channel = echo().channel(channelName);

    console.log('[useEcho] Registering listener:', {
      channelName,
      eventName,
    });

    channel.listen(eventName, (data: any) => {
      console.log('[useEcho] Event received!', { eventName, data });
      callback(data);
    });

    // Return cleanup function
    return () => {
      channel.stopListening(eventName);
      echo().leave(channelName);
    };
  }

  /**
   * Listen to a private channel event.
   * @param channelName - The channel name (e.g., 'project.1')
   * @param eventName - The event class name (e.g., 'ProjectUpdated' or 'App\\Events\\ProjectUpdated')
   * @param callback - The callback to execute when the event is received
   * @returns A function to stop listening
   */
  function listenPrivate(
    channelName: string,
    eventName: string,
    callback: (data: any) => void,
  ): () => void {
    const channel: Channel = echo().private(channelName);

    // If eventName doesn't include namespace, assume it's in App\Events
    const fullEventName = eventName.includes('\\')
      ? eventName
      : `App\\Events\\${eventName}`;

    channel.listen(fullEventName, callback);

    // Return cleanup function
    return () => {
      channel.stopListening(fullEventName);
      echo().leave(`private-${channelName}`);
    };
  }

  /**
   * Listen to a presence channel event.
   * @param channelName - The channel name (e.g., 'project.1')
   * @param eventName - The event class name (e.g., 'ProjectUpdated' or 'App\\Events\\ProjectUpdated')
   * @param callback - The callback to execute when the event is received
   * @returns A function to stop listening
   */
  function listenPresence(
    channelName: string,
    eventName: string,
    callback: (data: any) => void,
  ): () => void {
    const channel: Channel = echo().join(channelName);

    // If eventName doesn't include namespace, assume it's in App\Events
    const fullEventName = eventName.includes('\\')
      ? eventName
      : `App\\Events\\${eventName}`;

    channel.listen(fullEventName, callback);

    // Return cleanup function
    return () => {
      channel.stopListening(fullEventName);
      echo().leave(`presence-${channelName}`);
    };
  }

  return {
    listen,
    listenPrivate,
    listenPresence,
  };
}
