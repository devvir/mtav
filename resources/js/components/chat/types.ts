/** What the chat reads from the server: its conversations, the one open and its messages */

export interface ChatPerson {
  id: number;
  name: string;
  avatar: string | null;
}

export interface ConversationSummary {
  id: number;
  title: string;
  is_group: boolean;
  is_community: boolean;
  is_assistant?: boolean;
  /** It was asked, and has not said anything yet */
  answering?: boolean;
  faces: ChatPerson[];
  last: { body: string; author: string | null; mine: boolean; at: string } | null;
  unread?: boolean;
  pinned?: boolean;
}

export interface ChatMessage {
  id: number;
  body: string;
  author: ChatPerson | null;
  mine: boolean;
  from_assistant?: boolean;
  actions?: AssistantAction[];
  created_at: string;
  /** Theirs: until when they may still edit or delete it */
  changeable_until?: string | null;
}

/**
 * A button under an Assistant's message: to a place (its tour too), a reply sent with a tap,
 * writing to one of the project's admins, or a report for the platform's team
 */
export interface AssistantAction {
  type: 'link' | 'tour' | 'reply' | 'message' | 'report';
  label: string;
  href?: string;
  tour?: string;
  /** What a "report" says, as it will be sent; `sent` once it was */
  message?: string;
  sent?: boolean;
}

export type OpenConversation = ConversationSummary & {
  name: string | null;
  people: ChatPerson[];
  can: { update: boolean; leave: boolean };
};

export interface ChatData {
  conversations: ConversationSummary[];
  conversation: OpenConversation | null;
  messages: { data: ChatMessage[]; earlier: boolean } | null;
  people: ChatPerson[];
}
