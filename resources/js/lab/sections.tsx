import {
  EmptyState,
  FamilyMark,
  FileDrop,
  Glyph,
  NavItem,
  Panel,
  PersonAvatar,
  Stat,
  Status,
  type Tone,
} from '@/design';
import { confirm, prompt } from '@/lib/dialogs';
import { toast } from '@/lib/toasts';
import {
  Badge,
  Button,
  Card,
  Checkbox,
  Group,
  PasswordInput,
  SegmentedControl,
  Select,
  SimpleGrid,
  Switch,
  Text,
  TextInput,
  Textarea,
  Title,
} from '@mantine/core';
import {
  IconBell,
  IconCalendarEvent,
  IconDice5,
  IconHome,
  IconLayoutGrid,
  IconMap,
  IconPhoto,
  IconPlus,
  IconSearch,
  IconTrash,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';
import { useState } from 'react';
import { Page, Specimen, StateGrid } from './kit';

/**
 * The UI Lab's sections: every shared component, in its states and variants, drawn by
 * the theme of the moment. A new component gets its specimen here with it.
 */

const TONES: Tone[] = ['brand', 'accent', 'positive', 'warning', 'negative', 'info', 'neutral'];

const TOKENS: [string, string][] = [
  ['--mtav-canvas', 'What a page is drawn on'],
  ['--mtav-surface', 'Cards and panels (glass)'],
  ['--mtav-surface-raised', 'Menus, popovers, what floats'],
  ['--mtav-border', 'Lines and edges'],
  ['--mtav-text', 'Text'],
  ['--mtav-muted', 'Quiet text'],
  ['--mtav-brand', 'The brand, as text and lines'],
  ['--mtav-brand-soft', 'Its soft tint: chosen, hovered'],
  ['--mtav-accent', 'Highlights'],
  ['--mtav-positive', 'Done, approved, good'],
  ['--mtav-warning', 'Needs attention'],
  ['--mtav-negative', 'Failed, removed, refused'],
  ['--mtav-info', 'Worth knowing'],
];

export function Foundations() {
  return (
    <>
      <Page title="Foundations">
        Every colour a page uses, by its token: pages never name a colour, so a palette, a scheme or
        high contrast changes everything at once. Switch them at the top to see them move.
      </Page>
      <Specimen title="Tokens">
        <SimpleGrid cols={{ base: 1, sm: 2 }}>
          {TOKENS.map(([token, use]) => (
            <div key={token} className="swatch-card">
              <span className="swatch-colour" style={{ background: `var(${token})` }} />
              <div>
                <Text ff="monospace" fw={700} size="sm">
                  {token}
                </Text>
                <Text className="ds-caption">{use}</Text>
              </div>
            </div>
          ))}
        </SimpleGrid>
      </Specimen>
      <Specimen
        title="Type"
        note="Bricolage Grotesque for headings, where the app speaks up; Figtree for reading."
      >
        <Title order={1}>A cooperative builds its homes</Title>
        <Title order={2}>Forty dwellings, two blocks</Title>
        <Title order={3}>The lottery is in a month</Title>
        <Text>
          Each family ranks the units of its type; the lottery gives each the best it can.
        </Text>
        <Text className="ds-caption">A caption: secondary words, never under 13px.</Text>
        <Text className="ds-label">A label in capitals</Text>
      </Specimen>
      <Specimen title="Tones" note="What a part says by its colour. Glyphs carry them.">
        <Group>
          {TONES.map((tone) => (
            <Group key={tone} gap="xs">
              <Glyph icon={IconDice5} tone={tone} />
              <Text size="sm">{tone}</Text>
            </Group>
          ))}
        </Group>
      </Specimen>
    </>
  );
}

export function Controls() {
  const [scheme, setScheme] = useState('upcoming');

  return (
    <>
      <Page title="Controls">
        Buttons, fields and choices. One filled button per view: the action that matters; the rest
        lighter.
      </Page>
      <Specimen title="Buttons" wide>
        <StateGrid
          states={['base', 'hover', 'pressed', 'focus', 'disabled', 'loading']}
          rows={[
            {
              label: 'Filled',
              render: (state) => (
                <Button
                  disabled={state === 'disabled'}
                  loading={state === 'loading'}
                  leftSection={<IconPlus size={18} />}
                >
                  Add a unit
                </Button>
              ),
            },
            {
              label: 'Light',
              render: (state) => (
                <Button
                  variant="light"
                  disabled={state === 'disabled'}
                  loading={state === 'loading'}
                >
                  Invite people
                </Button>
              ),
            },
            {
              label: 'Default',
              render: (state) => (
                <Button
                  variant="default"
                  disabled={state === 'disabled'}
                  loading={state === 'loading'}
                >
                  Cancel
                </Button>
              ),
            },
            {
              label: 'Subtle',
              render: (state) => (
                <Button
                  variant="subtle"
                  disabled={state === 'disabled'}
                  loading={state === 'loading'}
                >
                  Back
                </Button>
              ),
            },
            {
              label: 'Danger',
              render: (state) => (
                <Button
                  color="red"
                  disabled={state === 'disabled'}
                  loading={state === 'loading'}
                  leftSection={<IconTrash size={18} />}
                >
                  Remove
                </Button>
              ),
            },
          ]}
        />
      </Specimen>
      <Specimen title="Fields" wide>
        <StateGrid
          states={['base', 'hover', 'focus', 'error', 'disabled']}
          rows={[
            {
              label: 'Text',
              target: 'input',
              render: (state) => (
                <TextInput
                  label="Name"
                  placeholder="Cooperativa Las Acacias"
                  disabled={state === 'disabled'}
                  error={state === 'error' ? 'The name is required.' : undefined}
                  w={220}
                />
              ),
            },
            {
              label: 'Select',
              target: 'input',
              render: (state) => (
                <Select
                  label="Unit type"
                  placeholder="Pick one"
                  data={['2 bedrooms', '3 bedrooms']}
                  disabled={state === 'disabled'}
                  error={state === 'error' ? 'Pick a type.' : undefined}
                  w={220}
                />
              ),
            },
            {
              label: 'Password',
              target: 'input',
              render: (state) => (
                <PasswordInput
                  label="Password"
                  disabled={state === 'disabled'}
                  error={state === 'error' ? 'Too short.' : undefined}
                  w={220}
                />
              ),
            },
          ]}
        />
        <Textarea
          label="Description"
          description="What the project is about"
          autosize
          minRows={2}
          maw={460}
        />
        <Group>
          <Checkbox label="Admin of the project" defaultChecked />
          <Switch label="Allow answers" defaultChecked />
        </Group>
      </Specimen>
      <Specimen
        title="Choices"
        note="A few views of one thing: a segmented control, or pills for lists."
      >
        <SegmentedControl
          value={scheme}
          onChange={setScheme}
          data={[
            { value: 'upcoming', label: 'Upcoming' },
            { value: 'past', label: 'Past' },
          ]}
          w="fit-content"
        />
        <nav className="ds-pills">
          <a className="ds-pill" data-active href="#lab">
            All
          </a>
          <a className="ds-pill" href="#lab">
            Awaiting approval <span className="ds-count">3</span>
          </a>
          <a className="ds-pill" href="#lab">
            Approved
          </a>
        </nav>
      </Specimen>
      <Specimen title="Files">
        <DropSample />
      </Specimen>
    </>
  );
}

function DropSample() {
  const [files, setFiles] = useState<File[]>([]);

  return (
    <FileDrop
      files={files}
      onChange={setFiles}
      multiple
      maxFileBytes={5 * 1024 * 1024}
      words={{
        prompt: 'Choose files or drop them here',
        limit: 'Up to 5 MB each',
        tooLarge: 'Too large',
        refused: (name) => `${name} cannot be shared`,
        remove: (name) => `Remove ${name}`,
      }}
    />
  );
}

export function Feedback() {
  return (
    <>
      <Page title="Feedback">
        States, badges, messages that come and go, and questions asked from code.
      </Page>
      <Specimen title="Status" note="A dot in its tone and its name; what is happening now pulses.">
        <Group>
          <Status tone="positive">Approved</Status>
          <Status tone="warning" live>
            Awaiting approval
          </Status>
          <Status tone="info" live>
            Running
          </Status>
          <Status tone="negative">Failed</Status>
          <Status tone="neutral">Draft</Status>
        </Group>
        <Group>
          {TONES.map((tone) => (
            <Status key={tone} tone={tone} pill>
              {tone}
            </Status>
          ))}
        </Group>
      </Specimen>
      <Specimen title="Badges">
        <Group>
          <Badge>Admin</Badge>
          <Badge color="green">On site</Badge>
          <Badge color="blue">Online</Badge>
          <Badge color="yellow">Draft</Badge>
          <Badge variant="filled">Lottery</Badge>
        </Group>
      </Specimen>
      <Specimen title="Toasts and questions">
        <Group>
          <Button
            variant="default"
            onClick={() => toast({ tone: 'success', message: 'The unit was added.' })}
          >
            Success
          </Button>
          <Button
            variant="default"
            onClick={() => toast({ tone: 'error', message: 'Something went wrong.' })}
          >
            Error
          </Button>
          <Button
            variant="default"
            onClick={() =>
              toast({
                tone: 'info',
                live: true,
                title: 'New event',
                message: 'General assembly on Friday',
              })
            }
          >
            Live notification
          </Button>
          <Button
            variant="default"
            onClick={() =>
              confirm({
                title: 'Remove this family?',
                message: 'Its people stay in the project.',
                danger: true,
                confirmLabel: 'Remove',
              })
            }
          >
            Confirm
          </Button>
          <Button
            variant="default"
            onClick={() =>
              confirm({
                title: 'Delete Cooperativa Las Acacias?',
                typeToConfirm: 'Cooperativa Las Acacias',
                danger: true,
                confirmLabel: 'Delete the project',
              })
            }
          >
            Typed confirmation
          </Button>
          <Button
            variant="default"
            onClick={() =>
              prompt({
                title: 'Invalidate the lottery',
                label: 'Why?',
                multiline: true,
                minLength: 10,
              })
            }
          >
            Prompt
          </Button>
        </Group>
      </Specimen>
    </>
  );
}

export function Surfaces() {
  return (
    <>
      <Page title="Surfaces">Glass panels, stat tiles and cards: what pages are built of.</Page>
      <Specimen title="Stat tiles">
        <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }}>
          <Stat
            label="Families"
            value={48}
            note="44 ranked their units"
            icon={IconUsersGroup}
            tone="brand"
          />
          <Stat label="Units" value={45} note="in 4 types" icon={IconLayoutGrid} tone="accent" />
          <Stat label="People" value={144} icon={IconUsers} tone="info" />
          <Stat
            label="Lottery"
            value="in 12 days"
            note="Nov 27, 10:00"
            icon={IconDice5}
            tone="positive"
          />
        </SimpleGrid>
      </Specimen>
      <Specimen title="Panel">
        <Panel
          title="Coming up"
          icon={IconCalendarEvent}
          aside="the next 30 days"
          actions={
            <Button size="xs" variant="light">
              All events
            </Button>
          }
        >
          <Text c="dimmed">A panel's content: a list, a table (flush), a form.</Text>
        </Panel>
      </Specimen>
      <Specimen title="Cards">
        <SimpleGrid cols={{ base: 1, sm: 2 }}>
          <Card p="lg">
            <Title order={3} fz="lg">
              A card
            </Title>
            <Text c="dimmed" mt={4}>
              Glass on the page's light.
            </Text>
          </Card>
          <Card p="lg" className="ds-interactive" component="a" href="#lab">
            <Title order={3} fz="lg">
              A card that opens something
            </Title>
            <Text c="dimmed" mt={4}>
              It lifts toward the pointer.
            </Text>
          </Card>
        </SimpleGrid>
      </Specimen>
      <Specimen title="Empty">
        <EmptyState
          icon={IconPhoto}
          title="Nothing shared yet"
          action={<Button>Share files</Button>}
        >
          Photos, videos, documents and audio shared in the project appear here.
        </EmptyState>
      </Specimen>
    </>
  );
}

