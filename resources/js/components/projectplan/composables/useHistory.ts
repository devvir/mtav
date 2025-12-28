export interface HistoryState {
  items: PlanItem[];
}

const MAX_HISTORY = 50;

export function useHistory(initialState: HistoryState) {
  const history = ref<HistoryState[]>([JSON.parse(JSON.stringify(initialState))]);
  const currentIndex = ref(0);

  const canUndo = computed(() => currentIndex.value > 0);
  const canRedo = computed(() => currentIndex.value < history.value.length - 1);
  const currentState = computed(() => history.value[currentIndex.value]);

  function saveState(state: HistoryState) {
    // Remove any future history (redo stack) when making a new change
    history.value = history.value.slice(0, currentIndex.value + 1);

    // Add new state
    history.value.push(JSON.parse(JSON.stringify(state)));

    // Limit history size
    if (history.value.length > MAX_HISTORY) {
      history.value.shift();
    } else {
      currentIndex.value++;
    }
  }

  function undo() {
    if (canUndo.value) {
      currentIndex.value--;
    }
  }

  function redo() {
    if (canRedo.value) {
      currentIndex.value++;
    }
  }

  function reset() {
    // Reset to initial state
    currentIndex.value = 0;
    history.value = [history.value[0]];
  }

  return {
    currentState,
    canUndo,
    canRedo,
    saveState,
    undo,
    redo,
    reset,
  };
}
