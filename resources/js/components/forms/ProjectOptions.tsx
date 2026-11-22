import { _ } from '@/lib/translations';
import type { ProjectFeature } from '@/types/models';
import {
  Checkbox,
  Collapse,
  Radio,
  SimpleGrid,
  Stack,
  Switch,
  Text,
  UnstyledButton,
} from '@mantine/core';
import { IconChevronDown } from '@tabler/icons-react';
import { type ReactNode, useState } from 'react';

export interface ProjectOptionValues {
  listed: boolean;
  open_to_join: boolean;
  allows_guests: boolean;
  feature_choices: Partial<Record<ProjectFeature, boolean>>;
}

type OptionProps = {
  values: ProjectOptionValues;
  onChange: <K extends keyof ProjectOptionValues>(key: K, value: ProjectOptionValues[K]) => void;
  errors: Partial<Record<keyof ProjectOptionValues, string>>;
};

/**
 * What a project has, the choice every admin makes: asked when it starts, and changeable in
 * its settings. Turning a feature off hides it and keeps what it holds.
 */
export function ProjectFeatures({ values, onChange }: Omit<OptionProps, 'errors'>) {
  const features: { value: ProjectFeature; label: string }[] = [
    { value: 'events', label: _('Events') },
    { value: 'gallery', label: _('Gallery (photos and videos)') },
    { value: 'files', label: _('Files (documents and recordings)') },
    { value: 'forum', label: _('Forum') },
    { value: 'chat', label: _('Chat') },
    { value: 'assistant', label: _('Chat assistant') },
  ];
  // What this deployment lets a project choose: the rest is not a question here
  const offered = features.filter((feature) => feature.value in values.feature_choices);

  if (!offered.length) return null;

  return (
    <div data-tour="settings.features">
      <Text fw={650} size="sm" mb="xs">
        {_('What it has')}
      </Text>
      <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="xs">
        {offered.map((feature) => (
          <Checkbox
            key={feature.value}
            label={feature.label}
            checked={values.feature_choices[feature.value] === true}
            // Its assistant lives in the chat
            disabled={feature.value === 'assistant' && values.feature_choices.chat === false}
            onChange={(event) =>
              onChange('feature_choices', {
                ...values.feature_choices,
                [feature.value]: event.currentTarget.checked,
              })
            }
          />
        ))}
      </SimpleGrid>
    </div>
  );
}

/**
 * Who can find a project and take part in it: the rarer choices, kept apart as advanced
 * settings so that they do not stand in the way of the common ones.
 */
export function ProjectAccess({
  values,
  onChange,
  errors,
  approved,
}: OptionProps & {
  /** Not yet: it will only show in the directory once it is */
  approved: boolean;
}) {
  return (
    <Stack gap="md" data-tour="settings.listed">
      <Switch
        label={_('Show it in Find a project')}
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
          'People who take part in everything but the lottery, until they join their family. Without guests, whoever enters joins their family first.',
        )}
        checked={values.allows_guests}
        error={errors.allows_guests}
        onChange={(event) => onChange('allows_guests', event.currentTarget.checked)}
      />
    </Stack>
  );
}

/**
 * The settings few need, folded at the end of a form: the common ones come first and stay in
 * view; whoever wants to go deeper opens these.
 */
export function AdvancedSettings({ children, tour }: { children: ReactNode; tour?: string }) {
  const [open, setOpen] = useState(false);

  return (
    <div data-tour={tour}>
      <UnstyledButton
        onClick={() => setOpen((value) => !value)}
        aria-expanded={open}
        className="advanced-settings-toggle"
      >
        <Text component="span" fw={650} size="sm">
          {_('Advanced settings')}
        </Text>
        <IconChevronDown
          size={16}
          style={{ transition: 'transform 200ms', transform: open ? 'rotate(180deg)' : undefined }}
        />
      </UnstyledButton>
      <Text size="sm" c="dimmed" mt={2}>
        {_('Who can find the project and join it, and whether it takes guests.')}
      </Text>
      <Collapse expanded={open} transitionDuration={250}>
        <div style={{ paddingTop: 16 }}>{children}</div>
      </Collapse>
    </div>
  );
}

/** A project's options as a form holds them: of its features, those this deployment lets it choose */
export const optionsOf = (
  choices: Partial<Record<ProjectFeature, boolean>>,
  project?: {
    listed: boolean;
    open_to_join: boolean;
    allows_guests: boolean;
    features: Record<ProjectFeature, boolean>;
  },
): ProjectOptionValues => ({
  listed: project?.listed ?? false,
  open_to_join: project?.open_to_join ?? false,
  allows_guests: project?.allows_guests ?? false,
  feature_choices: project
    ? Object.fromEntries(
        Object.keys(choices).map((name) => [name, project.features[name as ProjectFeature]]),
      )
    : choices,
});