export function People() {
  const names = [
    'Pérez Rodríguez',
    'García López',
    'Fernández Silva',
    'Martínez Sosa',
    'González Díaz',
    'Rodríguez Núñez',
  ];

  return (
    <>
      <Page title="People and families">
        A person is their photo or their initials on their colour; a family, a pattern grown from
        its name. The same name always looks the same.
      </Page>
      <Specimen title="Avatars">
        <Group>
          {['María Member', 'Andrés Admin', 'Sofía Superadmin', 'Ana Trujillo'].map((name) => (
            <PersonAvatar key={name} name={name} size={44} />
          ))}
        </Group>
      </Specimen>
      <Specimen title="Family marks">
        <Group>
          {names.map((name) => (
            <Group key={name} gap="xs">
              <FamilyMark name={name} size={40} />
              <Text size="sm" fw={600}>
                {name}
              </Text>
            </Group>
          ))}
        </Group>
      </Specimen>
    </>
  );
}

export function Navigation() {
  return (
    <>
      <Page title="Navigation">
        An area's places: lit where one is, with a count where something waits.
      </Page>
      <Specimen title="Places" wide>
        <StateGrid
          states={['base', 'hover', 'focus']}
          rows={[
            {
              label: 'Place',
              render: () => (
                <NavItem as="button" icon={IconHome} label="Home" style={{ width: 220 }} />
              ),
            },
            {
              label: 'Here',
              render: () => (
                <NavItem as="button" icon={IconMap} label="Plan" active style={{ width: 220 }} />
              ),
            },
            {
              label: 'Waiting',
              render: () => (
                <NavItem
                  as="button"
                  icon={IconBell}
                  label="Notifications"
                  count={4}
                  style={{ width: 220 }}
                />
              ),
            },
          ]}
        />
      </Specimen>
      <Specimen title="Search">
        <TextInput
          leftSection={<IconSearch size={18} />}
          radius="xl"
          placeholder="Search"
          maw={360}
        />
      </Specimen>
    </>
  );
}

export const SECTIONS = [
  { id: 'foundations', label: 'Foundations', render: Foundations },
  { id: 'controls', label: 'Controls', render: Controls },
  { id: 'feedback', label: 'Feedback', render: Feedback },
  { id: 'surfaces', label: 'Surfaces', render: Surfaces },
  { id: 'people', label: 'People', render: People },
  { id: 'navigation', label: 'Navigation', render: Navigation },
];
