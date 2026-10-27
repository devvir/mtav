<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { Plan, Point } from '@/types/models';
import { Copy, Layers, Plus, Redo2, Save, Trash2, Undo2 } from 'lucide-vue-next';
import { floorLabel } from './floors';
import { usePlanEditor, type ShapeType } from './usePlanEditor';

/**
 * Draw the project plan: move shapes, resize them from their corners, edit their
 * corners (drag; double-click an edge to add one; Delete removes the selected one),
 * add buildings, streets, green areas…, spread them over floors, and edit the
 * project's boundary. Arrow keys nudge the selection; Ctrl+Z / Ctrl+Shift+Z undo.
 */
const props = defineProps<{ plan: Plan; processing?: boolean }>();
const emit = defineEmits<{
  save: [payload: ReturnType<ReturnType<typeof usePlanEditor>['payload']>];
}>();

const editor = usePlanEditor(props.plan);
const { canvas, items, floor, allFloors, selected, selectedItem, selectedVertex, snap } = editor;

defineExpose({ markSaved: editor.markSaved, dirty: editor.dirty });

const svg = useTemplateRef<SVGSVGElement>('svg');

const shapeTypes = computed<{ value: ShapeType; label: string }[]>(() => [
  { value: 'building', label: _('Building') },
  { value: 'street', label: _('Street') },
  { value: 'green', label: _('Green area') },
  { value: 'common', label: _('Common space') },
  { value: 'other', label: _('Other') },
]);

const visible = computed(() => items.value.filter((item) => item.floor === floor.value));
const editingBoundary = computed(() => selected.value === 'boundary');
const activePolygon = computed<Point[] | null>(() =>
  editingBoundary.value ? canvas.polygon : (selectedItem.value?.polygon ?? null),
);

const points = (polygon: Point[]) => polygon.map(([x, y]) => `${x},${y}`).join(' ');
const handle = computed(() => Math.max(4, Math.min(canvas.width, canvas.height) / 120));

/** Pointer position in plan coordinates */
function toPlan(event: PointerEvent | MouseEvent): Point {
  const matrix = svg.value!.getScreenCTM()!.inverse();
  const point = new DOMPoint(event.clientX, event.clientY).matrixTransform(matrix);
  return [point.x, point.y];
}

type Drag =
  | { kind: 'move'; start: Point; original: Point[] }
  | { kind: 'vertex'; index: number }
  | { kind: 'resize'; anchor: Point; from: Point; original: Point[] };

let drag: Drag | null = null;

function setPolygon(polygon: Point[]) {
  if (editingBoundary.value) canvas.polygon = polygon;
  else if (selectedItem.value) selectedItem.value.polygon = polygon;
}

function startMove(event: PointerEvent, key: string) {
  selected.value = key;
  selectedVertex.value = null;
  drag = {
    kind: 'move',
    start: toPlan(event),
    original: structuredClone(toRaw(activePolygon.value!)),
  };
  svg.value?.setPointerCapture(event.pointerId);
}

function startVertex(event: PointerEvent, index: number) {
  selectedVertex.value = index;
  drag = { kind: 'vertex', index };
  svg.value?.setPointerCapture(event.pointerId);
}

function startResize(event: PointerEvent, corner: [number, number]) {
  const b = editor.bounds(activePolygon.value!);
  const from: Point = [corner[0] ? b.maxX : b.minX, corner[1] ? b.maxY : b.minY];
  const anchor: Point = [corner[0] ? b.minX : b.maxX, corner[1] ? b.minY : b.maxY];
  drag = { kind: 'resize', anchor, from, original: structuredClone(toRaw(activePolygon.value!)) };
  svg.value?.setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent) {
  if (!drag || !activePolygon.value) return;
  const at = toPlan(event);

  if (drag.kind === 'move') {
    setPolygon(editor.translate(drag.original, at[0] - drag.start[0], at[1] - drag.start[1]));
  } else if (drag.kind === 'vertex') {
    const polygon = [...activePolygon.value];
    polygon[drag.index] = [editor.round(at[0]), editor.round(at[1])];
    setPolygon(polygon);
  } else {
    setPolygon(editor.resize(drag.original, drag.anchor, drag.from, at));
  }
}

