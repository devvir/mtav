import { _ } from '@/lib/translations';
import { ColorSwatch, Kbd, Menu } from '@mantine/core';
import {
  IconArrowDown,
  IconArrowUp,
  IconClipboard,
  IconClipboardCopy,
  IconCopy,
  IconPencil,
  IconRotateClockwise,
  IconStackBack,
  IconStackFront,
  IconTrash,
} from '@tabler/icons-react';
import { TINTED } from './kinds';
import type { EditableItem, PlanModel } from './model';
import type { MenuSpot } from './PlanCanvas';

/** The colours offered at hand (the shape's details offer any) */
const SWATCHES = [
  '#93c5fd',
  '#a5b4fc',
  '#e9d5ff',
  '#f9a8d4',
  '#fca5a5',
  '#fdba74',
  '#fcd34d',
  '#86efac',
  '#d6d3d1',
];

/** How far Rotate turns a shape, in degrees (as R does) */
const TURN = 15;

/**
 * A right-click on a shape (or on a corner, `at`): all it takes, wherever its details are:
 * name it (`onName`), rotate it, its colour, a floor up or down, front or back, duplicate,
 * copy, paste, remove; a corner's menu removes it (three always stay). `onClose` closes it.
 */
export function ShapeMenu({
  model,
  at,
  onClose,
  onName,
}: {
  model: PlanModel;
  at: MenuSpot | null;
  onClose: () => void;
  onName: (item: EditableItem) => void;
}) {
  const selectedItem = model.selectedItem;
  const turn = (degrees: number) => {
    if (!model.activePolygon) return;
    model.setActivePolygon(model.rotate(model.activePolygon, degrees));
    model.commit();
  };

  return (
    <Menu
      opened={Boolean(at && (selectedItem || model.editingBoundary))}
      onChange={(open) => !open && onClose()}
      withinPortal
    >
      <Menu.Target>
        <div
          style={{
            position: 'fixed',
            left: at?.x ?? 0,
            top: at?.y ?? 0,
            width: 1,
            height: 1,
          }}
        />
      </Menu.Target>
      <Menu.Dropdown>
        {model.selectedVertex !== null ? (
          <Menu.Item
            color="red"
            leftSection={<IconTrash size={16} />}
            rightSection={<Kbd size="xs">Del</Kbd>}
            disabled={(model.activePolygon?.length ?? 0) <= 3}
            onClick={() => model.remove()}
          >
            {_('Remove the corner')}
          </Menu.Item>
        ) : selectedItem ? (
          <>
            {!selectedItem.unit ? (
              <Menu.Item
                leftSection={<IconPencil size={16} />}
                onClick={() => onName(selectedItem)}
              >
                {_('Name it')}
              </Menu.Item>
            ) : null}
            <Menu.Item
              leftSection={<IconRotateClockwise size={16} />}
              rightSection={<Kbd size="xs">R</Kbd>}
              onClick={() => turn(TURN)}
            >
              {_('Rotate')}
            </Menu.Item>
            {TINTED.includes(selectedItem.type) ? (
              <div className="plan-swatches" role="group" aria-label={_('Colour')}>
                {SWATCHES.map((color) => (
                  <ColorSwatch
                    key={color}
                    component="button"
                    type="button"
                    color={color}
                    size={20}
                    aria-label={color}
                    aria-pressed={selectedItem.color === color}
                    onClick={() => {
                      model.update({ color });
                      onClose();
                    }}
                  />
                ))}
              </div>
            ) : null}
            <Menu.Divider />
            <Menu.Item
              leftSection={<IconArrowUp size={16} />}
              rightSection={<Kbd size="xs">PgUp</Kbd>}
              disabled={!model.canMoveFloor(1)}
              onClick={() => model.moveFloor(1)}
            >
              {_('One floor up')}
            </Menu.Item>
            <Menu.Item
              leftSection={<IconArrowDown size={16} />}
              rightSection={<Kbd size="xs">PgDn</Kbd>}
              disabled={!model.canMoveFloor(-1)}
              onClick={() => model.moveFloor(-1)}
            >
              {_('One floor down')}
            </Menu.Item>
            <Menu.Divider />
            <Menu.Item
              leftSection={<IconStackFront size={16} />}
              onClick={() => model.restack('front')}
            >
              {_('Bring to front')}
            </Menu.Item>
            <Menu.Item
              leftSection={<IconStackBack size={16} />}
              onClick={() => model.restack('back')}
            >
              {_('Send to back')}
            </Menu.Item>
            {!selectedItem.unit ? (
              <>
                <Menu.Divider />
                <Menu.Item
                  leftSection={<IconCopy size={16} />}
                  rightSection={<Kbd size="xs">Ctrl D</Kbd>}
                  onClick={() => model.duplicate()}
                >
                  {_('Duplicate')}
                </Menu.Item>
                <Menu.Item
                  leftSection={<IconClipboardCopy size={16} />}
                  rightSection={<Kbd size="xs">Ctrl C</Kbd>}
                  onClick={() => model.copy()}
                >
                  {_('Copy')}
                </Menu.Item>
              </>
            ) : null}
            {model.canPaste ? (
              <Menu.Item
                leftSection={<IconClipboard size={16} />}
                rightSection={<Kbd size="xs">Ctrl V</Kbd>}
                onClick={() => model.paste()}
              >
                {_('Paste')}
              </Menu.Item>
            ) : null}
            {!selectedItem.unit ? (
              <>
                <Menu.Divider />
                <Menu.Item
                  color="red"
                  leftSection={<IconTrash size={16} />}
                  rightSection={<Kbd size="xs">Del</Kbd>}
                  onClick={() => model.remove()}
                >
                  {_('Remove')}
                </Menu.Item>
              </>
            ) : null}
          </>
        ) : null}
      </Menu.Dropdown>
    </Menu>
  );
}
