import { PersonAvatar } from '@/design';
import { Avatar } from '@mantine/core';
import { AssistantFace } from './AssistantFace';
import type { ConversationSummary } from './types';

/** A conversation's faces: the other person, or a group's first few, overlapping */
export function Faces({ conversation }: { conversation: ConversationSummary }) {
  if (conversation.is_assistant) return <AssistantFace />;
  if (!conversation.faces.length) return <Avatar radius="xl" size={40} />;
  if (!conversation.is_group) {
    const [person] = conversation.faces;
    return <PersonAvatar name={person.name} src={person.avatar} size={40} />;
  }
  return (
    <Avatar.Group spacing="md">
      {conversation.faces.slice(0, 2).map((person) => (
        <PersonAvatar key={person.id} name={person.name} src={person.avatar} size={32} />
      ))}
    </Avatar.Group>
  );
}
