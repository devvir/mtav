import { LotteryProgress } from '@/components/domain/LotteryProgress';
import { RankingList } from '@/components/domain/RankingList';
import { DateTime } from '@/components/feedback/DateTime';
import { Panel } from '@/design';
import { useOpenLotteryChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _nodes } from '@/lib/translations';
import type { Unit } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import {
  ActionIcon,
  Alert,
  Anchor,
  Badge,
  Button,
  CopyButton,
  Group,
  List,
  Modal,
  Stack,
  Text,
  TextInput,
  Title,
} from '@mantine/core';
import { IconCheck, IconCopy, IconX } from '@tabler/icons-react';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';

interface Item {
  id: number;
  name: string;
}
interface Person {
  id: number;
  name: string;
  ranked: boolean;
  item: string | null;
}
interface Props {
  lottery: { slug: string; name: string; items_name: string; link: string; kept_until: string };
  state: 'open' | 'running' | 'decided';
  pace: { started_at: string; reveal_at: string | null } | null;
  failure: string | null;
  items: Item[];
  people: Person[];
  left_over: string[];
  me: { id: number; ranking: number[]; link: string } | null;
  removed: boolean;
  organizer: {
    link: string;
    readiness: {
      unranked: string[];
      left_out: number;
      left_over: number;
      runnable: boolean;
      working: boolean;
    };
  } | null;
}

/** A link to copy, in one tap */
function LinkField({ label, hint, link }: { label: string; hint?: string; link: string }) {
  return (
    <TextInput
      label={label}
      description={hint}
      readOnly
      value={link}
      onFocus={(event) => event.target.select()}
      rightSection={
        <CopyButton value={link}>
          {({ copied, copy }) => (
            <ActionIcon variant="subtle" onClick={copy} aria-label={_('Copy')}>
              {copied ? <IconCheck size={16} /> : <IconCopy size={16} />}
            </ActionIcon>
          )}
        </CopyButton>
      }
    />
  );
}

