// PUBLIC API TYPES - Domain-focused, no Konva dependencies

export interface ShapeConfig {
  id?: string;
  points: number[]; // flat array: [x1, y1, x2, y2, ...]
  fill?: string;
  stroke?: string;
  strokeWidth?: number;
  opacity?: number;
  label?: string;
  floor?: number;
  highlighted?: boolean;
}

export interface BoundaryConfig {
  points: number[];
  stroke?: string;
  fill?: string;
  dash?: number[];
}

export interface CanvasConfig {
  width?: number;
  height?: number;
  backgroundColor?: string;
}

export type AutoScale = 'off' | 'scale' | 'stretch' | 'center';