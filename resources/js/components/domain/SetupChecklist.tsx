import { Panel } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import { Progress } from '@mantine/core';

/** What is done on the way to the lottery (ProjectService::setupChecklist) */
export type Setup = Record<
  'unit_types' | 'units' | 'families' | 'members' | 'scheduled' | 'lottery',
  boolean
>;

/** A circle and a tick as a pen leaves them, the circle not quite closing */
function HandMark({ done }: { done: boolean }) {
  return (
    <svg className="hand-mark" viewBox="0 0 24 24" width={24} height={24} aria-hidden fill="none">
      <path d="M13 3.1c4.7-.4 8.3 3.3 8.2 8.3-.1 5.1-3.8 9.3-9 9.3-5 .1-9-3.6-8.8-8.8C3.6 7.2 7 3.8 11 3.4" />
      {done ? (
        <path className="hand-tick" d="M6.8 12.6c1.5 1.2 2.7 2.9 3.6 4.7 2.1-4.7 5-8.6 9.1-12.2" />
      ) : null}
    </svg>
  );
}

/** The steps from an empty project to its lottery, each leading where it is done */
export function SetupChecklist({ setup }: { setup: Setup }) {
  const steps = [
    {
      done: setup.unit_types,
      label: _('Add the unit types'),
      href: route('units.index', { new_type: 1 }),
    },
    { done: setup.units, label: _('Add the units'), href: route('units.index', { new_unit: 1 }) },
    {
      done: setup.families,
      label: _('Register the families'),
      href: route('families.index'),
    },
    {
      done: setup.members,
      label: _('Invite their members'),
      href: route('manage.invitations.index'),
    },
    {
      done: setup.scheduled,
      label: _('Schedule the lottery'),
      href: route('lottery.show'),
    },
    { done: setup.lottery, label: _('Run the lottery'), href: route('lottery.show') },
  ];
  const done = steps.filter((step) => step.done).length;

  return (
    <Panel
      title={_('Getting to the lottery')}
      className="ds-paper"
      aside={`${done} / ${steps.length}`}
      data-tour="setup.checklist"
    >
      <Progress
        value={(done / steps.length) * 100}
        radius="xl"
        size="xs"
        mb="md"
        color="green"
        aria-label={_('Getting to the lottery')}
      />
      <ol className="checklist">
        {steps.map((step) => (
          <li key={step.label}>
            <Link href={step.href} className="checklist-step" data-done={step.done || undefined}>
              <HandMark done={step.done} />
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
