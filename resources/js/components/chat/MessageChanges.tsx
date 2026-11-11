import { _ } from '@/lib/translations';
import { ActionIcon, Group, Transition } from '@mantine/core';
import { IconPencil, IconTrash } from '@tabler/icons-react';

/**
 * One's own message's edit and delete buttons: only within the hour after sending, going by
 * themselves as it closes. With the Assistant there is no editing: its answer would stay as it was.
 */
export function MessageChanges({
  shown,
  editable,
  onEdit,
  onDelete,
}: {
  shown: boolean;
  editable: boolean;
  onEdit: () => void;
  onDelete: () => void;
}) {
  return (
    <Transition mounted={shown} transition="fade" duration={220}>
      {(style) => (
        <div style={style}>
          <Group gap={2} wrap="nowrap" className="chat-changes">
            {editable ? (
              <ActionIcon
                variant="subtle"
                color="gray"
                size="sm"
                className="chat-change"
                aria-label={_('Edit this message')}
                onClick={onEdit}
              >
                <IconPencil size={14} />
              </ActionIcon>
            ) : null}
            <ActionIcon
              variant="subtle"
              color="gray"
              size="sm"
              className="chat-change"
              aria-label={_('Delete this message')}
              onClick={onDelete}
            >
              <IconTrash size={14} />
            </ActionIcon>
          </Group>
        </div>
      )}
    </Transition>
  );
}
