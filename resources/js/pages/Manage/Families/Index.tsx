import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, FamilyMark } from '@/design';
import { confirm } from '@/lib/dialogs';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Family, Paginated, Ref } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { ActionIcon, Button, Card, Group, Text } from '@mantine/core';
import { IconPencil, IconPlus, IconTrash, IconUserPlus, IconUsersGroup } from '@tabler/icons-react';

interface Props {
  families: Paginated<Family>;
  unitTypes: Ref[];
  filters: { q: string | null; unit_type: number | null };
}

export default function ManageFamilies({ families, filters }: Props) {
  useProjectChanges({ families: ['families'], people: ['families'] });

  const remove = async (family: Family) => {
    const ok = await confirm({
      title: _('Remove the {name} family?', { name: family.name }),
      message: _('Its members leave the project (admins stay admins). This cannot be undone.'),
      confirmLabel: _('Remove'),
      danger: true,
    });
    if (ok) router.delete(route('manage.families.destroy', family.id), { preserveScroll: true });
  };

  return (
    <>
      <PageHeader
        title={_('Families')}
        help={
          <InfoTip title={_('What is a family?')}>
            {_(
              'The lottery participant: its members share one ranking, and it receives one unit of its type.',
            )}
          </InfoTip>
        }
        actions={
          <Button
            component={Link}
            href={route('manage.families.create')}
            leftSection={<IconPlus size={18} />}
          >
            {_('Add a family')}
          </Button>
        }
      />
      <div style={{ marginBottom: 16 }}>
        <QuerySearch value={filters.q} placeholder={_('Search families or people')} maw={440} />
      </div>
      {families.data.length ? (
        <Card p={0}>
          {families.data.map((family) => (
            <Group key={family.id} className="row" gap="md" wrap="wrap">
              <FamilyMark name={family.name} size={40} />
              <div style={{ flex: 1, minWidth: 200 }}>
                <Text fw={700}>{family.name}</Text>
                <Text className="ds-caption">
                  {family.unit_type?.name} ·{' '}
                  {_n('{count} person', '{count} people', family.members_count ?? 0)}
                  {family.unit ? (
                    <>
                      {' '}
                      · <span className="text-positive">{family.unit.identifier}</span>
                    </>
                  ) : null}
                </Text>
              </div>
              <Group gap={4}>
                <Button
                  variant="default"
                  size="xs"
                  component={Link}
                  href={route('manage.invitations.index', { family: family.id })}
                  leftSection={<IconUserPlus size={14} />}
                >
                  {_('Invite')}
                </Button>
                <ActionIcon
                  size="lg"
                  component={Link}
                  href={route('manage.families.edit', family.id)}
                  aria-label={_('Edit {name}', { name: family.name })}
                >
                  <IconPencil size={18} />
                </ActionIcon>
                <ActionIcon
                  size="lg"
                  color="red"
                  disabled={Boolean(family.unit)}
                  aria-label={_('Remove {name}', { name: family.name })}
                  onClick={() => remove(family)}
                >
                  <IconTrash size={18} />
                </ActionIcon>
              </Group>
            </Group>
          ))}
        </Card>
      ) : (
        <EmptyState
          icon={IconUsersGroup}
          title={_('No families yet')}
          action={
            <Button component={Link} href={route('manage.families.create')}>
              {_('Add the first one')}
            </Button>
          }
        />
      )}
      <Pagination page={families} />
    </>
  );
}
