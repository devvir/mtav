import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { Form, FormActions } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { field } from '@/lib/forms';
import { heldOffline } from '@/lib/offline';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { router, useForm } from '@inertiajs/react';
import { ActionIcon, Alert, Button, Card, Group, Menu, Stack, Text, Textarea } from '@mantine/core';
import {
  IconDots,
  IconLock,
  IconLockOpen,
  IconPencil,
  IconPin,
  IconPinnedOff,
  IconTrash,
} from '@tabler/icons-react';
import { useEffect, useState } from 'react';
import type { ForumPerson, TopicSummary } from './Index';

interface ForumPost {
  id: number;
  body: string;
  author: ForumPerson | null;
  created_at: string;
  edited_at: string | null;
  can: { update: boolean; delete: boolean };
}

interface Props {
  topic: TopicSummary & { can: { reply: boolean; manage: boolean } };
  posts: Paginated<ForumPost>;
}

/** A post: who wrote it and when, what they wrote; its writer edits it there */
function PostCard({ post }: { post: ForumPost }) {
  const [editing, setEditing] = useState(false);
  const form = useForm({ body: post.body });

  const remove = async () => {
    if (
      await confirm({
        title: _('Remove this post?'),
        confirmLabel: _('Remove'),
        danger: true,
      })
    )
      router.delete(route('forum.posts.destroy', { post: post.id }), { preserveScroll: true });
  };

  return (
    <Card p="md" id={`post-${post.id}`} className="forum-post">
      <Group justify="space-between" wrap="nowrap" align="flex-start">
        <Group gap="sm" wrap="nowrap">
          <PersonAvatar name={post.author?.name ?? '?'} src={post.author?.avatar} size={36} />
          <div>
            <Text fw={650}>{post.author?.name ?? _('Someone who left')}</Text>
            <Text className="ds-caption">
              <DateTime value={post.created_at} />
              {post.edited_at ? ` · ${_('edited')}` : null}
            </Text>
          </div>
        </Group>
        {post.can.update || post.can.delete ? (
          <Menu position="bottom-end">
            <Menu.Target>
              <ActionIcon variant="subtle" aria-label={_('Post options')}>
                <IconDots size={18} />
              </ActionIcon>
            </Menu.Target>
            <Menu.Dropdown>
              {post.can.update ? (
                <Menu.Item leftSection={<IconPencil size={16} />} onClick={() => setEditing(true)}>
                  {_('Edit')}
                </Menu.Item>
              ) : null}
              {post.can.delete ? (
                <Menu.Item color="red" leftSection={<IconTrash size={16} />} onClick={remove}>
                  {_('Remove')}
                </Menu.Item>
              ) : null}
            </Menu.Dropdown>
          </Menu>
        ) : null}
      </Group>
      {editing ? (
        <Form
          onSubmit={() =>
            form.put(route('forum.posts.update', { post: post.id }), {
              preserveScroll: true,
              onSuccess: () => setEditing(false),
            })
          }
        >
          <Textarea
            aria-label={_('Message')}
            autosize
            minRows={3}
            mt="sm"
            {...field(form, 'body')}
          />
          <FormActions submit={_('Save')} processing={form.processing} requiredNote={false}>
            <Button variant="default" onClick={() => setEditing(false)}>
              {_('Cancel')}
            </Button>
          </FormActions>
        </Form>
      ) : (
        <Text mt="sm" style={{ whiteSpace: 'pre-line', overflowWrap: 'anywhere' }}>
          {post.body}
        </Text>
      )}
    </Card>
  );
}

/** A topic: its conversation, and a reply at its end */
export default function ForumShow({ topic, posts }: Props) {
  useProjectChanges({ posts: ['posts', 'topic'], topics: ['topic'] });
  const reply = useForm({ body: '' });

  // A post the address names (?post=12, from a notification): in view
  useEffect(() => {
    const named = new URLSearchParams(window.location.search).get('post');
    if (named) document.getElementById(`post-${named}`)?.scrollIntoView({ block: 'center' });
  }, []);

  const set = (change: Partial<Pick<TopicSummary, 'is_pinned' | 'is_closed'>>) =>
    !heldOffline() &&
    router.put(route('forum.update', { topic: topic.id }), change, { preserveScroll: true });

  const remove = async () => {
    if (
      await confirm({
        title: _('Remove this topic?'),
        message: _('Every post in it goes too.'),
        confirmLabel: _('Remove'),
        danger: true,
      })
    )
      router.delete(route('forum.destroy', { topic: topic.id }));
  };

  return (
    <>
      <PageHeader
        title={topic.title}
        back={{ href: route('forum.index'), label: _('Forum') }}
        actions={
          topic.can.manage ? (
            <Group gap="xs">
              <Button
                variant="default"
                leftSection={topic.is_pinned ? <IconPinnedOff size={18} /> : <IconPin size={18} />}
                onClick={() => set({ is_pinned: !topic.is_pinned })}
              >
                {topic.is_pinned ? _('Unpin') : _('Pin')}
              </Button>
              <Button
                variant="default"
                leftSection={topic.is_closed ? <IconLockOpen size={18} /> : <IconLock size={18} />}
                onClick={() => set({ is_closed: !topic.is_closed })}
              >
                {topic.is_closed ? _('Reopen') : _('Close')}
              </Button>
              <ActionIcon
                variant="subtle"
                color="red"
                size="lg"
                aria-label={_('Remove the topic')}
                onClick={remove}
              >
                <IconTrash size={18} />
              </ActionIcon>
            </Group>
          ) : null
        }
      />

      <Stack gap="sm">
        {posts.data.map((post) => (
          <PostCard key={post.id} post={post} />
        ))}
      </Stack>
      <Pagination page={posts} />

      {topic.can.reply ? (
        <Card p="md" mt="lg">
          <Form
            onSubmit={() =>
              !heldOffline() &&
              reply.post(route('forum.reply', { topic: topic.id }), {
                onSuccess: () => reply.reset(),
              })
            }
          >
            <Textarea label={_('Your reply')} autosize minRows={3} {...field(reply, 'body')} />
            <FormActions submit={_('Reply')} processing={reply.processing} requiredNote={false} />
          </Form>
        </Card>
      ) : (
        <Alert mt="lg" radius="lg" icon={<IconLock size={18} />}>
          {_('This topic is closed: it takes no more replies.')}
        </Alert>
      )}
    </>
  );
}
