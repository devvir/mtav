export interface Colors {
  red: {
    fill: string;
    stroke: string;
  };
  green: {
    fill: string;
    stroke: string;
  };
}

export interface Ohcl {
  o: number;
  h: number;
  c: number;
  l: number;
}

export const KEY_COLORS = Symbol();
