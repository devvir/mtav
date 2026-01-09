// Copilot - Pending review

/**
 * Type definitions for broadcasting system.
 */

export type BroadcastChannel = 'private' | 'projects' | 'global';

export type BroadcastMessageType =
  | 'resource.created'
  | 'resource.updated'
  | 'resource.deleted'
  | 'user.joined'
  | 'user.left'
  | 'user.typing'
  | 'user.navigation'
  | 'notification'
  | 'system.message'
  | 'lottery.started'
  | 'lottery.completed';

export interface BroadcastMessage<T = any> {
  type: BroadcastMessageType;
  data: T;
  metadata: {
    timestamp: string;
    [key: string]: any;
  };
}

export interface OnlineUser {
  id: number;
  name: string;
  joinedAt: Date;
}

export interface PresenceInfo {
  users: OnlineUser[];
  count: number;
}
