import { useChatProject } from '@/lib/chat';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Text } from '@mantine/core';
import { AssistantActions } from './AssistantActions';
import { AssistantFace } from './AssistantFace';
import { assistantGreeting } from './assistantGreeting';

/**
 * How its conversation starts, always: what it is, and before the first message, a few things
 * to ask with a tap
 */
export function AssistantWelcome({
  onAsk,
  asking,
}: {
  onAsk: (words: string) => void;
  asking: boolean;
}) {
  const { project } = useChatProject();
  const can = project?.can;
  const asks = [
    _('What can I do here?'),
    _('How does the lottery work?'),
    ...(can?.rank ? [_('How do I rank the units?')] : []),
    ...(can?.manage ? [_('What is left to set up?')] : []),
    ...(can?.invite && !can?.manage ? [_('How do I invite my family?')] : []),
  ];

  return (
    <div className="chat-message" data-assistant>
      <AssistantFace size={28} />
      <div className="chat-bubble">
        <Text component="div" className="chat-body">
          {assistantGreeting()}
        </Text>
      </div>
      {asking ? (
        <AssistantActions
          actions={[
            ...(project && !project.needs_family
              ? [
                  {
                    type: 'tour' as const,
                    label: _('Show me around'),
                    href: route('projects.show', { project: project.id }),
                    tour: 'member.home',
                  },
                ]
              : []),
            ...asks.map((label) => ({ type: 'reply' as const, label })),
          ]}
          onReply={onAsk}
        />
      ) : null}
    </div>
  );
}
