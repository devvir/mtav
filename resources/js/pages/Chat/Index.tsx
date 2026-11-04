import { Chat, type ChatData } from '@/components/domain/Chat';
import { PageHeader } from '@/components/layout/PageHeader';
import { showConversation } from '@/lib/chat';
import { _ } from '@/lib/translations';
import { useLayoutEffect } from 'react';

/**
 * The chat outside any project, as a page (where phones have it): the person's own
 * conversation with the Assistant, open from the start. Inside a project, its people are there.
 */
export default function OwnChat({ conversations, conversation, messages, people }: ChatData) {
  useLayoutEffect(() => showConversation(conversation?.id ?? null), [conversation?.id]);

  return (
    <>
      <PageHeader
        title={_('Messages')}
        description={_(
          'The Assistant, here to help. Inside a project, you also talk with its people.',
        )}
      />
      <Chat initial={{ conversations, conversation, messages, people }} />
    </>
  );
}