export default function Lottery(props: Props) {
  const { lottery, state, pace, failure, items, people, left_over, me, removed, organizer } = props;
  const open = state === 'open';
  const [confirming, setConfirming] = useState(false);

  useOpenLotteryChanges(lottery.slug);

  // The result is due: there is no waiting for the next look, with no live connection
  const revealAt = pace?.reveal_at;
  useEffect(() => {
    if (!revealAt) return undefined;
    const due = window.setTimeout(
      () => router.reload(),
      Math.max(0, Date.parse(revealAt) - Date.now()) + 2500,
    );
    return () => window.clearTimeout(due);
  }, [revealAt]);

  const ready = organizer?.readiness;
  const unusual = ready && (ready.unranked.length > 0 || ready.left_out > 0 || ready.left_over > 0);
  const run = () =>
    router.post(
      route('open.run', { openLottery: lottery.slug }),
      { confirmed: true },
      {
        preserveScroll: true,
        onFinish: () => setConfirming(false),
      },
    );

  return (
    <Stack gap="lg">
      <Head title={lottery.name} />
      <Stack gap={4}>
        <Title order={1}>{lottery.name}</Title>
        <Text c="dimmed">{lottery.items_name}</Text>
      </Stack>

      {failure && open ? <Alert color="red">{failure}</Alert> : null}
      {removed && open ? (
        <Alert color="yellow">
          {_('The organizer took you out of this lottery. You can join again.')}
        </Alert>
      ) : null}

      {state === 'running' && pace ? (
        <Panel title={_('The lottery is running.')}>
          <LotteryProgress startedAt={pace.started_at} revealAt={pace.reveal_at} />
        </Panel>
      ) : null}

      {state === 'decided' ? (
        <Results people={people} leftOver={left_over} lottery={lottery} />
      ) : null}

      {open ? <LinkField label={_('Link to share')} link={lottery.link} /> : null}

      {open && !me ? <Join slug={lottery.slug} /> : null}
      {open && me ? (
        <Ranking slug={lottery.slug} items={items} me={me} title={lottery.items_name} />
      ) : null}

      {state !== 'decided' ? (
        <Panel title={_('Who is in')}>
          <Stack gap="xs">
            {people.length ? null : <Text c="dimmed">{_('Nobody yet. Send the link.')}</Text>}
            {people.map((person) => (
              <Group key={person.id} justify="space-between" wrap="nowrap">
                <Text>
                  {person.name}
                  {person.id === me?.id ? ` (${_('you')})` : ''}
                </Text>
                <Group gap="xs" wrap="nowrap">
                  <Badge variant="light" color={person.ranked ? 'green' : 'gray'}>
                    {person.ranked ? _('Ranked') : _('Not yet')}
                  </Badge>
                  {open && organizer ? (
                    <ActionIcon
                      variant="subtle"
                      color="gray"
                      aria-label={_('Take {name} out', { name: person.name })}
                      onClick={() =>
                        router.delete(
                          route('open.people.remove', {
                            openLottery: lottery.slug,
                            participant: person.id,
                          }),
                          { preserveScroll: true },
                        )
                      }
                    >
                      <IconX size={16} />
                    </ActionIcon>
                  ) : null}
                </Group>
              </Group>
            ))}
          </Stack>
        </Panel>
      ) : null}

      {open && organizer ? (
        <Organize
          slug={lottery.slug}
          items={items}
          title={lottery.items_name}
          link={organizer.link}
        >
          {ready?.working === false ? (
            <Alert color="yellow">
              {_('The server cannot run lotteries right now. Try again in a moment.')}
            </Alert>
          ) : null}
          <Button
            disabled={!ready?.runnable}
            onClick={() => (unusual ? setConfirming(true) : run())}
            style={{ alignSelf: 'flex-start' }}
          >
            {_('Run the lottery')}
          </Button>
          {ready?.runnable ? null : (
            <Text size="sm" c="dimmed">
              {_('It takes at least 2 people and 1 item.')}
            </Text>
          )}
        </Organize>
      ) : null}

      {open && me ? (
        <Stack gap="sm">
          <LinkField
            label={_('Your own link')}
            hint={_('To come back from another device. Anyone with it can rank for you.')}
            link={me.link}
          />
          <Anchor
            component="button"
            size="sm"
            c="dimmed"
            style={{ alignSelf: 'flex-start' }}
            onClick={() =>
              router.delete(route('open.leave', { openLottery: lottery.slug }), {
                preserveScroll: true,
              })
            }
          >
            {_('Leave this lottery')}
          </Anchor>
        </Stack>
      ) : null}

      <Text size="xs" c="dimmed">
        {_nodes('Kept until {date}.', { date: <DateTime value={lottery.kept_until} /> })}{' '}
        {_(
          'Rankings become visible to everyone when the lottery runs, so that anyone can check the result.',
        )}
      </Text>

      <Modal
        opened={confirming}
        onClose={() => setConfirming(false)}
        title={_('Run it like this?')}
      >
        <Stack>
          <List spacing="xs">
            {ready?.unranked.length ? (
              <List.Item>
                {_('Not ranked yet: {names}. They take part in an order chosen at random.', {
                  names: ready.unranked.join(', '),
                })}
              </List.Item>
            ) : null}
            {ready?.left_out ? (
              <List.Item>
                {_('{count} people will get nothing: there are too few to go round.', {
                  count: ready.left_out,
                })}
              </List.Item>
            ) : null}
            {ready?.left_over ? (
              <List.Item>{_('{count} will be left over.', { count: ready.left_over })}</List.Item>
            ) : null}
          </List>
          <Text size="sm" c="dimmed">
            {_('A lottery runs only once.')}
          </Text>
          <Group justify="flex-end">
            <Button variant="default" onClick={() => setConfirming(false)}>
              {_('Not yet')}
            </Button>
            <Button onClick={run}>{_('Run the lottery')}</Button>
          </Group>
        </Stack>
      </Modal>
    </Stack>
  );
}

function Join({ slug }: { slug: string }) {
  const form = useForm({ name: '' });

  const join = (event: FormEvent) => {
    event.preventDefault();
    form.post(route('open.join', { openLottery: slug }), { preserveScroll: true });
  };

  return (
    <Panel title={_('Join in')}>
      <form onSubmit={join}>
        <Group align="flex-start">
          <TextInput
            aria-label={_('Your name')}
            placeholder={_('Your name')}
            required
            maxLength={60}
            style={{ flex: 1 }}
            value={form.data.name}
            error={form.errors.name}
            onChange={(event) => form.setData('name', event.target.value)}
          />
          <Button type="submit" loading={form.processing}>
            {_('Join')}
          </Button>
        </Group>
      </form>
    </Panel>
  );
}

