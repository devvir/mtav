import { _ } from '@/lib/translations';
import {
  Button,
  Card,
  ColorInput,
  Group,
  NativeSelect,
  Stack,
  Text,
  TextInput,
  Title,
} from '@mantine/core';
import { IconCopy, IconTrash } from '@tabler/icons-react';
import { floorLabel } from './floors';
import { TINTED } from './kinds';
import type { PlanModel } from './model';

/**
 * The selected shape's details, beside the canvas where there is room for them (else its
 * menu has it all): its name, colour and floor, duplicate and remove; the boundary's, when it
 * is being edited; else a word on how to choose a shape.
 */
export function ShapeDetails({ model }: { model: PlanModel }) {
  const selectedItem = model.selectedItem;

  return (
    <Card p="sm" h="fit-content" className="plan-details">
      {selectedItem ? (
        <Stack gap="sm">
          <Title order={2} fz="md">
            {selectedItem.unit
              ? _('Unit {name}', { name: selectedItem.unit.identifier })
              : _('Shape')}
          </Title>
          {!selectedItem.unit ? (
            <TextInput
              label={_('Name')}
              name="shape_details_name"
              value={selectedItem.name}
              onChange={(event) => {
                model.edit({ name: event.currentTarget.value });
              }}
              onBlur={() => model.commit()}
            />
          ) : null}
          {TINTED.includes(selectedItem.type) ? (
            <ColorInput
              label={_('Colour')}
              name="shape_color"
              value={selectedItem.color}
              onChange={(color) => {
                model.edit({ color });
              }}
              onChangeEnd={() => model.commit()}
              format="hex"
            />
          ) : null}
          <NativeSelect
            label={_('Floor')}
            name="shape_floor"
            value={String(selectedItem.floor)}
            onChange={(event) => model.update({ floor: Number(event.currentTarget.value) })}
            data={model.allFloors.map((floor) => ({
              value: String(floor),
              label: floorLabel(floor),
            }))}
          />
          <Group gap="xs">
            {!selectedItem.unit ? (
              <Button
                variant="default"
                size="xs"
                leftSection={<IconCopy size={14} />}
                onClick={() => model.duplicate()}
              >
                {_('Duplicate')}
              </Button>
            ) : null}
            {!selectedItem.unit || model.selectedVertex !== null ? (
              <Button
                variant="default"
                color="red"
                c="red"
                size="xs"
                leftSection={<IconTrash size={14} />}
                onClick={() => model.remove()}
              >
                {model.selectedVertex !== null ? _('Remove the corner') : _('Remove')}
              </Button>
            ) : null}
          </Group>
        </Stack>
      ) : model.editingBoundary ? (
        <Stack gap="xs">
          <Title order={2} fz="md">
            {_('Boundary')}
          </Title>
          <Text className="ds-caption">
            {_('The outline of the land. Move its corners, or double-click an edge to add one.')}
          </Text>
          {model.selectedVertex !== null && model.activePolygon!.length > 3 ? (
            <Button
              variant="default"
              color="red"
              c="red"
              size="xs"
              w="fit-content"
              leftSection={<IconTrash size={14} />}
              onClick={() => model.remove()}
            >
              {_('Remove the corner')}
            </Button>
          ) : null}
        </Stack>
      ) : (
        <Text className="ds-caption">{_('Select a shape to change it.')}</Text>
      )}
    </Card>
  );
}
