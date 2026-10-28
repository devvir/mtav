import { PlanModel } from '@/components/plan/model';
import type { Plan } from '@/types/models';

const plan = (): Plan => ({
  id: 1,
  width: 800,
  height: 600,
  unit_system: 'meters',
  polygon: [
    [0, 0],
    [800, 0],
    [800, 600],
    [0, 600],
  ],
  items: [
    {
      id: 10,
      type: 'unit',
      floor: 0,
      name: null,
      metadata: null,
      polygon: [
        [10, 10],
        [50, 10],
        [50, 50],
        [10, 50],
      ],
      unit: { id: 5, identifier: 'A-101', unit_type_id: 1, family: null },
    },
  ],
});

describe('the plan editor', () => {
  it('moves shapes along the grid when snapping', () => {
    const editor = new PlanModel(plan());

    // Moved by (7, 3), the top-left corner lands on the grid point (15, 15)
    expect(
      editor.translate(
        [
          [10, 10],
          [20, 10],
          [20, 20],
        ],
        7,
        3,
      ),
    ).toEqual([
      [15, 15],
      [25, 15],
      [25, 25],
    ]);

    editor.snap = false;
    expect(
      editor.translate(
        [
          [10, 10],
          [20, 10],
          [20, 20],
        ],
        7,
        3,
      ),
    ).toEqual([
      [17, 13],
      [27, 13],
      [27, 23],
    ]);
  });

  it('resizes a shape from a corner, keeping the opposite one fixed', () => {
    const editor = new PlanModel(plan());
    const square: [number, number][] = [
      [0, 0],
      [10, 0],
      [10, 10],
      [0, 10],
    ];

    const resized = editor.resize(square, [0, 0], [10, 10], [20, 30]);

    expect(resized).toEqual([
      [0, 0],
      [20, 0],
      [20, 30],
      [0, 30],
    ]);
  });

  it('adds a corner on an edge, at the closest point', () => {
    const editor = new PlanModel(plan());

    const polygon = editor.insertVertex(
      [
        [0, 0],
        [100, 0],
        [100, 100],
      ],
      0,
      [42, 30],
    );

    expect(polygon).toEqual([
      [0, 0],
      [40, 0],
      [100, 0],
      [100, 100],
    ]);
  });

  it('undoes and redoes changes', () => {
    const editor = new PlanModel(plan());

    editor.add('green');
    expect(editor.items).toHaveLength(2);

    editor.undo();
    expect(editor.items).toHaveLength(1);

    editor.redo();
    expect(editor.items).toHaveLength(2);
    expect(editor.items[1].type).toBe('green');
  });

  it("never removes a unit's shape", () => {
    const editor = new PlanModel(plan());

    editor.selected = 'item-10';
    editor.remove();

    expect(editor.items).toHaveLength(1);
  });

  it('removes a selected corner, but keeps at least a triangle', () => {
    const editor = new PlanModel(plan());
    editor.selected = 'item-10';

    editor.selectedVertex = 0;
    editor.remove();
    expect(editor.items[0].polygon).toHaveLength(3);

    editor.selectedVertex = 0;
    editor.remove();
    expect(editor.items[0].polygon).toHaveLength(3);
  });

  it('sends every shape, new ones without an id', () => {
    const editor = new PlanModel(plan());
    editor.add('street');

    const payload = editor.payload();

    expect(payload.items.map((item) => [item.id, item.type])).toEqual([
      [10, 'unit'],
      [null, 'street'],
    ]);
    expect(payload.width).toBe(800);
  });
});
