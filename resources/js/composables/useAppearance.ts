type Mode = 'light' | 'dark' | 'system';
type ColorTheme = 'default' | 'ocean' | 'forest' | 'sunset' | 'mono' | 'high-contrast';

export function updateMode(value: Mode) {
  if (typeof window === 'undefined') {
    return;
  }

  if (value === 'system') {
    const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
    const systemMode = mediaQueryList.matches ? 'dark' : 'light';

    document.documentElement.classList.toggle('dark', systemMode === 'dark');
  } else {
    document.documentElement.classList.toggle('dark', value === 'dark');
  }
}

export function updateColorTheme(value: ColorTheme) {
  if (typeof window === 'undefined') {
    return;
  }

  // Remove all theme classes
  document.documentElement.classList.remove(
    'theme-ocean',
    'theme-forest',
    'theme-sunset',
    'theme-mono',
    'theme-high-contrast'
  );

  // Add new theme class if not default
  if (value !== 'default') {
    document.documentElement.classList.add(`theme-${value}`);
  }
}

const setCookie = (name: string, value: string, days = 365) => {
  if (typeof document === 'undefined') {
    return;
  }

  const maxAge = days * 24 * 60 * 60;

  document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
  if (typeof window === 'undefined') {
    return null;
  }

  return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredMode = () => {
  if (typeof window === 'undefined') {
    return null;
  }

  return localStorage.getItem('mode') as Mode | null;
};

const getStoredColorTheme = () => {
  if (typeof window === 'undefined') {
    return null;
  }

  return localStorage.getItem('colorTheme') as ColorTheme | null;
};

const handleSystemModeChange = () => {
  const currentMode = getStoredMode();

  updateMode(currentMode || 'system');
};

export function initializeTheme() {
  if (typeof window === 'undefined') {
    return;
  }

  // Migrate legacy 'appearance' to 'mode' if needed
  const legacyAppearance = localStorage.getItem('appearance');
  if (legacyAppearance && !localStorage.getItem('mode')) {
    localStorage.setItem('mode', legacyAppearance);
    localStorage.removeItem('appearance');
  }

  // Initialize mode from saved preference or default to system...
  const savedMode = getStoredMode();
  updateMode(savedMode || 'system');

  // Initialize color theme from saved preference or default to 'default'...
  const savedColorTheme = getStoredColorTheme();
  updateColorTheme(savedColorTheme || 'default');

  // Set up system mode change listener...
  mediaQuery()?.addEventListener('change', handleSystemModeChange);
}

const mode = ref<Mode>('system');
const colorTheme = ref<ColorTheme>('default');

export function useAppearance() {
  onMounted(() => {
    const savedMode = localStorage.getItem('mode') as Mode | null;
    const savedColorTheme = localStorage.getItem('colorTheme') as ColorTheme | null;

    if (savedMode) {
      mode.value = savedMode;
    }

    if (savedColorTheme) {
      colorTheme.value = savedColorTheme;
    }
  });

  function updateModePreference(value: Mode) {
    mode.value = value;

    // Store in localStorage for client-side persistence...
    localStorage.setItem('mode', value);

    // Store in cookie for SSR...
    setCookie('mode', value);

    updateMode(value);
  }

  function updateColorThemePreference(value: ColorTheme) {
    colorTheme.value = value;

    // Store in localStorage for client-side persistence...
    localStorage.setItem('colorTheme', value);

    // Store in cookie for SSR...
    setCookie('colorTheme', value);

    updateColorTheme(value);
  }

  return {
    mode,
    colorTheme,
    updateMode: updateModePreference,
    updateColorTheme: updateColorThemePreference,
  };
}
