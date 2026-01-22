import { getCookie, setCookie } from '@/lib/utils';

type Mode = 'light' | 'dark' | 'system';
type Theme = 'default' | 'ocean' | 'forest' | 'sunset' | 'mono' | 'high-contrast';

/**
 * Apply dark class to document based on mode value.
 */
function applyMode(value: Mode) {
  if (value === 'system') {
    const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
    const systemMode = mediaQueryList.matches ? 'dark' : 'light';
    document.documentElement.classList.toggle('dark', systemMode === 'dark');
  } else {
    document.documentElement.classList.toggle('dark', value === 'dark');
  }
}

/**
 * Apply theme class to document based on theme value.
 */
function applyTheme(value: Theme) {
  document.documentElement.classList.remove(
    'theme-ocean',
    'theme-forest',
    'theme-sunset',
    'theme-mono',
    'theme-high-contrast',
  );

  if (value !== 'default') {
    document.documentElement.classList.add(`theme-${value}`);
  }
}

/**
 * Listen to system dark mode changes.
 */
const watchSystemMode = (callback: () => void) => {
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', callback);
};

const mode = ref<Mode>('system');
const theme = ref<Theme>('default');

/**
 * Initialize theme on app startup from cookies.
 * Called once before Vue mounts.
 */
export function initializeTheme() {
  const savedMode = (getCookie('mode') as Mode | null) || 'system';
  const savedTheme = (getCookie('theme') as Theme | null) || 'default';

  mode.value = savedMode;
  theme.value = savedTheme;

  applyMode(savedMode);
  applyTheme(savedTheme);

  // Re-apply mode if system preference changes
  watchSystemMode(() => {
    if (mode.value === 'system') {
      applyMode('system');
    }
  });
}

/**
 * Composable to manage theme and mode with cookie persistence.
 */
export function useTheme() {
  function setMode(value: Mode) {
    mode.value = value;
    setCookie('mode', value);
    applyMode(value);
  }

  function setTheme(value: Theme) {
    theme.value = value;
    setCookie('theme', value);
    applyTheme(value);
  }

  return {
    mode: readonly(mode),
    theme: readonly(theme),
    setMode,
    setTheme,
  };
}
