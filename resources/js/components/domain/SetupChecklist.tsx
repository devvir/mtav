import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, usePage } from '@inertiajs/react';
import { Transition, UnstyledButton } from '@mantine/core';
import { useClickOutside } from '@mantine/hooks';
import { useEffect, useState, type ReactNode } from 'react';

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

/** It grows out of the small note, from their shared corner, swinging into place (see .note-sheet) */
const GROW = {
  in: { opacity: 1, scale: '1', rotate: '0deg' },
  out: { opacity: 0, scale: '0.22', rotate: '6deg' },
  transitionProperty: 'scale, rotate, opacity',
};

/**
 * The road to the lottery, for the whole community: a tiny sheet taped to the page's corner, that
 * grows from there into a readable one on a click, and goes back the same way.
 */
export function ChecklistCorner({ setup, opened = false }: { setup: Setup; opened?: boolean }) {
  const [open, setOpen] = useState(opened);
  const corner = useClickOutside(() => setOpen(false));

  useEffect(() => {
    if (!open) return undefined;
    const escape = (event: KeyboardEvent) => event.key === 'Escape' && setOpen(false);
    window.addEventListener('keydown', escape);
    return () => window.removeEventListener('keydown', escape);
  }, [open]);

  return (
    <div ref={corner} className="note-corner" data-tour="setup.checklist">
      <UnstyledButton
        className="note note-mini"
        aria-expanded={open}
        aria-label={`${_('Getting to the lottery')}: ${counted(setup)}`}
        onClick={() => setOpen(true)}
      >
        <span className="note-tape" aria-hidden />
        <span className="note-mini-lines" aria-hidden />
        <span className="note-count">{counted(setup)}</span>
      </UnstyledButton>
      <Transition mounted={open} transition={GROW} duration={340}>
        {(styles) => (
          <section className="note note-sheet" style={styles}>
            <Sheet setup={setup}>
              <span className="note-tape" aria-hidden />
            </Sheet>
          </section>
        )}
      </Transition>
    </div>
  );
}
