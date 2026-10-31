import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, usePage } from '@inertiajs/react';
import { Popover, UnstyledButton } from '@mantine/core';
import type { ReactNode } from 'react';

/** What is done on the way to the lottery (ProjectService::setupChecklist) */
export type Setup = Record<
  'unit_types' | 'units' | 'families' | 'members' | 'scheduled' | 'lottery',
  boolean
>;

/** Whether every step is done */
export const isSetUp = (setup: Setup) => Object.values(setup).every(Boolean);

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

/** The steps, written by hand: for the whole community to follow, leading admins where each is done */
function Steps({ setup }: { setup: Setup }) {
  const manages = usePage().props.project?.can.manage;
  const steps = [
    {
      done: setup.unit_types,
      label: _('Add the unit types'),
      href: route('units.index', { new_type: 1 }),
    },
    { done: setup.units, label: _('Add the units'), href: route('units.index', { new_unit: 1 }) },
    { done: setup.families, label: _('Register the families'), href: route('families.index') },
    {
      done: setup.members,
      label: _('Invite their members'),
      href: route('manage.invitations.index'),
    },
    { done: setup.scheduled, label: _('Schedule the lottery'), href: route('lottery.show') },
    { done: setup.lottery, label: _('Run the lottery'), href: route('lottery.show') },
  ];

  return (
    <ol className="checklist">
      {steps.map((step) => {
        const inner = (
          <>
            <HandMark done={step.done} />
            <span>{step.label}</span>
          </>
        );
        return (
          <li key={step.label}>
            {manages ? (
              <Link href={step.href} className="checklist-step" data-done={step.done || undefined}>
                {inner}
              </Link>
            ) : (
              <div className="checklist-step" data-done={step.done || undefined}>
                {inner}
              </div>
            )}
          </li>
        );
      })}
    </ol>
  );
}

const counted = (setup: Setup) =>
  `${Object.values(setup).filter(Boolean).length} / ${Object.values(setup).length}`;

/** A title and its count, in the same hand */
function Sheet({ setup, children }: { setup: Setup; children?: ReactNode }) {
  return (
    <>
      {children}
      <header className="note-head">
        <h2 className="note-title">{_('Getting to the lottery')}</h2>
        <span className="note-count">{counted(setup)}</span>
      </header>
      <Steps setup={setup} />
    </>
  );
}

/**
 * Option A: a tiny sheet taped to the page's corner, that grows into a readable one on a click.
 */
export function ChecklistCorner({ setup, opened = false }: { setup: Setup; opened?: boolean }) {
  return (
    <Popover
      defaultOpened={opened}
      position="bottom-end"
      offset={8}
      withinPortal
      shadow="none"
      transitionProps={{ transition: 'pop-top-right', duration: 220 }}
    >
      <Popover.Target>
        <UnstyledButton
          className="note note-mini"
          aria-label={`${_('Getting to the lottery')}: ${counted(setup)}`}
        >
          <span className="note-tape" aria-hidden />
          <span className="note-mini-lines" aria-hidden />
          <span className="note-count">{counted(setup)}</span>
        </UnstyledButton>
      </Popover.Target>
      <Popover.Dropdown className="note note-popup">
        <Sheet setup={setup}>
          <span className="note-tape" aria-hidden />
        </Sheet>
      </Popover.Dropdown>
    </Popover>
  );
}

/** Option B: a page torn from a spiral notebook, ruled, its bottom edge torn */
export function ChecklistNotebook({ setup }: { setup: Setup }) {
  return (
    <div className="note-lift" data-tour="setup.checklist">
      <section className="note note-notebook">
        <span className="note-spiral" aria-hidden />
        <Sheet setup={setup} />
      </section>
    </div>
  );
}

/** Option C: a sticky note stuck on the page, its corner curling up */
export function ChecklistSticky({ setup }: { setup: Setup }) {
  return (
    <section className="note note-sticky" data-tour="setup.checklist">
      <Sheet setup={setup} />
    </section>
  );
}
