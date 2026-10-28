import { Text } from '@mantine/core';
import { Fragment, useLayoutEffect, useRef, type ReactNode } from 'react';

/** A section's head: its name, and what the component is for. */
export const Page = ({ title, children }: { title: string; children?: ReactNode }) => (
  <header className="lab-page">
    <h1 className="lab-title">{title}</h1>
    {children ? (
      <Text c="dimmed" maw={780}>
        {children}
      </Text>
    ) : null}
  </header>
);

/** One thing shown: its name, a line on it, and it. */
export const Specimen = ({
  title,
  note,
  children,
  wide,
}: {
  title: string;
  note?: ReactNode;
  children: ReactNode;
  wide?: boolean;
}) => (
  <section className={wide ? 'specimen is-wide' : 'specimen'}>
    <h2 className="specimen-title">{title}</h2>
    {note ? (
      <Text size="sm" c="dimmed" maw={780} mb="sm">
        {note}
      </Text>
    ) : null}
    <div className="specimen-body">{children}</div>
  </section>
);

/** The states a component is seen in. Hover, pressed and focus are held still (pseudo.ts, `Held`); the rest are its own props. */
export type State =
  'base' | 'hover' | 'pressed' | 'focus' | 'on' | 'disabled' | 'loading' | 'error';

const HELD: Partial<Record<State, string[]>> = {
  hover: ['ps-hover'],
  pressed: ['ps-hover', 'ps-active'],
  focus: ['ps-focus'],
};
const NAMES: Record<State, string> = {
  base: 'Base',
  hover: 'Hover',
  pressed: 'Pressed',
  focus: 'Keyboard focus',
  on: 'On',
  disabled: 'Disabled',
  loading: 'Loading',
  error: 'Error',
};

/**
 * A component held in a state as if the pointer were on it, pressing it, or
 * the keyboard had reached it: the state's class on its root, or on the part
 * `target` names (a field's input, a card's link). Kept there if the
 * component redraws its classes.
 */
export const Held = ({
  state,
  target,
  children,
}: {
  state: State;
  target?: string;
  children: ReactNode;
}) => {
  const box = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    const names = HELD[state];
    const element = (
      target ? box.current?.querySelector(target) : box.current?.firstElementChild
    ) as HTMLElement | null | undefined;
    if (!names || !element) return undefined;

    const hold = () => {
      if (!names.every((name) => element.classList.contains(name))) element.classList.add(...names);
    };
    hold();
    const watch = new MutationObserver(hold);
    watch.observe(element, { attributes: true, attributeFilter: ['class'] });

    return () => {
      watch.disconnect();
      element.classList.remove(...names);
    };
  }, [state, target]);

  return (
    <div ref={box} className="held">
      {children}
    </div>
  );
};

/**
 * A component in each of its states, a column each, and each of its variants
 * a row. Every cell is live: it still answers to the pointer.
 */
export const StateGrid = ({
  states,
  rows,
}: {
  states: State[];
  rows: { label: string; target?: string; render: (state: State) => ReactNode }[];
}) => (
  <div
    className="state-grid"
    style={{
      gridTemplateColumns: `minmax(90px, max-content) repeat(${states.length}, max-content)`,
    }}
  >
    <span />
    {states.map((state) => (
      <span key={state} className="state-head">
        {NAMES[state]}
      </span>
    ))}
    {rows.map((row) => (
      <Fragment key={row.label}>
        <span className="state-row">{row.label}</span>
        {states.map((state) => (
          <div key={state} className="state-cell">
            <Held state={state} target={row.target}>
              {row.render(state)}
            </Held>
          </div>
        ))}
      </Fragment>
    ))}
  </div>
);

/** A box the reader can resize by its corner: how a component behaves as its room changes. */
export const Stage = ({ children, width = '100%' }: { children: ReactNode; width?: string }) => (
  <div className="stage" style={{ width }}>
    {children}
  </div>
);
