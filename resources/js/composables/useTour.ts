import { reactive } from 'vue';

export interface TourStep {
  /** The element to point at: data-tour="…" */
  target: string;
  title: string;
  body: string;
}

export type TourDefinition = () => TourStep[];

const tours = new Map<string, TourDefinition>();

const state = reactive<{ id: string | null; steps: TourStep[]; index: number }>({
  id: null,
  steps: [],
  index: 0,
});

/**
 * Guided tours: a sequence of steps, each pointing at an element of the page
 * (marked with data-tour="…"). Steps whose element is not on the page are skipped.
 *
 * Tours are defined once (tours/index.ts) as functions, so their copy is translated
 * when they start.
 */
export function defineTour(id: string, steps: TourDefinition) {
  tours.set(id, steps);
}

export const findTarget = (target: string) =>
  document.querySelector<HTMLElement>(`[data-tour="${target}"]`);

export function startTour(id: string) {
  const steps =
    tours
      .get(id)?.()
      .filter((step) => findTarget(step.target)) ?? [];
  if (!steps.length) return;

  Object.assign(state, { id, steps, index: 0 });
}

export function useTour() {
  return {
    state,
    current: () => (state.id ? state.steps[state.index] : null),
    next() {
      if (state.index < state.steps.length - 1) state.index++;
      else stop();
    },
    back() {
      if (state.index > 0) state.index--;
    },
    stop,
  };
}

function stop() {
  Object.assign(state, { id: null, steps: [], index: 0 });
}

export const hasTour = (id: string) => tours.has(id);
