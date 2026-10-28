import { RankingList } from '@/components/domain/RankingList';
import type { Unit } from '@/types/models';
import { MantineProvider } from '@mantine/core';
import { fireEvent, render, screen } from '@testing-library/react';

const units = (): Unit[] =>
  ['A-101', 'A-102', 'A-103'].map((identifier, id) => ({ id, identifier, can: {} }) as Unit);

const draw = (props: Partial<Parameters<typeof RankingList>[0]> = {}) => {
  const onChange = vi.fn();
  render(
    <MantineProvider>
      <RankingList units={units()} onChange={onChange} {...props} />
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

  it('cannot be reordered when locked', () => {
    draw({ disabled: true });

    expect(screen.queryByLabelText('Move A-103 up')).toBeNull();
  });
});
