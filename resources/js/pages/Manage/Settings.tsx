import { Form, FormActions } from '@/components/forms/Form';
import { OrganizationField } from '@/components/forms/OrganizationField';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { SettingsTabs } from '@/components/layout/SettingsTabs';
import { Glyph } from '@/design';
import { confirm } from '@/lib/dialogs';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Project } from '@/types/models';
import { router, useForm } from '@inertiajs/react';
import { Button, Card, Group, Text, TextInput, Textarea, Title } from '@mantine/core';
import { IconAlertTriangle, IconDownload, IconTableExport, IconTrash } from '@tabler/icons-react';

/** The project's details, and deleting it (its page prop is `settings`: `project` is shared) */
export default function Settings({ settings: project }: { settings: Project }) {
  const form = useForm({
    name: project.name,
    description: project.description,
    organization: project.organization ?? '',
  });

  const destroy = async () => {
    const ok = await confirm({
      title: _('Delete {project}?', { project: project.name }),
      message: _(
        'Everything in it goes: families, units, files, the lottery and its records. This cannot be undone.',
      ),
      typeToConfirm: project.name,
      confirmLabel: _('Delete the project'),
      danger: true,
    });
    if (ok)
      router.delete(route('manage.settings.destroy'), { data: { confirmation: project.name } });
  };

  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Project settings')} tour="manage.settings">
            {_('Its name, what it is about and its organization; and, with care, deleting it.')}
          </InfoTip>
        }
        title={_('Settings')}
        description={_('Its name, what it is about and its organization.')}
        tour="manage.settings"
      />
      <SettingsTabs />
      <Card p="lg" maw={720} data-tour="settings.form">
        <Form onSubmit={() => form.put(route('manage.settings.update'), { preserveScroll: true })}>
          <TextInput label={_('Name')} withAsterisk {...field(form, 'name')} />
          <Textarea
            label={_('Description')}
            withAsterisk
            autosize
            minRows={3}
            {...field(form, 'description')}
          />
          <OrganizationField
            value={form.data.organization}
            onChange={(organization) => form.setData('organization', organization)}
            error={form.errors.organization}
          />
          <FormActions submit={_('Save')} processing={form.processing} />
        </Form>
      </Card>
      <Card p="lg" maw={720} mt="xl" data-tour="settings.export">
        <Group gap="sm">
          <Glyph icon={IconTableExport} />
          <Title order={2} fz="lg">
            {_('Download its lists')}
          </Title>
        </Group>
        <Text c="dimmed" mt="sm">
          {_('As spreadsheets, to open in Excel, LibreOffice or Google Sheets.')}
        </Text>
        <Group gap="sm" mt="md">
          {[
            [_('People'), route('manage.export.people')],
            [_('Families'), route('manage.export.families')],
            [_('Units'), route('manage.export.units')],
          ].map(([label, href]) => (
            <Button
              key={href}
              component="a"
              href={href}
              download
              variant="default"
              leftSection={<IconDownload size={18} />}
            >
              {label}
            </Button>
          ))}
        </Group>
      </Card>
      <Card p="lg" maw={720} mt="xl" className="danger-card" data-tour="settings.delete">
        <Group gap="sm">
          <Glyph icon={IconAlertTriangle} tone="negative" />
          <Title order={2} fz="lg" c="var(--mtav-negative)">
            {_('Delete the project')}
          </Title>
        </Group>
        <Text c="dimmed" mt="sm">
          {_('Removes the project and everything in it, for everyone.')}
        </Text>
        <Button color="red" mt="md" leftSection={<IconTrash size={18} />} onClick={destroy}>
          {_('Delete the project')}
        </Button>
      </Card>
    </>
  );
}
