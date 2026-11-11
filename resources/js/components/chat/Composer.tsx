import { _ } from '@/lib/translations';
import { ActionIcon, Textarea } from '@mantine/core';
import { IconSend2 } from '@tabler/icons-react';

/** Where a new message is written: Enter sends it, Shift+Enter starts a new line */
export function Composer({
  body,
  error,
  sending,
  onType,
  onSend,
}: {
  body: string;
  error?: string;
  sending: boolean;
  onType: (body: string) => void;
  onSend: () => void;
}) {
  return (
    <form
      className="chat-composer"
      onSubmit={(event) => {
        event.preventDefault();
        onSend();
      }}
      data-tour="chat.composer"
    >
      <Textarea
        aria-label={_('Message')}
        placeholder={_('Write a message')}
        autosize
        minRows={1}
        maxRows={6}
        style={{ flex: 1 }}
        value={body}
        error={error}
        onChange={(event) => onType(event.currentTarget.value)}
        onKeyDown={(event) => {
          if (event.key === 'Enter' && !event.shiftKey && !event.nativeEvent.isComposing) {
            event.preventDefault();
            onSend();
          }
        }}
      />
      <ActionIcon
        type="submit"
        size={42}
        radius="xl"
        variant="filled"
        loading={sending}
        disabled={!body.trim()}
        aria-label={_('Send')}
      >
        <IconSend2 size={20} />
      </ActionIcon>
    </form>
  );
}
