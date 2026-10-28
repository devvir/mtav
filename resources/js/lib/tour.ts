import { createStore } from './store';

export interface TourStep {
  /** The element to point at: data-tour="…" */
  target: string;
  title: string;
  body: string;
}

export type TourDefinition = () => TourStep[];

const tours = new Map<string, TourDefinition>();

/** The tour playing, if any (drawn by TourHost, in the shell) */
export const tourState = createStore<{ id: string | null; steps: TourStep[]; index: number }>({
  id: null,
  steps: [],
  index: 0,
});

/**
 * Guided tours: a sequence of steps, each pointing at an element of the page (marked
 * data-tour="…"). Steps whose element is not on the page are skipped. Tours are defined
 * once (tours.ts) as functions, so their words are translated when they start.
 */
export function defineTour(id: string, steps: TourDefinition) {
  tours.set(id, steps);
}

export const hasTour = (id: string) => tours.has(id);

export const findTarget = (target: string) =>
  document.querySelector<HTMLElement>(`[data-tour="${target}"]`);

export function startTour(id: string) {
  const steps =
    tours
      .get(id)?.()
      .filter((step) => findTarget(step.target)) ?? [];
  if (steps.length) tourState.set({ id, steps, index: 0 });
}

export const currentStep = () => {
  const { id, steps, index } = tourState.get();
  return id ? steps[index] : null;
};

export function nextStep() {
  const state = tourState.get();
  if (state.index < state.steps.length - 1) tourState.set({ ...state, index: state.index + 1 });
  else stopTour();
}

export function previousStep() {
  const state = tourState.get();
  if (state.index > 0) tourState.set({ ...state, index: state.index - 1 });
}

export const stopTour = () => tourState.set({ id: null, steps: [], index: 0 });
