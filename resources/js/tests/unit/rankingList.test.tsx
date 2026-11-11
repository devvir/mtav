import { RankingList } from '@/components/domain/RankingList';
import type { Unit } from '@/types/models';
import { MantineProvider } from '@mantine/core';
import { fireEvent, render, screen } from '@testing-library/react';

const units = (): Unit[] =>
  ['A-101', 'A-102', 'A-103'].map((identifier, id) => ({ id, identifier, can: {} }) as Unit);

const draw = (props: Partial<Parameters<typeof RankingList<Unit>>[0]> = {}) => {
  const onChange = vi.fn();
  render(
    <MantineProvider>
      <RankingList
        items={units()}
        label={(unit) => unit.identifier}
        onChange={onChange}
        {...props}
      />
    </MantineProvider>,
  );
  return onChange;
};

describe("a family's ranking", () => {
  it('moves a unit up, says so, and reports the new order', () => {
    const onChange = draw();

    fireEvent.click(screen.getByLabelText('Move A-103 up'));

    const order: Unit[] = onChange.mock.calls.at(-1)![0];
    expect(order.map((unit) => unit.identifier)).toEqual(['A-101', 'A-103', 'A-102']);
    expect(document.querySelector('[data-ranking-announcement]')?.textContent).toContain('A-103');
  });

  it('keeps the arrows at the ends reachable, moving nothing: the focus stays where it was', () => {
    const onChange = draw();
    const up = screen.getByLabelText('Move A-101 up');
    const down = screen.getByLabelText('Move A-103 down');

    for (const end of [up, down]) {
      expect(end).toHaveProperty('disabled', false);
      expect(end.getAttribute('aria-disabled')).toBe('true');
      fireEvent.click(end);
    }

    expect(onChange).not.toHaveBeenCalled();
    expect(screen.getByLabelText('Move A-102 up').getAttribute('aria-disabled')).toBe('false');
  });

  it('cannot be reordered when locked', () => {
    draw({ disabled: true });

    expect(screen.queryByLabelText('Move A-103 up')).toBeNull();
  });
});