function onPointerUp() {
  if (drag) editor.commit();
  drag = null;
}

function addVertex(event: MouseEvent, edge: number) {
  if (activePolygon.value)
    setPolygon(editor.insertVertex(activePolygon.value, edge, toPlan(event)));
  editor.commit();
}

function clearSelection(event: PointerEvent) {
  if (event.target === svg.value || (event.target as SVGElement).dataset.background !== undefined) {
    selected.value = null;
    selectedVertex.value = null;
  }
}

/** Keyboard: nudge, delete, undo / redo */
function onKey(event: KeyboardEvent) {
  if ((event.target as HTMLElement).closest('input, textarea, select')) return;

  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
    event.preventDefault();
    if (event.shiftKey) editor.redo();
    else editor.undo();
    return;
  }

  const steps: Record<string, [number, number]> = {
    ArrowLeft: [-1, 0],
    ArrowRight: [1, 0],
    ArrowUp: [0, -1],
    ArrowDown: [0, 1],
  };
  if (steps[event.key] && activePolygon.value) {
    event.preventDefault();
    const step = event.shiftKey ? 25 : 5;
    setPolygon(
      editor.translate(activePolygon.value, steps[event.key][0] * step, steps[event.key][1] * step),
    );
    editor.commit();
  } else if ((event.key === 'Delete' || event.key === 'Backspace') && selectedItem.value) {
    event.preventDefault();
    editor.remove();
  } else if (event.key === 'Escape') {
    selected.value = null;
    selectedVertex.value = null;
  }
}

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));

const corners = computed(() => {
  if (!activePolygon.value) return [];
  const b = editor.bounds(activePolygon.value);
  return (
    [
      [0, 0],
      [1, 0],
      [1, 1],
      [0, 1],
    ] as [number, number][]
  ).map((corner) => ({
    corner,
    x: corner[0] ? b.maxX : b.minX,
    y: corner[1] ? b.maxY : b.minY,
  }));
});

const newShape = ref<ShapeType>('building');
</script>

