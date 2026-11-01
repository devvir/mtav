import type { ArchivedItem } from '@/components/domain/Archive';
import { DateTime } from '@/components/feedback/DateTime';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { Button, Group, Modal, Stack, Text } from '@mantine/core';
import { IconTrash } from '@tabler/icons-react';

/** Which archived family this is: who was in it and when, before deleting it for good */
export function ArchivedFamilyHistory({
  family,
  onClose,
}: {
  family: ArchivedItem | null;
  onClose: () => void;
}) {
  const remove = async (id: number, name: string) => {
    const ok = await confirm({
      title: _('Delete the {name} family for good?', { name }),
      message: _('It cannot be brought back. Past lottery records still show its place.'),
      confirmLabel: _('Delete for good'),
      danger: true,
    });
    if (ok)
      router.delete(route('manage.archive.families.destroy', id), {
        preserveScroll: true,
        onSuccess: onClose,
      });
  };

  return (
    <Modal
      opened={Boolean(family)}
      onClose={onClose}
      title={_('{family} family', { family: family?.title ?? '' })}
    >
      {family ? (
        <Stack>
          <Group grow>
            <div>
              <Text className="ds-caption">{_('Family created')}</Text>
              <DateTime value={family.created_at!} time={false} />
            </div>
            <div>
              <Text className="ds-caption">{_('Archived')}</Text>
              <DateTime value={family.deleted_at} time={false} />
            </div>
          </Group>
          <div>
            <Text className="ds-caption">{_('Who was in it')}</Text>
            {family.people?.length ? (
              family.people.map((person) => <Text key={person}>{person}</Text>)
            ) : (
              <Text>{_('Nobody on record')}</Text>
            )}
          </div>
          <Group justify="flex-end">
            <Button
              variant="default"
              c="red"
              leftSection={<IconTrash size={18} />}
              onClick={() => void remove(family.id, family.title)}
            >
              {_('Delete for good')}
            </Button>
          </Group>
        </Stack>
      ) : null}
    </Modal>
  );
}
