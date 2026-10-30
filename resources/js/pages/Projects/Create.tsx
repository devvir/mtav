import { Form, FormActions } from '@/components/forms/Form';
import { OrganizationField } from '@/components/forms/OrganizationField';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Glyph } from '@/design';
import { appName, megabytes } from '@/lib/app';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { useForm } from '@inertiajs/react';
import { Card, List, Text, TextInput, Textarea, Title } from '@mantine/core';
import { IconInfoCircle } from '@tabler/icons-react';

export default function ProjectsCreate({ limits }: { limits: Record<string, number> | null }) {
  const form = useForm({ name: '', description: '', organization: '' });

  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Starting a project')} tour="account.start_project">
            {_(
              'You become its first admin, then set it up step by step: unit types, units, families, invitations.',
            )}
          </InfoTip>
        }
        title={_('Start a project')}
        tour="account.start_project"
        description={_(
          'You will be its first admin, and can invite the rest of its people afterwards.',
        )}
        back={{ href: route('projects.index'), label: _('Your projects') }}
      />
      <div className="with-aside">
        <Card p="lg" data-tour="start.form">
          <Form onSubmit={() => form.post(route('projects.store'))}>
            <TextInput
              label={_('Name')}
              description={_('As its people call it')}
              withAsterisk
              autoFocus
              {...field(form, 'name')}
            />
            <Textarea
              label={_('Description')}
              withAsterisk
              autosize
              minRows={3}
              placeholder={_('What the project is about, how many dwellings…')}
              {...field(form, 'description')}
            />
            <OrganizationField
              description={_('Federation or institution, if any')}
              value={form.data.organization}
              onChange={(organization) => form.setData('organization', organization)}
              error={form.errors.organization}
            />
            <FormActions
              submit={_('Start the project')}
              processing={form.processing}
              cancelHref={route('projects.index')}
            />
          </Form>
        </Card>
        {limits ? (
          <Card p="lg" h="fit-content" data-tour="start.room">
            <Glyph icon={IconInfoCircle} tone="info" />
            <Title order={2} fz="md" mt="sm">
              {_('Room for the project')}
            </Title>
            <Text size="sm" c="dimmed" mt={4}>
              {_(
                'Every project starts limited, with room for most cooperatives. The {app} team can make yours unlimited: ask from the project whenever you like.',
                { app: appName() },
              )}
            </Text>
            <List size="sm" mt="sm" spacing={4}>
              <List.Item>{_('Up to {count} people', { count: limits.members })}</List.Item>
              <List.Item>
                {_('Up to {count} families and {units} units', {
                  count: limits.families,
                  units: limits.units,
                })}
              </List.Item>
              <List.Item>
                {_('Up to {count} invitations a day', { count: limits.invitations_per_day })}
              </List.Item>
              <List.Item>
                {_('Files up to {size}, {total} in all', {
                  size: megabytes(limits.media_file_mb),
                  total: megabytes(limits.media_storage_mb),
                })}
              </List.Item>
            </List>
          </Card>
        ) : null}
      </div>
    </>
  );
}
