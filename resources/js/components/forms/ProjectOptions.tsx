import { _ } from '@/lib/translations';
import type { ProjectFeature } from '@/types/models';
import { Checkbox, Collapse, Radio, SimpleGrid, Stack, Switch, Text } from '@mantine/core';

export interface ProjectOptionValues {
  listed: boolean;
  open_to_join: boolean;
  allows_guests: boolean;
  disabled_features: ProjectFeature[];
}

/**
 * Who can find a project and take part in it, and what it has: asked when it starts, and
 * changeable in its settings. Turning a feature off hides it and keeps what it holds.
 */
export function ProjectOptions({
  values,
  onChange,
  errors,
  approved,
}: {
  values: ProjectOptionValues;
  onChange: <K extends keyof ProjectOptionValues>(key: K, value: ProjectOptionValues[K]) => void;
  errors: Partial<Record<keyof ProjectOptionValues, string>>;
  /** Not yet: it will only show in the directory once it is */
  approved: boolean;
}) {
  const features: { value: ProjectFeature; label: string }[] = [
    { value: 'events', label: _('Events') },
    { value: 'gallery', label: _('Gallery (photos and videos)') },
    { value: 'files', label: _('Files (documents and recordings)') },
    { value: 'forum', label: _('Forum') },
    { value: 'chat', label: _('Chat') },
  ];

  return (
    <Stack gap="md" data-tour="settings.listed">
      <Switch
        label={_('List it in the directory')}
        description={
          approved
            ? _('Anyone with an account can find it, and read its name and description.')
            : _('It shows there once the project is approved.')
        }
        checked={values.listed}
        onChange={(event) => onChange('listed', event.currentTarget.checked)}
      />
      <Collapse expanded={values.listed} transitionDuration={250}>
        <Radio.Group
          label={_('Who can join')}
          value={values.open_to_join ? 'open' : 'request'}
          onChange={(value) => onChange('open_to_join', value === 'open')}
        >
          <Stack gap="xs" mt="xs">
            <Radio value="request" label={_('Those the admins accept')} />
            <Radio value="open" label={_('Anyone, at once')} />
          </Stack>
        </Radio.Group>
      </Collapse>
      <Switch
        label={_('Allow guests')}
        description={_(
          'People with no family, who take part in everything but the lottery. Without guests, whoever joins creates their family first.',
        )}
        checked={values.allows_guests}
        error={errors.allows_guests}
        onChange={(event) => onChange('allows_guests', event.currentTarget.checked)}
      />
      <div data-tour="settings.features">
        <Text fw={650} size="sm" mb="xs">
          {_('What it has')}
        </Text>
        <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="xs">
          {features.map((feature) => (
            <Checkbox
              key={feature.value}
              label={feature.label}
              checked={!values.disabled_features.includes(feature.value)}
              onChange={(event) =>
                onChange(
                  'disabled_features',
                  event.currentTarget.checked
                    ? values.disabled_features.filter((off) => off !== feature.value)
                    : [...values.disabled_features, feature.value],
                )
              }
            />
          ))}
        </SimpleGrid>
      </div>
    </Stack>
  );
}

/** A project's options as a form holds them, from its features' on and off */
export const optionsOf = (project?: {
  listed: boolean;
  open_to_join: boolean;
  allows_guests: boolean;
  features: Record<ProjectFeature, boolean>;
}): ProjectOptionValues => ({
  listed: project?.listed ?? false,
  open_to_join: project?.open_to_join ?? false,
  allows_guests: project?.allows_guests ?? false,
  disabled_features: project
    ? (Object.entries(project.features)
        .filter(([, on]) => !on)
        .map(([feature]) => feature) as ProjectFeature[])
    : [],
});
