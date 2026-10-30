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
export const tourState = createStore<{
  id: string | null;
  steps: TourStep[];
  index: number;
  /** After a first tour dismissed: whether to show no more (asked once) */
  offer?: boolean;
}>({
  id: null,
  steps: [],
  index: 0,
});

/** What the device remembers: the tours seen, whether to show none, a dismissal asked about */
const OFF = 'mtav.tours';
const SEEN = 'mtav.tour-seen.';
const ASKED = 'mtav.tours-asked';
const remembered = (key: string) => {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
};
const remember = (key: string, value: string) => {
  try {
    localStorage.setItem(key, value);
  } catch {
    // Without storage, tours are offered again on another visit
  }
};

/** Once the page has settled: a tour never jumps at someone mid-load */
const AUTO_AFTER_MS = 900;

/**
 * A page's tour, played by itself the first time someone arrives there (unless they asked
 * for no more tours); the (?) by the title plays it whenever they like
 */
export function autoTour(id: string) {
  // A browser driven by a program (tests, the manuals' pictures) plays none unless it asks
  const driven = navigator.webdriver && remembered(OFF) !== 'on';
  if (driven || remembered(OFF) === 'off' || remembered(SEEN + id)) return () => undefined;
  const later = window.setTimeout(() => {
    if (!tourState.get().id) startTour(id);
  }, AUTO_AFTER_MS);
  return () => window.clearTimeout(later);
}

/** No more tours played by themselves (the (?) still has them) */
export function noMoreTours() {
  remember(OFF, 'off');
  tourState.set({ ...tourState.get(), offer: false });
}

export const keepTours = () => tourState.set({ ...tourState.get(), offer: false });

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
  else stopTour({ finished: true });
}

export function previousStep() {
  const state = tourState.get();
  if (state.index > 0) tourState.set({ ...state, index: state.index - 1 });
}

/**
 * The tour ends, seen either way; the first one ever dismissed before its end asks, once,
 * whether to show no more
 */
export function stopTour({ finished = false }: { finished?: boolean } = {}) {
  const { id } = tourState.get();
  if (id) remember(SEEN + id, '1');
  const ask = Boolean(id) && !finished && !remembered(ASKED) && remembered(OFF) !== 'off';
  if (ask) remember(ASKED, '1');
  tourState.set({ id: null, steps: [], index: 0, offer: ask });
}
