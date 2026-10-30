import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { Form, FormActions } from '@/components/forms/Form';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, PersonAvatar } from '@/design';
import { useAddressedDialog } from '@/lib/addressedDialog';
import { field } from '@/lib/forms';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import { Badge, Button, Card, Group, Modal, Stack, Text, TextInput, Textarea } from '@mantine/core';
import { IconLock, IconMessages, IconPin, IconPlus } from '@tabler/icons-react';

export interface ForumPerson {
  id: number;
  name: string;
  avatar: string | null;
}

export interface TopicSummary {
  id: number;
  title: string;
  is_pinned: boolean;
  is_closed: boolean;
  replies_count: number;
  last_post_at: string | null;
  created_at: string;
  author: ForumPerson | null;
  excerpt?: string;
}

interface Props {
  topics: Paginated<TopicSummary>;
  filters: { q: string | null };
}

/** Opening a topic: its title, and what one has to say */
function TopicForm({ onDone }: { onDone: () => void }) {
  const form = useForm({ title: '', body: '' });

  return (
    <Form onSubmit={() => form.post(route('forum.store'), { onSuccess: onDone })}>
      <TextInput label={_('Title')} withAsterisk data-autofocus {...field(form, 'title')} />
      <Textarea label={_('Message')} withAsterisk autosize minRows={5} {...field(form, 'body')} />
      <FormActions submit={_('Open the topic')} processing={form.processing}>
        <Button variant="default" onClick={onDone}>
          {_('Cancel')}
        </Button>
      </FormActions>
    </Form>
  );
}

/**
 * The project's forum: conversations anyone in the project opens and joins, pinned ones
 * first, then those with the latest posts
 */
export default function ForumIndex({ topics, filters }: Props) {
  useProjectChanges({ topics: ['topics'], posts: ['topics'] });
  const opening = useAddressedDialog('new');

  return (
    <>
      <PageHeader
        title={_('Forum')}
        tour="member.forum"
        help={
          <InfoTip title={_('The forum')} tour="member.forum">
            {_(
              'Conversations among everyone in the project: open a topic, or join one. Admins can pin a topic to the top, or close it.',
            )}
          </InfoTip>
        }
        actions={
          <Button
            leftSection={<IconPlus size={18} />}
            onClick={opening.open}
            data-tour="forum.open"
          >
            {_('Open a topic')}
          </Button>
        }
      />
      <Group justify="flex-end" mb="md">
        <QuerySearch value={filters.q} placeholder={_('Search the topics')} />
      </Group>

      {topics.data.length ? (
        <Stack gap="sm" data-tour="forum.topics">
          {topics.data.map((topic) => (
            <Card
              key={topic.id}
              component={Link}
              href={route('forum.show', { topic: topic.id })}
              p="md"
              className="ds-interactive forum-topic"
            >
              <Group wrap="nowrap" align="flex-start" gap="md">
                <PersonAvatar
                  name={topic.author?.name ?? '?'}
                  src={topic.author?.avatar}
                  size={40}
                />
                <div style={{ flex: 1, minWidth: 0 }}>
                  <Group gap="xs" wrap="nowrap">
                    {topic.is_pinned ? <IconPin size={16} aria-label={_('Pinned')} /> : null}
                    {topic.is_closed ? <IconLock size={16} aria-label={_('Closed')} /> : null}
                    <Text fw={700} className="ds-truncate">
                      {topic.title}
                    </Text>
                  </Group>
                  {topic.excerpt ? (
                    <Text size="sm" c="dimmed" lineClamp={2} mt={2}>
                      {topic.excerpt}
                    </Text>
                  ) : null}
                  <Text className="ds-caption" mt={4}>
                    {topic.author?.name ?? _('Someone who left')} ·{' '}
                    <DateTime value={topic.last_post_at ?? topic.created_at} relative />
                  </Text>
                </div>
                <Badge variant="light" color={topic.replies_count ? 'blue' : 'gray'}>
                  {_n('{count} reply', '{count} replies', topic.replies_count)}
                </Badge>
              </Group>
            </Card>
          ))}
        </Stack>
      ) : (
        <EmptyState
          icon={IconMessages}
          title={filters.q ? _('No topic matches') : _('No topics yet')}
          action={
            filters.q ? null : (
              <Button variant="light" onClick={opening.open}>
                {_('Open the first one')}
              </Button>
            )
          }
        >
          {filters.q ? null : _('Questions, ideas and news, for everyone in the project.')}
        </EmptyState>
      )}
      <Pagination page={topics} />

      <Modal opened={opening.opened} onClose={opening.close} title={_('Open a topic')} size="lg">
        <TopicForm onDone={opening.close} />
      </Modal>
    </>
  );
}
