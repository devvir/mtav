import { OPTIONS, SEARCH, SWITCH } from '.';

export type OptionValue = string | number;

// Configuration interface for config-driven usage
export interface FilterConfig {
  [key: string]: {
    type: typeof SEARCH | typeof SWITCH | typeof OPTIONS;
    options?: Record<OptionValue, string>;
    value?: OptionValue;
  };
}