/** Their order, saved with every move; what they have not ordered follows below */
function Ranking({
  slug,
  items,
  me,
  title,
}: {
  slug: string;
  items: Item[];
  me: NonNullable<Props['me']>;
  title: string;
}) {
  const ordered = [
    ...me.ranking
      .map((id) => items.find((item) => item.id === id))
      .filter((item): item is Item => !!item),
    ...items.filter((item) => !me.ranking.includes(item.id)),
  ];
  const units = ordered.map((item) => ({ id: item.id, identifier: item.name }) as unknown as Unit);
  const save = (next: Unit[]) =>
    router.put(
      route('open.rank', { openLottery: slug }),
      { ranking: next.map((unit) => unit.id) },
      { preserveScroll: true, preserveState: true },
    );

  return (
    <Panel title={_('Your preferences: {what}', { what: title })}>
      <Stack gap="sm">
        {items.length ? (
          <RankingList units={units} onChange={save} />
        ) : (
          <Text c="dimmed">{_('Nothing to rank yet.')}</Text>
        )}
        {items.length && !me.ranking.length ? (
          <Button variant="light" onClick={() => save(units)} style={{ alignSelf: 'flex-start' }}>
            {_('This order is fine')}
          </Button>
        ) : null}
        {me.ranking.length && me.ranking.length < items.length ? (
          <Text size="sm" c="dimmed">
            {_('New ones were added since you ranked. Check their place.')}
          </Text>
        ) : null}
        <Text size="sm" c="dimmed">
          {_('Nobody sees your order until the lottery runs.')}
        </Text>
      </Stack>
    </Panel>
  );
}

function Organize({
  slug,
  items,
  title,
  link,
  children,
}: {
  slug: string;
  items: Item[];
  title: string;
  link: string;
  children: ReactNode;
}) {
  const form = useForm({ name: '' });
  const add = (event: FormEvent) => {
    event.preventDefault();
    form.post(route('open.items.store', { openLottery: slug }), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  };

  return (
    <Panel title={_('Organizing')}>
      <Stack>
        <Stack gap="xs">
          <Text fw={600}>{title}</Text>
          {items.map((item) => (
            <Group key={item.id} justify="space-between" wrap="nowrap">
              <Text>{item.name}</Text>
              <ActionIcon
                variant="subtle"
                color="gray"
                aria-label={_('Remove {name}', { name: item.name })}
                onClick={() =>
                  router.delete(route('open.items.remove', { openLottery: slug, item: item.id }), {
                    preserveScroll: true,
                  })
                }
              >
                <IconX size={16} />
              </ActionIcon>
            </Group>
          ))}
          <form onSubmit={add}>
            <Group align="flex-start">
              <TextInput
                aria-label={_('Add one')}
                placeholder={_('Add one')}
                maxLength={60}
                required
                style={{ flex: 1 }}
                value={form.data.name}
                error={form.errors.name}
                onChange={(event) => form.setData('name', event.target.value)}
              />
              <Button type="submit" variant="default" loading={form.processing}>
                {_('Add')}
              </Button>
            </Group>
          </form>
        </Stack>
        {children}
        <LinkField
          label={_('Your organizer link')}
          hint={_(
            'Keep it: there is no other way back to organizing this lottery from another browser.',
          )}
          link={link}
        />
      </Stack>
    </Panel>
  );
}

function Results({
  people,
  leftOver,
  lottery,
}: {
  people: Person[];
  leftOver: string[];
  lottery: Props['lottery'];
}) {
  return (
    <Panel title={_('The result')}>
      <Stack gap="xs">
        {people.map((person) => (
          <Group key={person.id} justify="space-between" wrap="nowrap">
            <Text>{person.name}</Text>
            <Text fw={600}>{person.item ?? _('Nothing this time')}</Text>
          </Group>
        ))}
        {leftOver.length ? (
          <Text size="sm" c="dimmed">
            {_('Left over: {names}', { names: leftOver.join(', ') })}
          </Text>
        ) : null}
        <Anchor
          href={route('open.record', { openLottery: lottery.slug })}
          size="sm"
          target="_blank"
        >
          {_('See the whole record')}
        </Anchor>
      </Stack>
    </Panel>
  );
}
