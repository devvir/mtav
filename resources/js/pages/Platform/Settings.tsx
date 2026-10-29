import { Form } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { Panel } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { useForm } from '@inertiajs/react';
import { Button, Group, NumberInput } from '@mantine/core';
import { IconDice5 } from '@tabler/icons-react';

/**
 * What superadmins set for the whole platform. The lottery solves in an instant, which
 * feels like nothing happened: here it is given the time it takes, as people see it.
 */
export default function Settings({
  settings,
  maxLotterySeconds,
}: {
  settings: { lottery_seconds: number };
  maxLotterySeconds: number;
}) {
  const form = useForm(settings);

  return (
    <>
      <PageHeader
        title={_('Settings')}
        description={_('How the platform works for every project.')}
      />
      <Panel title={_('Lottery')} icon={IconDice5} style={{ maxWidth: 560 }}>
        <Form onSubmit={() => form.put(route('admin.settings.update'), { preserveScroll: true })}>
          <NumberInput
            label={_('How long a lottery takes, in seconds')}
            description={_(
              'Everyone sees it work, step by step, for at least this long; the result is the same.',
            )}
            name="lottery_seconds"
            min={0}
            max={maxLotterySeconds}
            value={form.data.lottery_seconds}
            onChange={(value) => form.setData('lottery_seconds', Number(value) || 0)}
            error={form.errors.lottery_seconds}
          />
          <Group justify="flex-end">
            <Button type="submit" loading={form.processing}>
              {_('Save')}
            </Button>
          </Group>
        </Form>
      </Panel>
    </>
  );
}
