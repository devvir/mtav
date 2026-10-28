import { Form, FormActions } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { Glyph } from '@/design';
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
        title={_('Start a project')}
        description={_(
          'You will be its first admin, and can invite the rest of its people afterwards.',
        )}
        back={{ href: route('projects.index'), label: _('Your projects') }}
      />
      <div className="with-aside">
        <Card p="lg">
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
            <TextInput
              label={_('Organization')}
              description={_('Federation or institution, if any')}
              {...field(form, 'organization')}
            />
            <FormActions
              submit={_('Start the project')}
              processing={form.processing}
              cancelHref={route('projects.index')}
            />
          </Form>
        </Card>
        {limits ? (
          <Card p="lg" h="fit-content">
            <Glyph icon={IconInfoCircle} tone="info" />
            <Title order={2} fz="md" mt="sm">
              {_('Until it is approved')}
            </Title>
            <Text size="sm" c="dimmed" mt={4}>
              {_(
                'New projects work within some limits, which a superadmin lifts by approving the project. You can ask for approval from the project at any time.',
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
                {_('Files up to {size} MB, {total} MB in all', {
                  size: limits.media_file_mb,
                  total: limits.media_storage_mb,
                })}
              </List.Item>
            </List>
          </Card>
        ) : null}
      </div>
    </>
  );
}
