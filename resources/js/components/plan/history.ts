import type { EditableItem } from './model';

/** How many steps back undo reaches; and how many of them outlive a reload */
const KEPT = 100;
const REMEMBERED = 40;

/**
 * What a state holds, whatever keys its shapes have: the canvas and each shape by its id
 * (one without an id is unsaved, so the state cannot match the server's)
 */
const signature = (state: string) => {
  const { canvas, items } = JSON.parse(state) as { canvas: unknown; items: EditableItem[] };
  return JSON.stringify({
    canvas,
    items: items.map(({ id, type, floor, name, color, polygon, placed }) => ({
      id,
      type,
      floor,
      name,
      color,
      polygon,
      placed: placed ?? true,
    })),
  });
};

/** The history as the browser keeps it */
interface Kept {
  /** The signature of the state last saved */
  saved: string;
  current: string;
  past: string[];
  future: string[];
  ids: [string, number][];
}

/**
 * The plan's undo history, as states (the plan written out): the one now, the ones before and
 * after it, and the one last saved (unsaved is anything else). It is kept in the browser, so
 * undo survives a reload: taken back only when what it last saved is still the plan as the
 * server has it (else someone changed it since). It holds the ids the server gave shapes drawn
 * here too, by their key, so a shape brought back by undo keeps its id.
 */
export class PlanHistory {
  private past: string[] = [];
  private future: string[] = [];
  private now: string;
  private saved: string;
  private ids = new Map<string, number>();
  private readonly storageKey: string;

  constructor(planId: number, initial: string) {
    this.storageKey = `mtav.plan-history.${planId}`;
    this.now = initial;
    this.saved = initial;
  }

  /** The state as it is now */
  get current() {
    return this.now;
  }

  get canUndo() {
    return this.past.length > 0;
  }

  get canRedo() {
    return this.future.length > 0;
  }

  get dirty() {
    return this.now !== this.saved;
  }

  /** The id the server gave a shape drawn here, if it did */
  idOf(key: string) {
    return this.ids.get(key);
  }

  /** A change kept: the steps undone before it are gone; whether it changed anything */
  push(next: string) {
    if (next === this.now) return false;
    this.past.push(this.now);
    if (this.past.length > KEPT) this.past.shift();
    this.future = [];
    this.now = next;
    this.remember();
    return true;
  }

  /** The state a step back, now the current one (null when there is none) */
  undo() {
    const previous = this.past.pop();
    if (previous === undefined) return null;
    this.future.push(this.now);
    this.now = previous;
    this.remember();
    return previous;
  }

  /** The state a step forward, now the current one (null when there is none) */
  redo() {
    const next = this.future.pop();
    if (next === undefined) return null;
    this.past.push(this.now);
    this.now = next;
    this.remember();
    return next;
  }

  /**
   * A save went through: `sent` is what was sent, `now` the state as it is after the ids the
   * server gave (`ids`, by key) were taken in. Changes made since it was sent stay unsaved.
   */
  markSaved(sent: string, now: string, ids: Record<string, number>) {
    Object.entries(ids).forEach(([key, id]) => this.ids.set(key, id));
    this.saved = sent === this.now ? now : sent;
    this.now = now;
    this.remember();
  }

  /**
   * Something the server holds anyway, put into every state (`include`): undoing past it never
   * takes it away. `now` is the current state with it.
   */
  includeEverywhere(include: (state: string) => string, now: string) {
    this.past = this.past.map(include);
    this.future = this.future.map(include);
    this.saved = include(this.saved);
    this.now = now;
    this.remember();
  }

  /** The history kept in the browser for this plan, taken back: the state it was at, or null */
  recall(): string | null {
    try {
      const kept = JSON.parse(localStorage.getItem(this.storageKey) ?? 'null') as Kept | null;
      if (!kept || kept.saved !== signature(this.now) || signature(kept.current) !== kept.saved)
        return null;
      this.ids = new Map(kept.ids);
      this.past = kept.past;
      this.future = kept.future;
      this.now = kept.current;
      this.saved = kept.current;
      return this.now;
    } catch {
      // Unreadable: start afresh
      return null;
    }
  }

  private remember() {
    try {
      localStorage.setItem(
        this.storageKey,
        JSON.stringify({
          saved: signature(this.saved),
          current: this.now,
          past: this.past.slice(-REMEMBERED),
          future: this.future.slice(-REMEMBERED),
          ids: [...this.ids],
        } satisfies Kept),
      );
    } catch {
      // No room, or no storage: undo lasts until the page closes
    }
  }
}
