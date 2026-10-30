import { Chat, type ChatData } from '@/components/domain/Chat';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { showConversation } from '@/lib/chat';
import { _ } from '@/lib/translations';
import { useLayoutEffect } from 'react';

/** The chat as a page: where phones have it, and where a link to a conversation leads */
export default function ChatIndex({ conversations, conversation, messages, people }: ChatData) {
  // The conversation its address names opens right away
  useLayoutEffect(() => showConversation(conversation?.id ?? null), [conversation?.id]);

  return (
    <>
      <PageHeader
        tour="member.chat"
        title={_('Messages')}
        description={_('Conversations with the people of the project, one on one or in groups.')}
        help={
          <InfoTip title={_('Messages')} tour="member.chat">
            {_(
              'Only those in a conversation read it: nobody else, the admins included. For what concerns everyone, the forum is the place.',
            )}
          </InfoTip>
        }
      />
      <Chat initial={{ conversations, conversation, messages, people }} />
    </>
  );
}
