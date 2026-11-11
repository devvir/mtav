import { UnitForm } from '@/components/forms/UnitForm';
import { _ } from '@/lib/translations';
import type { Plan, Point, UnitType } from '@/types/models';
import { Group, Modal, Stack, Text } from '@mantine/core';
import {
  forwardRef,
  useEffect,
  useId,
  useImperativeHandle,
  useMemo,
  useRef,
  useState,
  useSyncExternalStore,
} from 'react';
import { useAutosave, type SaveStatus } from './autosave';
import { FloorTabs } from './FloorTabs';
import { HeightGrip } from './HeightGrip';
import { PlanHint } from './hint';
import { usePlanKeys } from './keys';
import { PlanModel, type EditableItem } from './model';
import { NameField, nameSpot } from './NameField';
import { PlanCanvas, type MenuSpot } from './PlanCanvas';
import { PlanPalette } from './PlanPalette';
import { PlanToolbar } from './PlanToolbar';
import { ShapeDetails } from './ShapeDetails';
import { ShapeMenu } from './ShapeMenu';

export type PlanPayload = ReturnType<PlanModel['payload']>;

export interface PlanEditorHandle {
  /** Saves what is pending now; whether everything is saved */
  flush: () => Promise<boolean>;
  dirty: () => boolean;
}

/**
 * Draw the project plan: move shapes, resize them from their corners, move one side (drag
 * an edge), rotate them (the handle above, or R), edit their corners (drag; double-click an
 * edge to add one; Delete removes the selected one), add units, streets, green areas… from
 * the palette, spread them over floors (Page Up / Page Down), and edit the project's
 * boundary; the canvas grows or shrinks from its bottom edge. Arrow keys nudge the
 * selection; Ctrl+Z / Ctrl+Shift+Z undo and redo. It saves itself a moment after each change.
 *
 * This component holds the editing session together (the model, saving, the menu, naming,
 * a unit being added); each part is its own: `PlanPalette`, `FloorTabs`, `PlanToolbar`,
 * `PlanCanvas` (with `usePlanDrag`), `ShapeDetails`, `ShapeMenu`, `NameField`, `usePlanKeys`.
 */
export const PlanEditor = forwardRef<
  PlanEditorHandle,
  {
    plan: Plan;
    /** Sends the plan; the ids the server gave new shapes, by their key */
    save: (payload: PlanPayload) => Promise<Record<string, number>>;
    /** Where saving is, and why the server refused it, for the panel to show */
    onStatus?: (status: SaveStatus, reason: string | null) => void;
    /** The project's unit types: given, units are added here, drawn where they go */
    unitTypes?: UnitType[];
    /** A unit was added on the plan (the units listed elsewhere follow) */
    onUnitAdded?: () => void;
  }
>(function PlanEditor({ plan, save, onStatus, unitTypes, onUnitAdded }, ref) {
  // One per editing session (the panel hands the plan as it was when editing began)
  const model = useMemo(() => new PlanModel(plan), [plan]);
  const version = useSyncExternalStore(model.subscribe, model.getVersion);

  // The plan saves itself a moment after each change
  const { status, reason, flush } = useAutosave(model, version, save);

  useImperativeHandle(ref, () => ({ flush, dirty: () => model.dirty }), [model, flush]);
  useEffect(() => onStatus?.(status, reason), [onStatus, status, reason]);

  // Where the menu was opened (right-click), on the shape selected; the shape being named
  const [menu, setMenu] = useState<MenuSpot | null>(null);
  const [naming, setNaming] = useState<{ key: string; at: MenuSpot } | null>(null);
  // A unit being added: where it starts, while its details are asked
  const [newUnit, setNewUnit] = useState<{ polygon: Point[]; floor: number } | null>(null);

  usePlanKeys(model, () => setMenu(null));

  const svg = useRef<SVGSVGElement>(null);
  const frame = useRef<HTMLDivElement>(null);
  const look = `plan${useId().replace(/:/g, '')}`;

  const named = model.items.find((item) => item.key === naming?.key) ?? null;
  const name = (item: EditableItem) =>
    setNaming({ key: item.key, at: nameSpot(svg, frame, item.polygon) });

  return (
    <>
      <div className="plan-editor">
        <PlanPalette
          model={model}
          look={look}
          withUnits={Boolean(unitTypes)}
          onAddUnit={() => setNewUnit({ polygon: model.startOf('unit'), floor: model.floor })}
        />

        <Stack gap="xs" style={{ minWidth: 0 }}>
          <Group justify="space-between" gap="xs" wrap="wrap">
            <FloorTabs model={model} />
            <PlanToolbar model={model} />
          </Group>

          <div className="plan-frame" ref={frame}>
            <PlanCanvas model={model} look={look} svg={svg} onMenu={setMenu} onName={name} />
            <HeightGrip model={model} svg={svg} />
            {named ? (
              <NameField
                key={named.key}
                item={named}
                at={naming!.at}
                onClose={() => setNaming(null)}
                onSave={(name) => {
                  model.rename(named.key, name);
                  setNaming(null);
                }}
              />
            ) : null}
            <PlanHint host={frame} />
          </div>
          {/* What to know */}
          <Text className="ds-caption">
            {_(
              'Drag to move, or drag an edge to move that side. Ctrl + drag makes a copy. Corners resize, and round handles move a corner. Double-click an edge to add one. The handle above rotates, and so does R. Double-click a shape to name it. Right-click for more.',
            )}
          </Text>
        </Stack>

        <ShapeDetails model={model} />
      </div>

      <ShapeMenu model={model} at={menu} onClose={() => setMenu(null)} onName={name} />

      {unitTypes ? (
        <Modal opened={Boolean(newUnit)} onClose={() => setNewUnit(null)} title={_('Add a unit')}>
          {newUnit ? (
            <UnitForm
              unitTypes={unitTypes}
              place={{
                ...newUnit,
                onPlaced: (item) => {
                  model.adopt(item);
                  setNewUnit(null);
                  onUnitAdded?.();
                },
              }}
              onDone={() => setNewUnit(null)}
            />
          ) : null}
        </Modal>
      ) : null}
    </>
  );
});
