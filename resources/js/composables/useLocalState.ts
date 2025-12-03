/**
 * Local storage wrapper that preserves types (string, boolean, number, null, undefined)
 *
 * Values are stored with type prefixes:
 * - bool:<value> for booleans
 * - num:<value> for numbers
 * - str:<value> for strings
 * - null: for null
 * - undefined: for undefined
 *
 * Usage:
 * const { get, set } = useLocalState()
 * set('myKey', true)
 * const value = get('myKey') // returns boolean true
 * const value = get('nonExistent') // returns undefined
 * const value = get('nonExistent', false) // returns false
 */
export function useLocalState() {
  const get = <T extends string | boolean | number | null | undefined>(
    key: string,
    defaultValue?: T
  ): T | undefined => {
    try {
      const stored = localStorage.getItem(key);

      if (stored === null) {
        return defaultValue;
      }

      // Parse type prefix
      if (stored.startsWith('bool:')) {
        const value = stored.slice(5);
        return (value === 'true') as T;
      }

      if (stored.startsWith('num:')) {
        const value = stored.slice(4);
        const parsed = Number(value);
        return (isNaN(parsed) ? defaultValue : parsed) as T;
      }

      if (stored.startsWith('str:')) {
        return stored.slice(4) as T;
      }

      if (stored === 'null:') {
        return null as T;
      }

      if (stored === 'undefined:') {
        return undefined as T;
      }
    } catch (error) {
      console.warn(`Failed to read from localStorage key "${key}":`, error);
      return defaultValue;
    }

    return undefined as never;
  };

  const set = <T extends string | boolean | number | null | undefined>(
    key: string,
    value: T
  ): void => {
    try {
      let encoded: string;

      if (value === null) {
        encoded = 'null:';
      } else if (value === undefined) {
        encoded = 'undefined:';
      } else if (typeof value === 'boolean') {
        encoded = `bool:${value}`;
      } else if (typeof value === 'number') {
        encoded = `num:${value}`;
      } else {
        encoded = `str:${value}`;
      }

      localStorage.setItem(key, encoded);
    } catch (error) {
      console.warn(`Failed to write to localStorage key "${key}":`, error);
    }
  };

  return { get, set };
}