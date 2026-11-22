import { Form } from '@/components/forms/Form';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Panel } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { useForm } from '@inertiajs/react';
import { Button, Group, NumberInput } from '@mantine/core';
import { IconDice5 } from '@tabler/icons-react';

/**
 * What superadmins set for the whole platform. The lottery solves in an instant, which
 * feels like nothing happened: here it is given the time it takes, as people see it. And
 * how long after a run every family's ranking becomes everyone's in the project.
 */
export default function Settings({
  settings,
  maxLotterySeconds,
  maxDisclosureHours,
}: {
  settings: { lottery_seconds: number; disclosure_hours: number };
  maxLotterySeconds: number;
  maxDisclosureHours: number;
}) {
  const form = useForm(settings);

  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Platform settings')} tour="platform.settings">
            {_('What applies to every project, like how long a lottery takes as people see it.')}
          </InfoTip>
        }
        title={_('Settings')}
        tour="platform.settings"
        description={_('How the platform works for every project.')}
      />
      <Panel
        title={_('Lottery')}
        icon={IconDice5}
        style={{ maxWidth: 560 }}
        data-tour="platform_settings.lottery"
      >
        <Form onSubmit={() => form.put(route('admin.settings.update'), { preserveScroll: true })}>
          <NumberInput
            label={_('How long a lottery takes, in seconds')}
            description={_(
              'Everyone sees it work, step by step, for at least this long. The result is the same.',
            )}
            name="lottery_seconds"
            min={0}
            max={maxLotterySeconds}
            value={form.data.lottery_seconds}
            onChange={(value) => form.setData('lottery_seconds', Number(value) || 0)}
            error={form.errors.lottery_seconds}
          />
          <NumberInput
            label={_('Hours before every family’s ranking becomes public, after a run')}
            description={_(
              'Time for admins to invalidate a run nobody could game. Then everyone in the project sees every ranking and downloads the whole record, to check the result.',
            )}
            name="disclosure_hours"
            min={0}
            max={maxDisclosureHours}
            value={form.data.disclosure_hours}
            onChange={(value) => form.setData('disclosure_hours', Number(value) || 0)}
            error={form.errors.disclosure_hours}
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