<template>
  <div class="grid gap-4 xl:grid-cols-[1fr_18rem]">
    <div class="min-w-0 space-y-3">
      <!-- Toolbar -->
      <div
        data-tour="plan.toolbar"
        class="flex flex-wrap items-center gap-2 rounded-2xl border-2 border-border bg-surface p-2"
      >
        <div class="flex items-center gap-1">
          <SelectInput
            v-model="newShape"
            :options="shapeTypes"
            :aria-label="_('Shape to add')"
            class="min-h-10 w-40"
          />
          <Button variant="outline" size="sm" @click="editor.add(newShape)"
            ><Plus /> {{ _('Add') }}</Button
          >
        </div>
        <span class="mx-1 h-6 w-px bg-border" aria-hidden="true" />
        <Button
          variant="ghost"
          size="icon"
          :disabled="!editor.canUndo.value"
          :aria-label="_('Undo')"
          @click="editor.undo()"
          ><Undo2
        /></Button>
        <Button
          variant="ghost"
          size="icon"
          :disabled="!editor.canRedo.value"
          :aria-label="_('Redo')"
          @click="editor.redo()"
          ><Redo2
        /></Button>
        <Button
          variant="ghost"
          size="sm"
          :class="editingBoundary && 'bg-selection/50'"
          :aria-pressed="editingBoundary"
          @click="selected = editingBoundary ? null : 'boundary'"
        >
          {{ _('Edit the boundary') }}
        </Button>
        <label class="flex items-center gap-2 px-2 text-sm"
          ><input v-model="snap" type="checkbox" class="size-4 accent-interactive" />
          {{ _('Snap to grid') }}</label
        >
        <Button class="ml-auto" :disabled="processing" @click="emit('save', editor.payload())"
          ><Save data-tour="plan.save" /> {{ _('Save the plan') }}</Button
        >
      </div>

      <!-- Floors -->
      <div
        data-tour="plan.floors"
        class="flex flex-wrap items-center gap-2"
        role="tablist"
        :aria-label="_('Floors')"
      >
        <button
          v-for="f in allFloors"
          :key="f"
          type="button"
          role="tab"
          :aria-selected="floor === f"
          :class="
            cn(
              'min-h-10 rounded-lg border-2 px-3 text-sm font-medium',
              floor === f ? 'border-border-interactive bg-selection/40' : 'border-border',
            )
          "
          @click="((floor = f), (selected = null))"
        >
          {{ floorLabel(f) }}
        </button>
        <Button variant="ghost" size="sm" @click="editor.addFloor()"
          ><Layers /> {{ _('Add a floor') }}</Button
        >
      </div>

      <!-- Canvas -->
      <svg
        data-tour="plan.canvas"
        ref="svg"
        :viewBox="`0 0 ${canvas.width} ${canvas.height}`"
        class="h-auto w-full touch-none rounded-2xl border-2 border-border bg-surface select-none"
        role="application"
        :aria-label="_('Plan editor')"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
        @pointerdown="clearSelection"
      >
        <defs>
          <pattern id="grid" :width="25" :height="25" patternUnits="userSpaceOnUse">
            <path d="M 25 0 L 0 0 0 25" fill="none" stroke="var(--border)" stroke-width="0.5" />
          </pattern>
        </defs>
        <rect :width="canvas.width" :height="canvas.height" fill="url(#grid)" data-background />

        <polygon
          :points="points(canvas.polygon)"
          fill="var(--surface-sunken)"
          fill-opacity="0.6"
          stroke="var(--border-strong)"
          :stroke-width="editingBoundary ? 3 : 2"
          :stroke-dasharray="editingBoundary ? '8 4' : undefined"
          data-background
          @pointerdown.stop="editingBoundary && startMove($event, 'boundary')"
        />

        <g
          v-for="item in visible"
          :key="item.key"
          :data-shape="item.unit?.identifier ?? item.name"
          class="cursor-move"
          @pointerdown.stop="startMove($event, item.key)"
        >
          <polygon
            :points="points(item.polygon)"
            :fill="item.color"
            fill-opacity="0.85"
            :stroke="item.key === selected ? 'var(--interactive)' : 'var(--text)'"
            :stroke-opacity="item.key === selected ? 1 : 0.4"
            :stroke-width="item.key === selected ? 2.5 : 1"
          />
          <text
            :x="editor.centre(item.polygon)[0]"
            :y="editor.centre(item.polygon)[1]"
            text-anchor="middle"
            dominant-baseline="middle"
            font-size="12"
            font-weight="600"
            fill="#1c1917"
            class="pointer-events-none"
          >
            {{ item.unit?.identifier ?? item.name }}
          </text>
        </g>

        <!-- Handles of the selected shape -->
        <g v-if="activePolygon">
          <line
            v-for="(point, index) in activePolygon"
            :key="`edge-${index}`"
            :x1="point[0]"
            :y1="point[1]"
            :x2="activePolygon[(index + 1) % activePolygon.length][0]"
            :y2="activePolygon[(index + 1) % activePolygon.length][1]"
            stroke="transparent"
            :stroke-width="handle * 2"
            class="cursor-copy"
            @dblclick.stop="addVertex($event, index)"
          />
          <rect
            v-for="item in corners"
            :key="`corner-${item.corner.join()}`"
            :x="item.x - handle"
            :y="item.y - handle"
            :width="handle * 2"
            :height="handle * 2"
            fill="var(--surface)"
            stroke="var(--interactive)"
            stroke-width="1.5"
            :class="item.corner[0] === item.corner[1] ? 'cursor-nwse-resize' : 'cursor-nesw-resize'"
            @pointerdown.stop="startResize($event, item.corner)"
          />
          <circle
            v-for="(point, index) in activePolygon"
            :key="`vertex-${index}`"
            :cx="point[0]"
            :cy="point[1]"
            :r="handle * 0.8"
            :fill="selectedVertex === index ? 'var(--interactive)' : 'var(--surface)'"
            stroke="var(--interactive)"
            stroke-width="1.5"
            class="cursor-grab"
            @pointerdown.stop="startVertex($event, index)"
          />
        </g>
      </svg>
      <p class="text-sm text-text-muted">
        {{
          _(
            'Drag to move. Corners resize. Round handles move a corner of the shape; double-click an edge to add one, Delete removes it.',
          )
        }}
      </p>
    </div>

    <!-- Selected shape -->
    <aside class="h-fit space-y-4 rounded-2xl border-2 border-border bg-surface p-4">
      <template v-if="selectedItem">
        <h2 class="font-semibold">
          {{
            selectedItem.unit
              ? _('Unit {name}', { name: selectedItem.unit.identifier })
              : _('Shape')
          }}
        </h2>
        <Field v-if="!selectedItem.unit" :label="_('Kind')" v-slot="field">
          <SelectInput
            v-bind="field"
            v-model="selectedItem.type"
            name="shape_type"
            :options="shapeTypes"
            @change="editor.commit()"
          />
        </Field>
        <Field v-if="!selectedItem.unit" :label="_('Name')" optional v-slot="field">
          <TextInput
            v-bind="field"
            v-model="selectedItem.name"
            name="shape_name"
            @change="editor.commit()"
          />
        </Field>
        <Field :label="_('Colour')" v-slot="field">
          <input
            v-bind="field"
            v-model="selectedItem.color"
            name="shape_color"
            type="color"
            class="h-11 w-full cursor-pointer rounded-lg border-2 border-border bg-surface"
            @change="editor.commit()"
          />
        </Field>
        <Field :label="_('Floor')" v-slot="field">
          <SelectInput
            v-bind="field"
            v-model="selectedItem.floor"
            name="shape_floor"
            :options="allFloors.map((f) => ({ value: f, label: floorLabel(f) }))"
            @change="((floor = selectedItem!.floor), editor.commit())"
          />
        </Field>
        <div class="flex gap-2">
          <Button v-if="!selectedItem.unit" variant="outline" size="sm" @click="editor.duplicate()"
            ><Copy /> {{ _('Duplicate') }}</Button
          >
          <Button
            v-if="!selectedItem.unit || selectedVertex !== null"
            variant="outline"
            size="sm"
            class="text-error"
            @click="editor.remove()"
          >
            <Trash2 /> {{ selectedVertex !== null ? _('Remove the corner') : _('Remove') }}
          </Button>
        </div>
      </template>

      <template v-else-if="editingBoundary">
        <h2 class="font-semibold">{{ _('Boundary') }}</h2>
        <p class="text-sm text-text-muted">
          {{ _('The outline of the land. Move its corners, or double-click an edge to add one.') }}
        </p>
      </template>

      <template v-else>
        <h2 class="font-semibold">{{ _('Canvas') }}</h2>
        <div class="grid grid-cols-2 gap-3">
          <Field :label="_('Width')" v-slot="field">
            <TextInput
              v-bind="field"
              v-model.number="canvas.width"
              type="number"
              min="100"
              max="10000"
              @change="editor.commit()"
            />
          </Field>
          <Field :label="_('Height')" v-slot="field">
            <TextInput
              v-bind="field"
              v-model.number="canvas.height"
              type="number"
              min="100"
              max="10000"
              @change="editor.commit()"
            />
          </Field>
        </div>
        <p class="text-sm text-text-muted">{{ _('Select a shape to change it.') }}</p>
      </template>
    </aside>
  </div>
</template>
