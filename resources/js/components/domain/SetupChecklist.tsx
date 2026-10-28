import { Panel } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import { Progress } from '@mantine/core';
import { IconCircle, IconCircleCheckFilled, IconListCheck } from '@tabler/icons-react';

/** What is done on the way to the lottery (ProjectService::setupChecklist) */
export type Setup = Record<
  'unit_types' | 'units' | 'families' | 'members' | 'balanced' | 'scheduled' | 'lottery',
  boolean
>;

/** The steps from an empty project to its lottery, each leading where it is done */
export function SetupChecklist({ setup }: { setup: Setup }) {
  const steps = [
    {
      done: setup.unit_types,
      label: _('Add the unit types'),
      href: route('manage.unit_types.index'),
    },
    { done: setup.units, label: _('Add the units'), href: route('manage.units.index') },
    {
      done: setup.families,
      label: _('Register the families'),
      href: route('manage.families.index'),
    },
    {
      done: setup.members,
      label: _('Invite their members'),
      href: route('manage.invitations.index'),
    },
    {
      done: setup.balanced,
      label: _('Have as many units as families of each type'),
      href: route('manage.lottery.show'),
    },
    {
      done: setup.scheduled,
      label: _('Schedule the lottery'),
      href: route('manage.lottery.show'),
    },
    { done: setup.lottery, label: _('Run the lottery'), href: route('manage.lottery.show') },
  ];
  const done = steps.filter((step) => step.done).length;

  return (
    <Panel
      title={_('Getting to the lottery')}
      icon={IconListCheck}
      aside={`${done} / ${steps.length}`}
      data-tour="setup.checklist"
    >
      <Progress
        value={(done / steps.length) * 100}
        radius="xl"
        size="sm"
        mb="md"
        color="green"
        aria-label={_('Getting to the lottery')}
      />
      <ol className="checklist">
        {steps.map((step) => (
          <li key={step.label}>
            <Link href={step.href} className="checklist-step" data-done={step.done || undefined}>
              {step.done ? (
                <IconCircleCheckFilled size={22} className="text-positive" />
              ) : (
                <IconCircle size={22} />
              )}
              <span>{step.label}</span>
            </Link>
          </li>
        ))}
      </ol>
    </Panel>
  );
}

/** Whether every step is done */
export const isSetUp = (setup: Setup) => Object.values(setup).every(Boolean);
